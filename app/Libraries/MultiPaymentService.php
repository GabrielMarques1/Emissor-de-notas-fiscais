<?php

namespace App\Libraries;

use App\Models\PosSalePaymentModel;
use App\Models\PosSaleModel;
use App\Traits\TenantAwareTrait;

class MultiPaymentService
{
    use TenantAwareTrait;
    
    protected PosSalePaymentModel $paymentModel;
    protected PosSaleModel $saleModel;
    
    public function __construct()
    {
        $this->paymentModel = new PosSalePaymentModel();
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * Adicionar uma forma de pagamento à venda
     * 
     * @param int $idSale
     * @param array $paymentData ['type' => 'cash', 'amount' => 100.00, ...]
     * @return array ['success' => bool, 'payment' => [...], 'change' => 0.00]
     */
    public function addPayment(int $idSale, array $paymentData): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Validar que venda existe e pertence ao tenant
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale || !$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Venda não encontrada ou não pertence ao tenant atual');
        }
        
        // Validações
        if (empty($paymentData['type']) || empty($paymentData['amount'])) {
            return ['success' => false, 'error' => 'Tipo e valor são obrigatórios'];
        }
        
        $type = $paymentData['type'];
        $amount = (float) $paymentData['amount'];
        
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Valor deve ser maior que zero'];
        }
        
        // Calcular troco (apenas para dinheiro)
        $change = 0.00;
        $calculateChange = (bool) ($paymentData['calculate_change'] ?? false);
        
        if ($type === 'cash' && $calculateChange) {
            $saleTotal = (float) ($sale['total'] ?? 0);
            $alreadyPaid = $this->paymentModel->getTotalBySale($idSale);
            $remaining = $saleTotal - $alreadyPaid;
            
            if ($amount > $remaining) {
                $change = $amount - $remaining;
                $amount = $remaining; // Ajustar para não pagar a mais
            }
        }
        
        // Preparar dados do pagamento
        $payment = [
            'id_pos_sale' => $idSale,
            'payment_type' => $type,
            'amount' => $amount,
            'installments' => (int) ($paymentData['installments'] ?? 1),
            'change_amount' => $change,
            'status' => 'confirmed', // Por padrão confirmado (TEF/PIX confirmarão via seus serviços)
            'confirmed_at' => date('Y-m-d H:i:s'),
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
        ];
        
        // Se for TEF ou PIX, vincular transações
        if (isset($paymentData['id_tef_transaction'])) {
            $payment['id_tef_transaction'] = (int) $paymentData['id_tef_transaction'];
        }
        
        if (isset($paymentData['id_pix_transaction'])) {
            $payment['id_pix_transaction'] = (int) $paymentData['id_pix_transaction'];
        }
        
        // Metadata opcional
        if (isset($paymentData['metadata'])) {
            $payment['metadata'] = json_encode($paymentData['metadata']);
        }
        
        // Inserir pagamento
        try {
            $idPayment = $this->paymentModel->insert($payment);
            
            if (!$idPayment) {
                return [
                    'success' => false,
                    'error' => 'Erro ao registrar pagamento: ' . implode(', ', $this->paymentModel->errors()),
                ];
            }
            
            // Marcar venda como multi-payment se houver mais de 1 forma
            $paymentCount = $this->paymentModel->countPaymentsBySale($idSale);
            
            if ($paymentCount > 1) {
                $this->saleModel->update($idSale, ['is_multi_payment' => true]);
            }
            
            log_message('info', '[MultiPayment] Pagamento adicionado', [
                'id_sale' => $idSale,
                'id_payment' => $idPayment,
                'type' => $type,
                'amount' => $amount,
                'change' => $change,
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            return [
                'success' => true,
                'payment' => $this->paymentModel->find($idPayment),
                'change' => $change,
            ];
            
        } catch (\Exception $e) {
            log_message('error', '[MultiPayment] Erro ao adicionar pagamento', [
                'error' => $e->getMessage(),
                'id_sale' => $idSale,
            ]);
            
            return [
                'success' => false,
                'error' => 'Erro ao processar pagamento: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Validar se soma dos pagamentos corresponde ao total da venda
     * 
     * @param int|array $sale ID da venda ou objeto da venda
     * @return array ['valid' => bool, 'total' => 0.00, 'paid' => 0.00, 'difference' => 0.00]
     */
    public function validateTotal($sale): array
    {
        if (is_int($sale)) {
            $sale = $this->saleModel->find($sale);
        }
        
        if (!$sale) {
            return ['valid' => false, 'error' => 'Venda não encontrada'];
        }
        
        $saleTotal = (float) ($sale['total'] ?? 0);
        $totalPaid = $this->paymentModel->getTotalBySale($sale['id_pos_sale']);
        
        $difference = round($saleTotal - $totalPaid, 2);
        $valid = abs($difference) < 0.01; // Tolerância de 1 centavo
        
        return [
            'valid' => $valid,
            'total' => $saleTotal,
            'paid' => $totalPaid,
            'difference' => $difference,
        ];
    }
    
    /**
     * Finalizar venda com múltiplas formas de pagamento
     * 
     * @param int|array $sale
     * @return array ['success' => bool]
     */
    public function finalize($sale): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        if (is_int($sale)) {
            $sale = $this->saleModel->find($sale);
        }
        
        if (!$sale || !$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            return ['success' => false, 'error' => 'Venda não encontrada'];
        }
        
        // Validar total
        $validation = $this->validateTotal($sale);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => 'Total de pagamentos não corresponde ao total da venda',
                'validation' => $validation,
            ];
        }
        
        // Atualizar venda
        $idSale = $sale['id_pos_sale'];
        
        $this->saleModel->update($idSale, [
            'status' => 'finalized',
            'total_paid' => $validation['paid'],
            'finalized_at' => date('Y-m-d H:i:s'),
        ]);
        
        log_message('info', '[MultiPayment] Venda finalizada', [
            'id_sale' => $idSale,
            'total' => $validation['total'],
            'paid' => $validation['paid'],
            'payment_count' => $this->paymentModel->countPaymentsBySale($idSale),
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return [
            'success' => true,
            'sale' => $this->saleModel->find($idSale),
            'payments' => $this->paymentModel->getBySale($idSale),
        ];
    }
    
    /**
     * Remover um pagamento (antes de finalizar)
     * 
     * @param int $idPayment
     * @return array ['success' => bool]
     */
    public function removePayment(int $idPayment): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $payment = $this->paymentModel->find($idPayment);
        
        if (!$payment || !$this->validateTenantOwnership($payment, $idContador, $idEmpresa)) {
            return ['success' => false, 'error' => 'Pagamento não encontrado'];
        }
        
        // Verificar se venda ainda está pendente
        $sale = $this->saleModel->find($payment['id_pos_sale']);
        
        if ($sale['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Não é possível remover pagamento de venda finalizada'];
        }
        
        // Remover pagamento
        $this->paymentModel->delete($idPayment);
        
        // Verificar se ainda é multi-payment
        $paymentCount = $this->paymentModel->countPaymentsBySale($payment['id_pos_sale']);
        
        if ($paymentCount <= 1) {
            $this->saleModel->update($payment['id_pos_sale'], ['is_multi_payment' => false]);
        }
        
        log_message('info', '[MultiPayment] Pagamento removido', [
            'id_payment' => $idPayment,
            'id_sale' => $payment['id_pos_sale'],
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return ['success' => true];
    }
    
    /**
     * Obter resumo dos pagamentos de uma venda
     * 
     * @param int $idSale
     * @return array
     */
    public function getSummary(int $idSale): array
    {
        $payments = $this->paymentModel->getBySale($idSale);
        $validation = $this->validateTotal($idSale);
        
        $summary = [
            'total_payments' => count($payments),
            'total_paid' => $validation['paid'],
            'sale_total' => $validation['total'],
            'difference' => $validation['difference'],
            'is_valid' => $validation['valid'],
            'payments_by_type' => [],
            'total_change' => 0.00,
        ];
        
        foreach ($payments as $payment) {
            $type = $payment['payment_type'];
            
            if (!isset($summary['payments_by_type'][$type])) {
                $summary['payments_by_type'][$type] = [
                    'count' => 0,
                    'total' => 0.00,
                ];
            }
            
            $summary['payments_by_type'][$type]['count']++;
            $summary['payments_by_type'][$type]['total'] += (float) $payment['amount'];
            $summary['total_change'] += (float) $payment['change_amount'];
        }
        
        return $summary;
    }
}

