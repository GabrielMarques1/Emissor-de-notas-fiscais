<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\PixService;
use App\Models\PixTransactionModel;
use App\Models\PosSaleModel;

class PixWebhook extends ResourceController
{
    protected $format = 'json';
    
    /**
     * Webhook para confirmação de pagamento PIX
     * POST /api/pix/webhook/{id_empresa}
     * 
     * Payload esperado (varia por provedor):
     * {
     *   "txid": "PIX123456789",
     *   "e2e_id": "E2E789012345",
     *   "status": "paid",
     *   "amount": 100.00
     * }
     */
    public function confirm(int $idEmpresa = null): ResponseInterface
    {
        try {
            // Validar que ID da empresa foi fornecido
            if (!$idEmpresa) {
                return $this->fail('ID da empresa é obrigatório', 400);
            }
            
            // Obter payload do webhook
            $payload = $this->request->getJSON(true);
            
            if (empty($payload)) {
                return $this->fail('Payload inválido', 400);
            }
            
            // Extrair dados do pagamento
            $txid = $payload['txid'] ?? $payload['transaction_id'] ?? null;
            $e2eId = $payload['e2e_id'] ?? $payload['end_to_end_id'] ?? null;
            $status = $payload['status'] ?? null;
            
            if (!$txid) {
                return $this->fail('TXID não fornecido', 400);
            }
            
            // Buscar transação PIX
            $pixModel = new PixTransactionModel();
            
            // IMPORTANTE: Não usar tenant awareness aqui pois o webhook vem de fora
            // Mas validar que a empresa do webhook corresponde à transação
            $db = \Config\Database::connect();
            $transaction = $db->table('pix_transactions')
                              ->where('txid', $txid)
                              ->where('id_empresa', $idEmpresa)
                              ->get()
                              ->getRowArray();
            
            if (!$transaction) {
                log_message('warning', '[PIX Webhook] Transação não encontrada', [
                    'txid' => $txid,
                    'id_empresa' => $idEmpresa,
                ]);
                
                return $this->fail('Transação não encontrada', 404);
            }
            
            // Validar que está pendente
            if ($transaction['status'] !== 'pending') {
                return $this->respond([
                    'success' => true,
                    'message' => 'Transação já foi processada',
                ], 200);
            }
            
            // Validar status do pagamento
            if ($status !== 'paid' && $status !== 'confirmed') {
                log_message('info', '[PIX Webhook] Status não confirmado', [
                    'txid' => $txid,
                    'status' => $status,
                ]);
                
                return $this->respond([
                    'success' => true,
                    'message' => 'Status registrado mas não confirmado',
                ], 200);
            }
            
            // Confirmar transação
            $db->table('pix_transactions')
               ->where('id_pix_transaction', $transaction['id_pix_transaction'])
               ->update([
                   'status' => 'confirmed',
                   'e2e_id' => $e2eId,
                   'confirmed_at' => date('Y-m-d H:i:s'),
                   'webhook_data' => json_encode($payload),
                   'updated_at' => date('Y-m-d H:i:s'),
               ]);
            
            // Se há venda vinculada, finalizar
            if ($transaction['id_pos_sale']) {
                $saleModel = new PosSaleModel();
                
                // Atualizar status da venda
                // IMPORTANTE: Não usar update() direto pois precisa desativar tenant check
                $db->table('pos_sales')
                   ->where('id_pos_sale', $transaction['id_pos_sale'])
                   ->where('id_empresa', $idEmpresa)
                   ->update([
                       'status' => 'finalized',
                       'finalized_at' => date('Y-m-d H:i:s'),
                       'updated_at' => date('Y-m-d H:i:s'),
                   ]);
                
                log_message('info', '[PIX Webhook] Venda finalizada automaticamente', [
                    'id_sale' => $transaction['id_pos_sale'],
                    'txid' => $txid,
                    'amount' => $transaction['amount'],
                ]);
            }
            
            log_message('info', '[PIX Webhook] Pagamento confirmado', [
                'txid' => $txid,
                'e2e_id' => $e2eId,
                'amount' => $transaction['amount'],
                'id_empresa' => $idEmpresa,
            ]);
            
            return $this->respond([
                'success' => true,
                'message' => 'Pagamento confirmado com sucesso',
            ], 200);
            
        } catch (\Exception $e) {
            log_message('error', '[PIX Webhook] Erro ao processar', [
                'error' => $e->getMessage(),
                'id_empresa' => $idEmpresa,
            ]);
            
            return $this->fail('Erro ao processar webhook: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Endpoint para consultar status de transação PIX
     * GET /api/pix/status/{txid}
     */
    public function status(string $txid = null): ResponseInterface
    {
        if (!$txid) {
            return $this->fail('TXID é obrigatório', 400);
        }
        
        try {
            // Usar sessão para validar tenant
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
            
            if (!$idContador || !$idEmpresa) {
                return $this->fail('Sessão inválida', 401);
            }
            
            // Simular sessão para PixService
            $_SESSION['id_contador'] = $idContador;
            $_SESSION['id_empresa'] = $idEmpresa;
            
            $pixService = new PixService();
            $result = $pixService->checkStatus($txid);
            
            return $this->respond($result, 200);
            
        } catch (\Exception $e) {
            log_message('error', '[PIX Status] Erro', [
                'error' => $e->getMessage(),
                'txid' => $txid,
            ]);
            
            return $this->fail('Erro ao consultar status: ' . $e->getMessage(), 500);
        }
    }
}

