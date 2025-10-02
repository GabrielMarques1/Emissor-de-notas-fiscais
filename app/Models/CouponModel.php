<?php

namespace App\Models;

class CouponModel extends BaseAppModel
{
    protected $table = 'coupons';
    protected $primaryKey = 'id_coupon';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    
    protected $allowedFields = [
        'code', 'description', 'type', 'value', 'min_purchase', 'max_discount',
        'usage_limit', 'used_count', 'valid_from', 'valid_until', 'is_active',
        'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
    protected $validationRules = [
        'code' => 'required|max_length[50]|alpha_numeric_punct',
        'type' => 'required|in_list[percentage,fixed,free_shipping]',
        'value' => 'required|decimal|greater_than[0]',
        'min_purchase' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'usage_limit' => 'permit_empty|is_natural',
    ];
    
    /**
     * Buscar cupom por código
     */
    public function findByCode(string $code): ?array
    {
        return $this->where('code', strtoupper($code))
                    ->where('is_active', true)
                    ->first();
    }
    
    /**
     * Validar se cupom é válido
     */
    public function isValid(array $coupon): array
    {
        $now = date('Y-m-d H:i:s');
        
        // Verificar se está ativo
        if (!$coupon['is_active']) {
            return ['valid' => false, 'reason' => 'Cupom inativo'];
        }
        
        // Verificar data de início
        if ($coupon['valid_from'] && $coupon['valid_from'] > $now) {
            return ['valid' => false, 'reason' => 'Cupom ainda não começou'];
        }
        
        // Verificar data de fim
        if ($coupon['valid_until'] && $coupon['valid_until'] < $now) {
            return ['valid' => false, 'reason' => 'Cupom expirado'];
        }
        
        // Verificar limite de uso
        if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ['valid' => false, 'reason' => 'Limite de usos atingido'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Incrementar contador de uso
     */
    public function incrementUsage(int $idCoupon): bool
    {
        $coupon = $this->find($idCoupon);
        
        if (!$coupon) {
            return false;
        }
        
        return $this->update($idCoupon, [
            'used_count' => ((int) $coupon['used_count']) + 1,
        ]);
    }
    
    /**
     * Listar cupons ativos
     */
    public function getActive(): array
    {
        $now = date('Y-m-d H:i:s');
        
        return $this->where('is_active', true)
                    ->groupStart()
                        ->where('valid_from IS NULL')
                        ->orWhere('valid_from <=', $now)
                    ->groupEnd()
                    ->groupStart()
                        ->where('valid_until IS NULL')
                        ->orWhere('valid_until >=', $now)
                    ->groupEnd()
                    ->findAll();
    }
}

