<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

/**
 * Controlador de Sincronização - Modo Offline
 */
class Sync extends ResourceController
{
    protected $format = 'json';

    /**
     * Sincroniza operação do outbox
     * 
     * POST /api/sync/outbox
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function outbox()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            
            // Validar tenant
            if (!$idContador || !$idEmpresa) {
                return $this->fail('Sessão inválida', 401);
            }
            
            $data = $this->request->getJSON(true);
            
            if (!$data || !isset($data['operation'])) {
                return $this->failValidationErrors('Dados inválidos');
            }
            
            log_message('info', '[Sync::outbox] Recebendo operação', [
                'operation' => $data['operation'],
                'tenant' => "{$idContador}:{$idEmpresa}"
            ]);
            
            // Processar operação baseado no tipo
            $result = $this->processOperation($data, $idContador, $idEmpresa);
            
            if ($result['success']) {
                return $this->respondCreated([
                    'message' => 'Operação sincronizada com sucesso',
                    'data' => $result['data'] ?? null
                ]);
            } else {
                return $this->fail($result['error'] ?? 'Erro ao processar operação', 400);
            }
            
        } catch (\Throwable $e) {
            log_message('error', '[Sync::outbox] Erro na sincronização', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return $this->failServerError('Erro ao sincronizar operação');
        }
    }
    
    /**
     * Processa operação do outbox
     */
    protected function processOperation(array $data, int $idContador, int $idEmpresa): array
    {
        $operation = $data['operation'];
        $payload = $data['data'] ?? [];
        
        // Validar que a operação pertence ao tenant correto
        if (isset($payload['id_contador']) && $payload['id_contador'] != $idContador) {
            return ['success' => false, 'error' => 'Tenant inválido'];
        }
        
        if (isset($payload['id_empresa']) && $payload['id_empresa'] != $idEmpresa) {
            return ['success' => false, 'error' => 'Empresa inválida'];
        }
        
        // Processar baseado no tipo de operação
        switch ($operation) {
            case 'create_sale':
                return $this->processSale($payload, $idContador, $idEmpresa);
                
            case 'update_sale':
                return $this->updateSale($payload, $idContador, $idEmpresa);
                
            case 'cancel_sale':
                return $this->cancelSale($payload, $idContador, $idEmpresa);
                
            case 'create_customer':
                return $this->processCustomer($payload, $idContador, $idEmpresa);
                
            default:
                log_message('warning', '[Sync::processOperation] Operação desconhecida', [
                    'operation' => $operation
                ]);
                return ['success' => false, 'error' => 'Operação não suportada'];
        }
    }
    
    /**
     * Processa criação de venda
     */
    protected function processSale(array $data, int $idContador, int $idEmpresa): array
    {
        try {
            $saleModel = new \App\Models\PosSaleModel();
            
            // Garantir campos de tenant
            $data['id_contador'] = $idContador;
            $data['id_empresa'] = $idEmpresa;
            
            $saleId = $saleModel->insert($data);
            
            if ($saleId) {
                return ['success' => true, 'data' => ['id' => $saleId]];
            } else {
                return ['success' => false, 'error' => implode(', ', $saleModel->errors())];
            }
            
        } catch (\Throwable $e) {
            log_message('error', '[Sync::processSale] Erro', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Erro ao criar venda'];
        }
    }
    
    /**
     * Processa atualização de venda
     */
    protected function updateSale(array $data, int $idContador, int $idEmpresa): array
    {
        try {
            $saleModel = new \App\Models\PosSaleModel();
            
            if (!isset($data['id_pos_sale'])) {
                return ['success' => false, 'error' => 'ID da venda não informado'];
            }
            
            $saleId = $data['id_pos_sale'];
            unset($data['id_pos_sale']);
            
            // Validar ownership
            $sale = $saleModel->where('id_contador', $idContador)
                              ->where('id_empresa', $idEmpresa)
                              ->find($saleId);
            
            if (!$sale) {
                return ['success' => false, 'error' => 'Venda não encontrada'];
            }
            
            $updated = $saleModel->update($saleId, $data);
            
            if ($updated) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => implode(', ', $saleModel->errors())];
            }
            
        } catch (\Throwable $e) {
            log_message('error', '[Sync::updateSale] Erro', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Erro ao atualizar venda'];
        }
    }
    
    /**
     * Processa cancelamento de venda
     */
    protected function cancelSale(array $data, int $idContador, int $idEmpresa): array
    {
        try {
            if (!isset($data['id_pos_sale'])) {
                return ['success' => false, 'error' => 'ID da venda não informado'];
            }
            
            // Usar o controlador de cancelamento existente
            $posController = new \App\Controllers\Api\Pos();
            $response = $posController->cancel($data['id_pos_sale']);
            
            if ($response->getStatusCode() === 200) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Erro ao cancelar venda'];
            }
            
        } catch (\Throwable $e) {
            log_message('error', '[Sync::cancelSale] Erro', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Erro ao cancelar venda'];
        }
    }
    
    /**
     * Processa criação de cliente
     */
    protected function processCustomer(array $data, int $idContador, int $idEmpresa): array
    {
        try {
            $customerModel = new \App\Models\ClienteModel();
            
            // Garantir campos de tenant
            $data['id_contador'] = $idContador;
            $data['id_empresa'] = $idEmpresa;
            
            $customerId = $customerModel->insert($data);
            
            if ($customerId) {
                return ['success' => true, 'data' => ['id' => $customerId]];
            } else {
                return ['success' => false, 'error' => implode(', ', $customerModel->errors())];
            }
            
        } catch (\Throwable $e) {
            log_message('error', '[Sync::processCustomer] Erro', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Erro ao criar cliente'];
        }
    }
    
    /**
     * Retorna estatísticas de sincronização
     * GET /api/sync/stats
     */
    public function stats()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            
            if (!$idContador || !$idEmpresa) {
                return $this->fail('Sessão inválida', 401);
            }
            
            // Stats do outbox
            $db = \Config\Database::connect();
            $pending = $db->table('outbox_events')
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->whereIn('status', ['pending', 'retry'])
                ->countAllResults();
            
            // Stats de auditoria
            $auditStats = \App\Libraries\OfflineAudit::getSyncStats($idContador, $idEmpresa);
            
            // Última sincronização
            $lastSync = $db->table('outbox_events')
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->where('status', 'processed')
                ->orderBy('processed_at', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();
            
            return $this->respond([
                'pending' => $pending,
                'audit' => $auditStats,
                'last_sync' => $lastSync['processed_at'] ?? null,
                'is_offline' => function_exists('is_offline_mode') ? is_offline_mode() : false
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[Sync::stats] Erro', ['error' => $e->getMessage()]);
            return $this->failServerError('Erro ao obter estatísticas');
        }
    }
    
    /**
     * Executa sincronização manual
     * POST /api/sync/execute
     */
    public function execute()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            
            if (!$idContador || !$idEmpresa) {
                return $this->fail('Sessão inválida', 401);
            }
            
            // Verificar se não está offline
            if (function_exists('is_offline_mode') && is_offline_mode()) {
                return $this->fail('Sistema em modo offline. Conecte-se à internet primeiro.', 503);
            }
            
            log_message('info', '[Sync::execute] Iniciando sincronização manual', [
                'tenant' => "{$idContador}:{$idEmpresa}"
            ]);
            
            // Executar sync via comando
            $sync = new \App\Commands\SyncCloud();
            $sync->runWithOptions([
                'tables' => [],
                'limit' => 500,
                'dry' => false,
                'useOutbox' => true,
            ]);
            
            // Atualizar auditoria
            $pendingAudits = \App\Libraries\OfflineAudit::getPendingOperations($idContador, $idEmpresa);
            foreach ($pendingAudits as $audit) {
                \App\Libraries\OfflineAudit::updateStatus((int) $audit['id'], 'synced', 'Sincronização manual executada');
            }
            
            return $this->respondCreated([
                'message' => 'Sincronização executada com sucesso',
                'synced' => count($pendingAudits)
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[Sync::execute] Erro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->failServerError('Erro ao executar sincronização');
        }
    }
    
    /**
     * Health check para verificar conectividade
     * GET /api/health-check
     */
    public function healthCheck()
    {
        try {
            // Verificar conexão com banco cloud
            $cloud = \Config\Database::connect('cloud');
            $cloud->query('SELECT 1');
            
            return $this->respond([
                'status' => 'online',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Throwable $e) {
            return $this->respond([
                'status' => 'offline',
                'timestamp' => date('Y-m-d H:i:s')
            ], 503);
        }
    }
}

