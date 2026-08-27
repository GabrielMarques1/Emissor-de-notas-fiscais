<?php

namespace App\Libraries;

use Config\Database;

/**
 * Fallbacks para Operações Críticas em Modo Offline
 * Garante que operações essenciais nunca falhem completamente
 */
class CriticalOperationFallback
{
    /**
     * Executa operação crítica com fallback automático
     * 
     * @param callable $primaryOperation Operação principal
     * @param callable $fallbackOperation Operação de fallback
     * @param array $context Contexto da operação
     * @return array ['success' => bool, 'data' => mixed, 'fallback_used' => bool]
     */
    public static function execute(callable $primaryOperation, callable $fallbackOperation, array $context = []): array
    {
        $operationId = uniqid('op_');
        
        log_message('info', '[CriticalFallback] Iniciando operação crítica', [
            'operation_id' => $operationId,
            'context' => $context
        ]);
        
        try {
            // Tentar operação principal
            $result = $primaryOperation();
            
            log_message('info', '[CriticalFallback] Operação principal bem-sucedida', [
                'operation_id' => $operationId
            ]);
            
            return [
                'success' => true,
                'data' => $result,
                'fallback_used' => false,
                'operation_id' => $operationId
            ];
            
        } catch (\Throwable $primaryError) {
            log_message('warning', '[CriticalFallback] Operação principal falhou, tentando fallback', [
                'operation_id' => $operationId,
                'primary_error' => $primaryError->getMessage()
            ]);
            
            try {
                // Tentar fallback
                $fallbackResult = $fallbackOperation($primaryError);
                
                log_message('info', '[CriticalFallback] Fallback bem-sucedido', [
                    'operation_id' => $operationId
                ]);
                
                // Registrar para sincronização posterior
                self::registerForLaterSync($context, $fallbackResult, $operationId);
                
                return [
                    'success' => true,
                    'data' => $fallbackResult,
                    'fallback_used' => true,
                    'operation_id' => $operationId,
                    'primary_error' => $primaryError->getMessage()
                ];
                
            } catch (\Throwable $fallbackError) {
                log_message('error', '[CriticalFallback] Ambas operações falharam', [
                    'operation_id' => $operationId,
                    'primary_error' => $primaryError->getMessage(),
                    'fallback_error' => $fallbackError->getMessage()
                ]);
                
                return [
                    'success' => false,
                    'data' => null,
                    'fallback_used' => true,
                    'operation_id' => $operationId,
                    'primary_error' => $primaryError->getMessage(),
                    'fallback_error' => $fallbackError->getMessage()
                ];
            }
        }
    }
    
    /**
     * Fallback para criação de venda
     */
    public static function createSaleFallback(array $saleData): array
    {
        return self::execute(
            // Operação principal: salvar na nuvem
            function() use ($saleData) {
                if (function_exists('is_offline_mode') && is_offline_mode()) {
                    throw new \Exception('Sistema offline');
                }
                
                $saleModel = new \App\Models\PosSaleModel();
                $saleId = $saleModel->insert($saleData);
                
                if (!$saleId) {
                    throw new \Exception('Erro ao salvar venda: ' . implode(', ', $saleModel->errors()));
                }
                
                return ['id' => $saleId, 'status' => 'saved_cloud'];
            },
            
            // Fallback: salvar localmente
            function($error) use ($saleData) {
                $localDb = Database::connect('local_backup');
                
                // Garantir tenant_id
                [$idContador, $idEmpresa] = self::resolveTenantIds($saleData);
                $saleData['id_contador'] = $idContador;
                $saleData['id_empresa'] = $idEmpresa;
                $saleData['sync_status'] = 'pending';
                $saleData['created_offline'] = 1;
                
                $saleId = $localDb->table('pos_sales')->insert($saleData, true);
                
                // Registrar no outbox
                Outbox::record('pos_sales', ['id_pos_sale' => $saleId], 'insert', $saleData);
                
                // Registrar auditoria
                OfflineAudit::log('create_sale_fallback', 'pos_sale', $saleId, $saleData, 'pending');
                
                return ['id' => $saleId, 'status' => 'saved_local', 'needs_sync' => true];
            },
            
            ['operation' => 'create_sale', 'tenant' => $saleData['id_contador'] ?? 0]
        );
    }
    
    /**
     * Fallback para atualização de estoque
     */
    public static function updateStockFallback(int $productId, int $quantity, string $operation = 'subtract'): array
    {
        return self::execute(
            // Operação principal
            function() use ($productId, $quantity, $operation) {
                if (function_exists('is_offline_mode') && is_offline_mode()) {
                    throw new \Exception('Sistema offline');
                }
                
                $productModel = new \App\Models\ProdutoModel();
                $product = $productModel->find($productId);
                
                if (!$product) {
                    throw new \Exception('Produto não encontrado');
                }
                
                $newStock = $operation === 'add' 
                    ? $product['estoque'] + $quantity 
                    : $product['estoque'] - $quantity;
                
                if ($newStock < 0) {
                    throw new \Exception('Estoque insuficiente');
                }
                
                $updated = $productModel->update($productId, ['estoque' => $newStock]);
                
                if (!$updated) {
                    throw new \Exception('Erro ao atualizar estoque');
                }
                
                return ['new_stock' => $newStock, 'status' => 'updated_cloud'];
            },
            
            // Fallback: atualizar localmente
            function($error) use ($productId, $quantity, $operation) {
                $localDb = Database::connect('local_backup');
                
                // Buscar produto local
                $product = $localDb->table('produtos')->where('id_produto', $productId)->get()->getRowArray();
                
                if (!$product) {
                    throw new \Exception('Produto não encontrado no banco local');
                }
                
                $newStock = $operation === 'add' 
                    ? $product['estoque'] + $quantity 
                    : $product['estoque'] - $quantity;
                
                // Permitir estoque negativo em modo offline (será validado na sincronização)
                $localDb->table('produtos')->where('id_produto', $productId)->update([
                    'estoque' => $newStock,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'sync_status' => 'pending'
                ]);
                
                // Registrar no outbox
                Outbox::record('produtos', ['id_produto' => $productId], 'update', [
                    'id_produto' => $productId,
                    'estoque' => $newStock,
                    'operation' => $operation,
                    'quantity' => $quantity
                ]);
                
                return ['new_stock' => $newStock, 'status' => 'updated_local', 'needs_sync' => true];
            },
            
            ['operation' => 'update_stock', 'product_id' => $productId]
        );
    }
    
    /**
     * Fallback para processamento de pagamento
     */
    public static function processPaymentFallback(array $paymentData): array
    {
        return self::execute(
            // Operação principal: processar online
            function() use ($paymentData) {
                if (function_exists('is_offline_mode') && is_offline_mode()) {
                    throw new \Exception('Sistema offline');
                }
                
                // Simular processamento de pagamento online
                if ($paymentData['method'] === 'credit_card') {
                    // Integração com gateway de pagamento
                    $gateway = new \App\Libraries\PaymentGateway();
                    return $gateway->process($paymentData);
                }
                
                return ['status' => 'approved', 'transaction_id' => uniqid('tx_')];
            },
            
            // Fallback: registrar para processamento posterior
            function($error) use ($paymentData) {
                // Pagamentos offline só para dinheiro e PIX
                $allowedOfflineMethods = ['cash', 'pix'];
                
                if (!in_array($paymentData['method'], $allowedOfflineMethods)) {
                    throw new \Exception('Método de pagamento não suportado offline');
                }
                
                $localDb = Database::connect('local_backup');
                
                $offlinePayment = [
                    'sale_id' => $paymentData['sale_id'],
                    'method' => $paymentData['method'],
                    'amount' => $paymentData['amount'],
                    'status' => 'pending_sync',
                    'created_at' => date('Y-m-d H:i:s'),
                    'offline_transaction_id' => uniqid('offline_')
                ];
                
                $paymentId = $localDb->table('offline_payments')->insert($offlinePayment, true);
                
                // Registrar para sincronização
                Outbox::record('payments', ['id' => $paymentId], 'insert', $offlinePayment);
                
                return [
                    'status' => 'pending_sync',
                    'offline_transaction_id' => $offlinePayment['offline_transaction_id'],
                    'needs_sync' => true
                ];
            },
            
            ['operation' => 'process_payment', 'method' => $paymentData['method']]
        );
    }
    
    /**
     * Registra operação para sincronização posterior
     */
    private static function registerForLaterSync(array $context, $result, string $operationId): void
    {
        try {
            $db = Database::connect();
            
            $syncRecord = [
                'operation_id' => $operationId,
                'operation_type' => $context['operation'] ?? 'unknown',
                'context' => json_encode($context),
                'result' => json_encode($result),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Criar tabela se não existir
            if (!$db->tableExists('fallback_sync_queue')) {
                $forge = Database::forge();
                $forge->addField([
                    'id' => ['type' => 'INT', 'auto_increment' => true],
                    'operation_id' => ['type' => 'VARCHAR', 'constraint' => 50],
                    'operation_type' => ['type' => 'VARCHAR', 'constraint' => 100],
                    'context' => ['type' => 'TEXT'],
                    'result' => ['type' => 'TEXT'],
                    'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'synced', 'failed']],
                    'created_at' => ['type' => 'DATETIME'],
                    'synced_at' => ['type' => 'DATETIME', 'null' => true]
                ]);
                $forge->addKey('id', true);
                $forge->createTable('fallback_sync_queue');
            }
            
            $db->table('fallback_sync_queue')->insert($syncRecord);
            
        } catch (\Throwable $e) {
            log_message('error', '[CriticalFallback] Erro ao registrar para sync', [
                'operation_id' => $operationId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Resolve tenant IDs
     */
    private static function resolveTenantIds(array $data): array
    {
        $idContador = (int) ($data['id_contador'] ?? 0);
        $idEmpresa  = (int) ($data['id_empresa'] ?? 0);
        
        if ($idContador === 0 || $idEmpresa === 0) {
            if (function_exists('resolve_tenant_ids')) {
                return resolve_tenant_ids();
            }
            
            $session = session();
            return [
                (int) ($session->get('id_contador') ?? 0),
                (int) ($session->get('id_empresa') ?? 0)
            ];
        }
        
        return [$idContador, $idEmpresa];
    }
}
