<?php

namespace App\Libraries;

use App\Models\PixTransactionModel;
use App\Traits\TenantAwareTrait;

class PixService
{
    use TenantAwareTrait;
    
    protected PixTransactionModel $pixModel;
    
    /**
     * Configurações do tenant
     */
    protected string $provider = 'mercadopago';
    protected string $pixKey = '';
    protected string $accessToken = '';
    protected string $webhookSecret = '';
    protected int $expirationMinutes = 15;
    
    public function __construct()
    {
        $this->pixModel = new PixTransactionModel();
        $this->loadTenantConfig();
    }
    
    /**
     * Carregar configurações PIX do tenant
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
            $this->provider = $config['pix_provider'] ?? 'mercadopago';
            $this->pixKey = $config['pix_key'] ?? '';
            $this->accessToken = $config['pix_access_token'] ?? '';
            $this->webhookSecret = $config['pix_webhook_secret'] ?? '';
            $this->expirationMinutes = (int) ($config['pix_expiration_minutes'] ?? 15);
        }
    }
    
    /**
     * Gerar QR Code PIX
     * 
     * @param array $data ['amount' => 100.00, 'description' => 'Venda #123']
     * @return array ['success' => bool, 'transaction' => [...], 'error' => string]
     */
    public function generate(array $data): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Validações
        if (empty($data['amount']) || $data['amount'] <= 0) {
            return ['success' => false, 'error' => 'Valor inválido'];
        }
        
        $amount = (float) $data['amount'];
        $description = $data['description'] ?? 'Pagamento PDV';
        
        try {
            // Gerar TXID único
            $txid = $this->generateTxid();
            
            // Calcular expiração
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->expirationMinutes} minutes"));
            
            // Registrar transação como pending
            $transactionData = [
                'txid' => $txid,
                'provider' => $this->provider,
                'amount' => $amount,
                'qr_code' => 'pending', // Será atualizado após gerar
                'pix_key' => $this->pixKey,
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'request_payload' => json_encode($data),
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa,
            ];
            
            $idTransaction = $this->pixModel->insert($transactionData);
            
            // Gerar QR Code com provedor
            $qrCodeResult = $this->generateQRCode($txid, $amount, $description);
            
            if (!$qrCodeResult['success']) {
                // Falhou ao gerar QR Code - cancelar transação
                $this->pixModel->update($idTransaction, [
                    'status' => 'cancelled',
                    'cancelled_at' => date('Y-m-d H:i:s'),
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Erro ao gerar QR Code: ' . ($qrCodeResult['error'] ?? 'Desconhecido'),
                ];
            }
            
            // Atualizar transação com QR Code
            $this->pixModel->update($idTransaction, [
                'qr_code' => $qrCodeResult['qr_code'],
                'qr_code_image' => $qrCodeResult['qr_code_image'] ?? null,
                'response_payload' => json_encode($qrCodeResult),
            ]);
            
            log_message('info', '[PIX] QR Code gerado', [
                'id_transaction' => $idTransaction,
                'txid' => $txid,
                'amount' => $amount,
                'expires_at' => $expiresAt,
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            // Buscar transação atualizada
            $transaction = $this->pixModel->find($idTransaction);
            
            return [
                'success' => true,
                'transaction' => $transaction,
                'qr_code' => $qrCodeResult['qr_code'],
                'qr_code_image' => $qrCodeResult['qr_code_image'] ?? null,
                'expires_at' => $expiresAt,
            ];
            
        } catch (\Exception $e) {
            log_message('error', '[PIX] Erro ao gerar QR Code', [
                'error' => $e->getMessage(),
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao gerar PIX: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Confirmar pagamento PIX (via webhook ou polling)
     * 
     * @param string $txid
     * @param string $e2eId End to End ID do pagamento
     * @return array ['success' => bool]
     */
    public function confirm(string $txid, string $e2eId): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar transação
        $transaction = $this->pixModel->findByTxid($txid);
        
        if (!$transaction || !$this->validateTenantOwnership($transaction, $idContador, $idEmpresa)) {
            return ['success' => false, 'error' => 'Transação não encontrada'];
        }
        
        if ($transaction['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Transação não está pendente'];
        }
        
        // Verificar se não expirou
        if (strtotime($transaction['expires_at']) < time()) {
            return ['success' => false, 'error' => 'Transação expirada'];
        }
        
        // Atualizar status
        $this->pixModel->update($transaction['id_pix_transaction'], [
            'status' => 'confirmed',
            'e2e_id' => $e2eId,
            'confirmed_at' => date('Y-m-d H:i:s'),
            'webhook_data' => json_encode(['e2e_id' => $e2eId, 'confirmed_at' => date('c')]),
        ]);
        
        log_message('info', '[PIX] Pagamento confirmado', [
            'txid' => $txid,
            'e2e_id' => $e2eId,
            'amount' => $transaction['amount'],
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return ['success' => true, 'message' => 'Pagamento confirmado'];
    }
    
    /**
     * Consultar status da transação
     */
    public function checkStatus(string $txid): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $transaction = $this->pixModel->findByTxid($txid);
        
        if (!$transaction || !$this->validateTenantOwnership($transaction, $idContador, $idEmpresa)) {
            return ['success' => false, 'error' => 'Transação não encontrada'];
        }
        
        // Se está pendente e expirou, atualizar status
        if ($transaction['status'] === 'pending' && strtotime($transaction['expires_at']) < time()) {
            $this->pixModel->update($transaction['id_pix_transaction'], [
                'status' => 'expired',
            ]);
            
            return [
                'success' => true,
                'status' => 'expired',
                'transaction' => $transaction,
            ];
        }
        
        return [
            'success' => true,
            'status' => $transaction['status'],
            'transaction' => $transaction,
        ];
    }
    
    /**
     * Expirar transações antigas (CRON JOB)
     */
    public function expireOld(): int
    {
        $expired = $this->pixModel->findExpired();
        $count = 0;
        
        foreach ($expired as $transaction) {
            $this->pixModel->update($transaction['id_pix_transaction'], [
                'status' => 'expired',
            ]);
            
            $count++;
            
            log_message('info', '[PIX] Transação expirada automaticamente', [
                'txid' => $transaction['txid'],
                'amount' => $transaction['amount'],
                'tenant' => "{$transaction['id_contador']}:{$transaction['id_empresa']}",
            ]);
        }
        
        return $count;
    }
    
    /**
     * Gerar TXID único conforme padrão BACEN
     */
    protected function generateTxid(): string
    {
        // TXID deve ter entre 26 e 35 caracteres alfanuméricos
        return strtoupper(uniqid('PIX') . bin2hex(random_bytes(8)));
    }
    
    /**
     * Gerar QR Code com provedor configurado
     */
    protected function generateQRCode(string $txid, float $amount, string $description): array
    {
        // Se não tem access token configurado, gerar mock para testes
        if (empty($this->accessToken)) {
            return $this->generateMockQRCode($txid, $amount, $description);
        }
        
        // Integração real com provedor
        switch ($this->provider) {
            case 'mercadopago':
                return $this->generateMercadoPagoQRCode($txid, $amount, $description);
            
            case 'pagseguro':
                return $this->generatePagSeguroQRCode($txid, $amount, $description);
            
            case 'banco':
                return $this->generateBancoQRCode($txid, $amount, $description);
            
            default:
                return ['success' => false, 'error' => 'Provedor não suportado'];
        }
    }
    
    /**
     * Gerar QR Code mock (para testes)
     */
    protected function generateMockQRCode(string $txid, float $amount, string $description): array
    {
        $brCode = "00020126360014BR.GOV.BCB.PIX0114{$this->pixKey}520400005303986540{$amount}5802BR5913{$description}6009SAO PAULO62070503***6304{$txid}";
        
        return [
            'success' => true,
            'qr_code' => $brCode,
            'qr_code_image' => null, // Poderia gerar imagem base64
        ];
    }
    
    /**
     * Gerar QR Code com Mercado Pago
     */
    protected function generateMercadoPagoQRCode(string $txid, float $amount, string $description): array
    {
        // Simulação - integração real seria com API do Mercado Pago
        return $this->generateMockQRCode($txid, $amount, $description);
    }
    
    /**
     * Gerar QR Code com PagSeguro
     */
    protected function generatePagSeguroQRCode(string $txid, float $amount, string $description): array
    {
        // Simulação - integração real seria com API do PagSeguro
        return $this->generateMockQRCode($txid, $amount, $description);
    }
    
    /**
     * Gerar QR Code com Banco
     */
    protected function generateBancoQRCode(string $txid, float $amount, string $description): array
    {
        // Simulação - integração real seria com API do banco
        return $this->generateMockQRCode($txid, $amount, $description);
    }
}

