<?php

namespace App\Libraries;

use App\Models\TefTransactionModel;
use App\Traits\TenantAwareTrait;
use App\Libraries\TefAdapters\AcquirerAdapterInterface;

class TefService
{
    use TenantAwareTrait;
    
    protected TefTransactionModel $tefModel;
    protected ?AcquirerAdapterInterface $adapter = null;
    
    /**
     * Configurações do tenant
     */
    protected string $acquirer = 'cielo';
    protected string $merchantId = '';
    protected string $merchantKey = '';
    protected string $environment = 'sandbox';
    protected int $timeout = 30;
    protected int $maxInstallments = 12;
    
    public function __construct()
    {
        $this->tefModel = new TefTransactionModel();
        $this->loadTenantConfig();
        $this->initializeAdapter();
    }
    
    /**
     * Carregar configurações TEF do tenant
     */
    protected function loadTenantConfig(): void
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $db = \Config\Database::connect();
        $config = $db->table('empresas')
                     ->where('id_contador', $idContador)
                     ->where('id_empresa', $idEmpresa)
                     ->get()
                     ->getRowArray();
        
        if ($config) {
            $this->acquirer = $config['tef_acquirer'] ?? 'cielo';
            $this->merchantId = $config['tef_merchant_id'] ?? '';
            $this->merchantKey = $config['tef_merchant_key'] ?? '';
            $this->environment = $config['tef_environment'] ?? 'sandbox';
            $this->timeout = (int) ($config['tef_timeout'] ?? 30);
            $this->maxInstallments = (int) ($config['tef_max_installments'] ?? 12);
        }
    }
    
    /**
     * Inicializar adapter da adquirente
     */
    protected function initializeAdapter(): void
    {
        $adapterClass = "App\\Libraries\\TefAdapters\\" . ucfirst($this->acquirer) . "Adapter";
        
        if (!class_exists($adapterClass)) {
            throw new \RuntimeException("Adapter não encontrado para adquirente: {$this->acquirer}");
        }
        
        $this->adapter = new $adapterClass();
        
        $this->adapter->configure([
            'merchant_id' => $this->merchantId,
            'merchant_key' => $this->merchantKey,
            'environment' => $this->environment,
            'timeout' => $this->timeout,
        ]);
    }
    
    /**
     * Autorizar transação TEF
     * 
     * @param array $data [
     *   'amount' => 100.00,
     *   'card_type' => 'credit|debit',
     *   'installments' => 1,
     *   'card_data' => [...],
     * ]
     * @return array ['success' => bool, 'transaction' => [...], 'error' => string]
     */
    public function authorize(array $data): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Validações
        if (empty($data['amount']) || $data['amount'] <= 0) {
            return ['success' => false, 'error' => 'Valor inválido'];
        }
        
        if (empty($data['card_type']) || !in_array($data['card_type'], ['credit', 'debit'])) {
            return ['success' => false, 'error' => 'Tipo de cartão inválido'];
        }
        
        $installments = (int) ($data['installments'] ?? 1);
        
        if ($installments > $this->maxInstallments) {
            return ['success' => false, 'error' => "Máximo de {$this->maxInstallments} parcelas"];
        }
        
        if ($data['card_type'] === 'debit' && $installments > 1) {
            return ['success' => false, 'error' => 'Débito não pode ser parcelado'];
        }
        
        try {
            // Registrar transação como pending
            $transactionData = [
                'acquirer' => $this->acquirer,
                'card_type' => $data['card_type'],
                'amount' => $data['amount'],
                'installments' => $installments,
                'status' => 'pending',
                'request_payload' => json_encode($data),
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa,
            ];
            
            $idTransaction = $this->tefModel->insert($transactionData);
            
            // Chamar adapter da adquirente
            $result = $this->adapter->authorize($data);
            
            // Atualizar transação com resultado
            $updateData = [
                'status' => $result['success'] ? 'authorized' : 'failed',
                'authorization_code' => $result['authorization_code'] ?? null,
                'nsu' => $result['nsu'] ?? null,
                'tid' => $result['tid'] ?? null,
                'card_brand' => $result['card_brand'] ?? null,
                'card_last4' => $result['card_last4'] ?? null,
                'acquirer_response_code' => $result['response_code'] ?? null,
                'acquirer_response_message' => $result['response_message'] ?? null,
                'response_payload' => json_encode($result),
                'authorized_at' => $result['success'] ? date('Y-m-d H:i:s') : null,
            ];
            
            $this->tefModel->update($idTransaction, $updateData);
            
            log_message('info', '[TEF] Autorização processada', [
                'id_transaction' => $idTransaction,
                'acquirer' => $this->acquirer,
                'amount' => $data['amount'],
                'success' => $result['success'],
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            // Buscar transação atualizada
            $transaction = $this->tefModel->find($idTransaction);
            
            return [
                'success' => $result['success'],
                'transaction' => $transaction,
                'error' => $result['error'] ?? null,
            ];
            
        } catch (\Exception $e) {
            log_message('error', '[TEF] Erro na autorização', [
                'error' => $e->getMessage(),
                'acquirer' => $this->acquirer,
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao processar transação: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Confirmar transação (captura)
     */
    public function confirm(int $idTransaction): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar transação
        $transaction = $this->tefModel->find($idTransaction);
        
        if (!$transaction || !$this->validateTenantOwnership($transaction, $idContador, $idEmpresa)) {
            return ['success' => false, 'error' => 'Transação não encontrada'];
        }
        
        if ($transaction['status'] !== 'authorized') {
            return ['success' => false, 'error' => 'Transação não está autorizada'];
        }
        
        try {
            // Chamar adapter
            $result = $this->adapter->confirm(
                $transaction['authorization_code'],
                $transaction['amount']
            );
            
            // Atualizar status
            $this->tefModel->update($idTransaction, [
                'status' => $result['success'] ? 'confirmed' : 'failed',
                'confirmed_at' => $result['success'] ? date('Y-m-d H:i:s') : null,
                'response_payload' => json_encode($result),
            ]);
            
            log_message('info', '[TEF] Confirmação processada', [
                'id_transaction' => $idTransaction,
                'success' => $result['success'],
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            return [
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
            ];
            
        } catch (\Exception $e) {
            log_message('error', '[TEF] Erro na confirmação', [
                'error' => $e->getMessage(),
                'id_transaction' => $idTransaction,
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cancelar transação
     */
    public function cancel(int $idTransaction): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar transação
        $transaction = $this->tefModel->find($idTransaction);
        
        if (!$transaction || !$this->validateTenantOwnership($transaction, $idContador, $idEmpresa)) {
            return ['success' => false, 'error' => 'Transação não encontrada'];
        }
        
        if (!in_array($transaction['status'], ['authorized', 'confirmed'])) {
            return ['success' => false, 'error' => 'Transação não pode ser cancelada'];
        }
        
        try {
            // Chamar adapter
            $result = $this->adapter->cancel(
                $transaction['authorization_code'],
                $transaction['amount']
            );
            
            // Atualizar status
            $this->tefModel->update($idTransaction, [
                'status' => $result['success'] ? 'cancelled' : $transaction['status'],
                'cancelled_at' => $result['success'] ? date('Y-m-d H:i:s') : null,
                'response_payload' => json_encode($result),
            ]);
            
            log_message('info', '[TEF] Cancelamento processado', [
                'id_transaction' => $idTransaction,
                'success' => $result['success'],
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            return [
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
            ];
            
        } catch (\Exception $e) {
            log_message('error', '[TEF] Erro no cancelamento', [
                'error' => $e->getMessage(),
                'id_transaction' => $idTransaction,
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

