<?php

namespace App\Libraries;

use App\Models\CouponModel;
use App\Models\DiscountModel;
use App\Models\PosSaleModel;
use App\Traits\TenantAwareTrait;

class DiscountService
{
    use TenantAwareTrait;
    
    protected CouponModel $couponModel;
    protected DiscountModel $discountModel;
    protected PosSaleModel $saleModel;
    
    public function __construct()
    {
        $this->couponModel = new CouponModel();
        $this->discountModel = new DiscountModel();
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * Aplicar desconto manual em venda
     * 
     * @param int $idSale
     * @param array $discountData ['type' => 'percentage|fixed', 'value' => 10.00, 'reason' => '...']
     * @return array ['success' => bool, 'discount_amount' => float, 'new_total' => float]
     */
    public function applyDiscount(int $idSale, array $discountData): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar venda
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale || !$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Venda não encontrada');
        }
        
        // Validar status
        if ($sale['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Apenas vendas pendentes podem receber desconto'];
        }
        
        // Validar dados
        if (empty($discountData['type']) || empty($discountData['value'])) {
            return ['success' => false, 'error' => 'Tipo e valor do desconto são obrigatórios'];
        }
        
        $type = $discountData['type'];
        $value = (float) $discountData['value'];
        $reason = (string) ($discountData['reason'] ?? 'Desconto manual');
        
        // Calcular desconto em R$
        $saleTotal = (float) $sale['total'];
        $discountAmount = 0.00;
        
        if ($type === 'percentage') {
            $discountAmount = ($saleTotal * $value) / 100;
        } elseif ($type === 'fixed') {
            $discountAmount = $value;
        } else {
            return ['success' => false, 'error' => 'Tipo de desconto inválido'];
        }
        
        // Validar que desconto não excede total
        if ($discountAmount > $saleTotal) {
            return ['success' => false, 'error' => 'Desconto não pode ser maior que o total da venda'];
        }
        
        // Obter operador
        $operatorId = (int) (session()->get('id_login') ?? $_SESSION['id_login'] ?? 0);
        
        // NOVO: Validar limite do operador (por perfil)
        $operatorValidation = $this->validateOperatorLimits($operatorId, $type, $value, $discountAmount);
        
        if (!$operatorValidation['valid']) {
            // Verificar se há aprovador
            $approvedBy = isset($discountData['approved_by']) ? (int) $discountData['approved_by'] : null;
            
            if ($approvedBy) {
                // Validar se aprovador tem permissão
                $approverValidation = $this->validateApprover($approvedBy);
                
                if (!$approverValidation['valid']) {
                    return ['success' => false, 'error' => 'Aprovador não tem permissão para autorizar este desconto'];
                }
            } else {
                return [
                    'success' => false, 
                    'error' => $operatorValidation['reason'],
                    'requires_approval' => true,
                ];
            }
        }
        
        // Validar limite do tenant
        $validation = $this->validateTenantLimits($type, $value, $discountAmount);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['reason']];
        }
        
        // Registrar desconto (auditoria)
        $approvedBy = isset($discountData['approved_by']) ? (int) $discountData['approved_by'] : null;
        
        $this->discountModel->insert([
            'id_pos_sale' => $idSale,
            'type' => $type,
            'value' => $value,
            'amount' => $discountAmount,
            'applied_by' => $operatorId,
            'approved_by' => $approvedBy,
            'reason' => $reason,
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
        ]);
        
        // Atualizar total da venda
        $currentDiscount = (float) ($sale['total_discount'] ?? 0);
        $newTotalDiscount = $currentDiscount + $discountAmount;
        $newTotal = $saleTotal - $newTotalDiscount;
        
        $this->saleModel->update($idSale, [
            'total_discount' => $newTotalDiscount,
            'total' => max($newTotal, 0), // Não pode ser negativo
        ]);
        
        log_message('info', '[Discount] Desconto aplicado', [
            'id_sale' => $idSale,
            'type' => $type,
            'value' => $value,
            'amount' => $discountAmount,
            'operator' => $operatorId,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return [
            'success' => true,
            'discount_amount' => $discountAmount,
            'new_total' => max($newTotal, 0),
        ];
    }
    
    /**
     * Aplicar cupom de desconto
     * 
     * @param int $idSale
     * @param string $couponCode
     * @return array
     */
    public function applyCoupon(int $idSale, string $couponCode): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar venda
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale || !$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Venda não encontrada');
        }
        
        // Buscar cupom
        $coupon = $this->couponModel->findByCode($couponCode);
        
        if (!$coupon || !$this->validateTenantOwnership($coupon, $idContador, $idEmpresa)) {
            return ['success' => false, 'error' => 'Cupom inválido ou não pertence a este estabelecimento'];
        }
        
        // Validar cupom
        $validation = $this->couponModel->isValid($coupon);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['reason']];
        }
        
        // Validar compra mínima
        $saleTotal = (float) $sale['total'];
        
        if ($coupon['min_purchase'] && $saleTotal < $coupon['min_purchase']) {
            return [
                'success' => false,
                'error' => sprintf('Compra mínima de R$ %.2f necessária para usar este cupom', $coupon['min_purchase']),
            ];
        }
        
        // Calcular desconto
        $discountAmount = 0.00;
        
        if ($coupon['type'] === 'percentage') {
            $discountAmount = ($saleTotal * $coupon['value']) / 100;
            
            // Aplicar desconto máximo se configurado
            if ($coupon['max_discount'] && $discountAmount > $coupon['max_discount']) {
                $discountAmount = (float) $coupon['max_discount'];
            }
        } elseif ($coupon['type'] === 'fixed') {
            $discountAmount = (float) $coupon['value'];
        }
        
        // Validar que desconto não excede total
        if ($discountAmount > $saleTotal) {
            $discountAmount = $saleTotal;
        }
        
        // Obter operador
        $operatorId = (int) (session()->get('id_login') ?? $_SESSION['id_login'] ?? 0);
        
        // Registrar desconto
        $this->discountModel->insert([
            'id_pos_sale' => $idSale,
            'id_coupon' => $coupon['id_coupon'],
            'type' => 'coupon',
            'value' => $coupon['value'],
            'amount' => $discountAmount,
            'applied_by' => $operatorId,
            'reason' => "Cupom: {$couponCode}",
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
        ]);
        
        // Incrementar uso do cupom
        $this->couponModel->incrementUsage($coupon['id_coupon']);
        
        // Atualizar total da venda
        $currentDiscount = (float) ($sale['total_discount'] ?? 0);
        $newTotalDiscount = $currentDiscount + $discountAmount;
        $newTotal = $saleTotal - $newTotalDiscount;
        
        $this->saleModel->update($idSale, [
            'total_discount' => $newTotalDiscount,
            'total' => max($newTotal, 0),
        ]);
        
        log_message('info', '[Discount] Cupom aplicado', [
            'id_sale' => $idSale,
            'code' => $couponCode,
            'amount' => $discountAmount,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return [
            'success' => true,
            'coupon' => $coupon,
            'discount_amount' => $discountAmount,
            'new_total' => max($newTotal, 0),
        ];
    }
    
    /**
     * Validar limites de desconto do tenant
     */
    protected function validateTenantLimits(string $type, float $value, float $amount): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $db = \Config\Database::connect();
        $config = $db->table('empresas')
                     ->where('id_contador', $idContador)
                     ->where('id_empresa', $idEmpresa)
                     ->get()
                     ->getRowArray();
        
        if (!$config) {
            return ['valid' => true]; // Sem configuração = sem limite
        }
        
        // Validar percentual máximo
        if ($type === 'percentage') {
            $maxPercentage = (float) ($config['max_discount_percentage'] ?? 100);
            
            if ($value > $maxPercentage) {
                return [
                    'valid' => false,
                    'reason' => sprintf('Desconto máximo permitido: %.2f%%', $maxPercentage),
                ];
            }
        }
        
        // Validar valor máximo em R$
        if ($config['max_discount_amount']) {
            $maxAmount = (float) $config['max_discount_amount'];
            
            if ($amount > $maxAmount) {
                return [
                    'valid' => false,
                    'reason' => sprintf('Desconto máximo permitido: R$ %.2f', $maxAmount),
                ];
            }
        }
        
        // Verificar se requer aprovação
        if ($config['require_discount_approval']) {
            $threshold = (float) ($config['discount_approval_threshold'] ?? 20);
            
            if ($type === 'percentage' && $value > $threshold) {
                return [
                    'valid' => false,
                    'reason' => sprintf('Desconto acima de %.2f%% requer aprovação de gerente', $threshold),
                ];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validar limites de desconto do operador (por perfil)
     * 
     * @param int $operatorId
     * @param string $type
     * @param float $value
     * @param float $discountAmount
     * @return array
     */
    protected function validateOperatorLimits(int $operatorId, string $type, float $value, float $discountAmount): array
    {
        if (!$operatorId) {
            return ['valid' => false, 'reason' => 'Operador não identificado'];
        }
        
        // Buscar limites do operador
        $db = \Config\Database::connect();
        $operator = $db->table('logins')
                       ->where('id_login', $operatorId)
                       ->get()
                       ->getRowArray();
        
        if (!$operator) {
            return ['valid' => false, 'reason' => 'Operador não encontrado'];
        }
        
        // Validar desconto em percentual
        if ($type === 'percentage') {
            $maxPercentage = (float) ($operator['max_discount_percentage'] ?? 10.00);
            
            if ($value > $maxPercentage) {
                return [
                    'valid' => false,
                    'reason' => sprintf(
                        'Você só pode aplicar até %.0f%% de desconto. Para mais, solicite aprovação do gerente.',
                        $maxPercentage
                    ),
                ];
            }
        }
        
        // Validar desconto em valor fixo
        $maxAmount = isset($operator['max_discount_amount']) && $operator['max_discount_amount'] !== null
                     ? (float) $operator['max_discount_amount']
                     : PHP_FLOAT_MAX;
        
        if ($discountAmount > $maxAmount) {
            return [
                'valid' => false,
                'reason' => sprintf(
                    'Você só pode aplicar até R$ %.2f de desconto. Para mais, solicite aprovação do gerente.',
                    $maxAmount
                ),
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validar se aprovador tem permissão
     * 
     * @param int $approverId
     * @return array
     */
    protected function validateApprover(int $approverId): array
    {
        $db = \Config\Database::connect();
        $approver = $db->table('logins')
                       ->where('id_login', $approverId)
                       ->get()
                       ->getRowArray();
        
        if (!$approver) {
            return ['valid' => false, 'reason' => 'Aprovador não encontrado'];
        }
        
        $canApprove = (bool) ($approver['can_approve_discounts'] ?? false);
        
        if (!$canApprove) {
            return ['valid' => false, 'reason' => 'Este usuário não pode aprovar descontos'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Listar cupons ativos do tenant
     */
    public function getActiveCoupons(): array
    {
        return $this->couponModel->getActive();
    }
    
    /**
     * Estatísticas de descontos
     */
    public function getStats(string $dateFrom, string $dateTo): array
    {
        return $this->discountModel->getStatsByPeriod($dateFrom, $dateTo);
    }
}

