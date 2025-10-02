<?php

namespace App\Libraries;

use App\Models\ReturnModel;
use App\Models\ReturnItemModel;
use App\Models\PosSaleModel;
use App\Traits\TenantAwareTrait;

class ReturnService
{
    use TenantAwareTrait;
    
    protected ReturnModel $returnModel;
    protected ReturnItemModel $returnItemModel;
    protected PosSaleModel $saleModel;
    
    public function __construct()
    {
        $this->returnModel = new ReturnModel();
        $this->returnItemModel = new ReturnItemModel();
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * Processar devolução ou troca
     * 
     * @param int $idSale ID da venda original
     * @param array $returnData
     * @return array ['success' => bool, 'return' => array|null]
     */
    public function processReturn(int $idSale, array $returnData): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar venda original
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale || !$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Venda não encontrada ou não pertence ao tenant atual');
        }
        
        // Validar que está finalizada
        if ($sale['status'] !== 'finalized') {
            return ['success' => false, 'error' => 'Apenas vendas finalizadas podem ser devolvidas'];
        }
        
        // Validar prazo
        $validation = $this->validateReturnPeriod($sale);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['reason']];
        }
        
        // Validar tipo de devolução permitido
        $type = $returnData['type'] ?? 'full_return';
        
        $config = $this->getTenantConfig();
        
        if ($type === 'partial_return' && !$config['allow_partial_returns']) {
            return ['success' => false, 'error' => 'Devoluções parciais não são permitidas'];
        }
        
        if ($type === 'exchange' && !$config['allow_exchanges']) {
            return ['success' => false, 'error' => 'Trocas não são permitidas'];
        }
        
        // Obter operador
        $operatorId = (int) (session()->get('id_login') ?? $_SESSION['id_login'] ?? 0);
        
        // Verificar se requer aprovação
        $approvedBy = null;
        
        if ($config['require_return_approval']) {
            if (empty($returnData['approved_by'])) {
                return ['success' => false, 'error' => 'Aprovação de gerente é obrigatória para devoluções'];
            }
            $approvedBy = (int) $returnData['approved_by'];
        }
        
        // Calcular total devolvido
        $totalReturned = (float) ($returnData['total_returned'] ?? $sale['total']);
        
        // Validar que não excede total da venda
        if ($totalReturned > $sale['total']) {
            return ['success' => false, 'error' => 'Valor da devolução não pode exceder o total da venda'];
        }
        
        // Iniciar transação
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // Registrar devolução
            $returnId = $this->returnModel->insert([
                'id_original_sale' => $idSale,
                'type' => $type,
                'reason' => (string) ($returnData['reason'] ?? 'Não especificado'),
                'total_returned' => $totalReturned,
                'refund_method' => (string) ($returnData['refund_method'] ?? 'same_method'),
                'refund_status' => 'pending',
                'processed_by' => $operatorId,
                'approved_by' => $approvedBy,
                'notes' => (string) ($returnData['notes'] ?? ''),
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa,
            ]);
            
            // Processar itens devolvidos (se especificado)
            if (isset($returnData['items']) && is_array($returnData['items'])) {
                foreach ($returnData['items'] as $item) {
                    $this->returnItemModel->insert([
                        'id_return' => $returnId,
                        'id_original_item' => (int) $item['id_original_item'],
                        'id_produto' => (int) $item['id_produto'],
                        'quantity' => (int) $item['quantity'],
                        'unit_price' => (float) $item['unit_price'],
                        'total_price' => (float) $item['total_price'],
                        'condition' => (string) ($item['condition'] ?? 'perfect'),
                        'restock' => (bool) ($item['restock'] ?? true),
                        'id_contador' => $idContador,
                        'id_empresa' => $idEmpresa,
                    ]);
                }
            }
            
            // Repor estoque (se configurado)
            if (isset($returnData['restock']) && $returnData['restock']) {
                $this->restockItems($returnId);
            }
            
            // Processar estorno de pagamento
            $refundResult = $this->processRefund($sale, $totalReturned, $returnData['refund_method'] ?? 'same_method');
            
            if ($refundResult['success']) {
                $this->returnModel->update($returnId, ['refund_status' => 'completed']);
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                return ['success' => false, 'error' => 'Erro ao processar devolução'];
            }
            
            log_message('info', '[Return] Devolução processada', [
                'id_return' => $returnId,
                'id_sale' => $idSale,
                'type' => $type,
                'total_returned' => $totalReturned,
                'operator' => $operatorId,
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            return [
                'success' => true,
                'return' => $this->returnModel->find($returnId),
                'refund' => $refundResult,
            ];
            
        } catch (\Exception $e) {
            $db->transRollback();
            
            log_message('error', '[Return] Erro ao processar devolução', [
                'error' => $e->getMessage(),
                'id_sale' => $idSale,
            ]);
            
            return ['success' => false, 'error' => 'Erro ao processar devolução: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validar prazo de devolução
     */
    protected function validateReturnPeriod(array $sale): array
    {
        $config = $this->getTenantConfig();
        $daysLimit = (int) ($config['return_days_limit'] ?? 7);
        
        if ($daysLimit === 0) {
            return ['valid' => true]; // Sem limite
        }
        
        $saleDate = strtotime($sale['created_at']);
        $limitDate = strtotime("+{$daysLimit} days", $saleDate);
        $now = time();
        
        if ($now > $limitDate) {
            return [
                'valid' => false,
                'reason' => sprintf('Prazo para devolução expirado (%d dias)', $daysLimit),
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Processar estorno de pagamento
     */
    protected function processRefund(array $sale, float $amount, string $method): array
    {
        // Se método for 'same_method', identificar forma de pagamento original
        if ($method === 'same_method') {
            $paymentType = $sale['payment_type'] ?? 'cash';
            
            if (in_array($paymentType, ['credit', 'debit'])) {
                // Estornar via TEF
                return $this->refundTef($sale, $amount);
            }
            
            if ($paymentType === 'pix') {
                // Estornar via PIX
                return $this->refundPix($sale, $amount);
            }
            
            // Dinheiro ou outros
            $method = 'cash';
        }
        
        // Estorno em dinheiro, crédito ou voucher
        return [
            'success' => true,
            'method' => $method,
            'amount' => $amount,
            'message' => "Estorno de R$ {$amount} via {$method}",
        ];
    }
    
    /**
     * Estornar via TEF
     */
    protected function refundTef(array $sale, float $amount): array
    {
        // Se houver transação TEF vinculada
        if (empty($sale['id_tef_transaction'])) {
            return [
                'success' => false,
                'error' => 'Transação TEF não encontrada para estorno',
            ];
        }
        
        try {
            $tefService = new TefService();
            $result = $tefService->cancel($sale['id_tef_transaction']);
            
            return [
                'success' => $result['success'] ?? false,
                'method' => 'tef',
                'amount' => $amount,
                'message' => $result['success'] ? 'Estorno TEF processado' : 'Falha ao estornar TEF',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao estornar TEF: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Estornar via PIX
     */
    protected function refundPix(array $sale, float $amount): array
    {
        // PIX não tem estorno automático - registrar como pendente
        return [
            'success' => true,
            'method' => 'pix',
            'amount' => $amount,
            'message' => 'Estorno PIX registrado. Processar manualmente via banco.',
            'requires_manual_action' => true,
        ];
    }
    
    /**
     * Repor itens no estoque
     */
    protected function restockItems(int $idReturn): void
    {
        $items = $this->returnItemModel->getForRestock($idReturn);
        
        foreach ($items as $item) {
            // Aqui você chamaria o EstoqueService para repor
            // Por simplicidade, apenas logando
            log_message('info', '[Return] Item reposto no estoque', [
                'id_return' => $idReturn,
                'id_produto' => $item['id_produto'],
                'quantity' => $item['quantity'],
            ]);
        }
    }
    
    /**
     * Listar devoluções
     */
    public function listReturns(array $filters = []): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $builder = $this->returnModel->builder();
        
        $builder->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa);
        
        if (isset($filters['type'])) {
            $builder->where('type', $filters['type']);
        }
        
        if (isset($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }
        
        return $builder->orderBy('created_at', 'DESC')
                       ->get()
                       ->getResultArray();
    }
    
    /**
     * Obter configurações do tenant
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

