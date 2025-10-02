<?php

namespace App\Libraries;

use App\Models\PosSaleModel;
use App\Traits\TenantAwareTrait;

class SuspensionService
{
    use TenantAwareTrait;
    
    protected PosSaleModel $saleModel;
    
    public function __construct()
    {
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * Suspender uma venda
     * 
     * @param int $idSale ID da venda
     * @param string $reason Motivo da suspensão
     * @param int|null $operatorId ID do operador (opcional, pega da sessão)
     * @return array ['success' => bool, 'sale' => array|null, 'error' => string|null]
     */
    public function suspend(int $idSale, string $reason = '', ?int $operatorId = null): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar venda
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale || !$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Venda não encontrada ou não pertence ao tenant atual');
        }
        
        // Validar que está pending
        if ($sale['status'] !== 'pending') {
            return [
                'success' => false,
                'error' => 'Apenas vendas pendentes podem ser suspensas',
            ];
        }
        
        // Validar que não está já suspensa
        if ($sale['is_suspended']) {
            return [
                'success' => false,
                'error' => 'Venda já está suspensa',
            ];
        }
        
        // Verificar limite de suspensas
        $config = $this->getTenantConfig();
        $maxSuspended = (int) ($config['max_suspended_sales'] ?? 10);
        
        $currentSuspended = $this->saleModel->where('is_suspended', true)
                                           ->where('id_contador', $idContador)
                                           ->where('id_empresa', $idEmpresa)
                                           ->countAllResults();
        
        if ($currentSuspended >= $maxSuspended) {
            return [
                'success' => false,
                'error' => "Limite de vendas suspensas atingido ({$maxSuspended}). Finalize ou cancele vendas suspensas antes de suspender novas.",
            ];
        }
        
        // Calcular expiração
        $timeoutHours = (int) ($config['suspension_timeout_hours'] ?? 24);
        $expiresAt = null;
        
        if ($timeoutHours > 0) {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$timeoutHours} hours"));
        }
        
        // Obter operador
        if (!$operatorId) {
            $operatorId = (int) (session()->get('id_login') ?? $_SESSION['id_login'] ?? 0);
        }
        
        // Suspender venda
        $this->saleModel->update($idSale, [
            'is_suspended' => true,
            'suspended_at' => date('Y-m-d H:i:s'),
            'suspended_by' => $operatorId,
            'suspended_reason' => $reason,
            'suspension_expires_at' => $expiresAt,
        ]);
        
        log_message('info', '[Suspension] Venda suspensa', [
            'id_sale' => $idSale,
            'reason' => $reason,
            'operator' => $operatorId,
            'expires_at' => $expiresAt,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return [
            'success' => true,
            'sale' => $this->saleModel->find($idSale),
        ];
    }
    
    /**
     * Retomar venda suspensa
     * 
     * @param int $idSale ID da venda
     * @param int|null $operatorId ID do operador (opcional)
     * @return array ['success' => bool, 'sale' => array|null, 'error' => string|null]
     */
    public function resume(int $idSale, ?int $operatorId = null): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar venda
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale || !$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Venda não encontrada ou não pertence ao tenant atual');
        }
        
        // Validar que está suspensa
        if (!$sale['is_suspended']) {
            return [
                'success' => false,
                'error' => 'Venda não está suspensa',
            ];
        }
        
        // Validar que não expirou
        if ($sale['suspension_expires_at'] && strtotime($sale['suspension_expires_at']) < time()) {
            return [
                'success' => false,
                'error' => 'Venda suspensa expirou e foi cancelada automaticamente',
            ];
        }
        
        // Obter operador
        if (!$operatorId) {
            $operatorId = (int) (session()->get('id_login') ?? $_SESSION['id_login'] ?? 0);
        }
        
        // Retomar venda
        $this->saleModel->update($idSale, [
            'is_suspended' => false,
            'resumed_at' => date('Y-m-d H:i:s'),
            'resumed_by' => $operatorId,
        ]);
        
        log_message('info', '[Suspension] Venda retomada', [
            'id_sale' => $idSale,
            'operator' => $operatorId,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return [
            'success' => true,
            'sale' => $this->saleModel->find($idSale),
        ];
    }
    
    /**
     * Listar vendas suspensas
     * 
     * @param array $filters ['operator_id' => int, 'date_from' => string]
     * @return array
     */
    public function listSuspended(array $filters = []): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $builder = $this->saleModel->builder();
        
        $builder->where('is_suspended', true)
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa);
        
        // Filtros opcionais
        if (isset($filters['operator_id'])) {
            $builder->where('suspended_by', (int) $filters['operator_id']);
        }
        
        if (isset($filters['date_from'])) {
            $builder->where('suspended_at >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $builder->where('suspended_at <=', $filters['date_to']);
        }
        
        return $builder->orderBy('suspended_at', 'DESC')
                       ->get()
                       ->getResultArray();
    }
    
    /**
     * Expirar vendas suspensas antigas (CRON JOB)
     * 
     * @return int Quantidade de vendas expiradas
     */
    public function expireOld(): int
    {
        $now = date('Y-m-d H:i:s');
        
        // Buscar suspensas expiradas (sem filtro de tenant para cron global)
        $db = \Config\Database::connect();
        
        $expired = $db->table('pos_sales')
                      ->where('is_suspended', true)
                      ->where('suspension_expires_at IS NOT NULL')
                      ->where('suspension_expires_at <', $now)
                      ->get()
                      ->getResultArray();
        
        $count = 0;
        
        foreach ($expired as $sale) {
            // Cancelar venda
            $db->table('pos_sales')
               ->where('id_pos_sale', $sale['id_pos_sale'])
               ->update([
                   'is_suspended' => false,
                   'status' => 'cancelled',
                   'updated_at' => $now,
               ]);
            
            $count++;
            
            log_message('info', '[Suspension] Venda expirada automaticamente', [
                'id_sale' => $sale['id_pos_sale'],
                'suspended_at' => $sale['suspended_at'],
                'expires_at' => $sale['suspension_expires_at'],
                'tenant' => "{$sale['id_contador']}:{$sale['id_empresa']}",
            ]);
        }
        
        return $count;
    }
    
    /**
     * Obter estatísticas de suspensões
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getStats(string $dateFrom, string $dateTo): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $builder = $this->saleModel->builder();
        
        $total = $builder->where('suspended_at IS NOT NULL')
                         ->where('suspended_at >=', $dateFrom)
                         ->where('suspended_at <=', $dateTo)
                         ->where('id_contador', $idContador)
                         ->where('id_empresa', $idEmpresa)
                         ->countAllResults();
        
        $resumed = $builder->where('resumed_at IS NOT NULL')
                           ->where('resumed_at >=', $dateFrom)
                           ->where('resumed_at <=', $dateTo)
                           ->where('id_contador', $idContador)
                           ->where('id_empresa', $idEmpresa)
                           ->countAllResults();
        
        $expired = $builder->where('suspended_at IS NOT NULL')
                           ->where('suspended_at >=', $dateFrom)
                           ->where('suspended_at <=', $dateTo)
                           ->where('is_suspended', false)
                           ->where('status', 'cancelled')
                           ->where('id_contador', $idContador)
                           ->where('id_empresa', $idEmpresa)
                           ->countAllResults();
        
        $active = $builder->where('is_suspended', true)
                          ->where('id_contador', $idContador)
                          ->where('id_empresa', $idEmpresa)
                          ->countAllResults();
        
        return [
            'total_suspended' => $total,
            'resumed' => $resumed,
            'expired' => $expired,
            'currently_suspended' => $active,
            'resume_rate' => $total > 0 ? round(($resumed / $total) * 100, 2) : 0,
        ];
    }
    
    /**
     * Obter configurações de suspensão do tenant
     */
    protected function getTenantConfig(): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $db = \Config\Database::connect();
        
        $config = $db->table('empresas')
                     ->where('id_contador', $idContador)
                     ->where('id_empresa', $idEmpresa)
                     ->get()
                     ->getRowArray();
        
        return $config ?? [];
    }
}

