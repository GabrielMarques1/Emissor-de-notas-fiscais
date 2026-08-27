<?php

namespace App\Models;

class DiscountModel extends BaseAppModel
{
    protected $table = 'discounts';
    protected $primaryKey = 'id_discount';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_pos_sale', 'id_pos_sale_item', 'id_coupon', 'type', 'value',
        'amount', 'applied_by', 'reason', 'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null; // Sem updated_at (auditoria não muda)
    
    protected $validationRules = [
        'id_pos_sale' => 'required|is_natural_no_zero',
        'type' => 'required|in_list[percentage,fixed,coupon]',
        'value' => 'required|decimal|greater_than[0]',
        'amount' => 'required|decimal|greater_than[0]',
        'applied_by' => 'required|is_natural_no_zero',
    ];
    
    /**
     * Buscar descontos por venda
     */
    public function getBySale(int $idSale): array
    {
        return $this->where('id_pos_sale', $idSale)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Calcular total descontado em uma venda
     */
    public function getTotalBySale(int $idSale): float
    {
        $result = $this->selectSum('amount', 'total')
                       ->where('id_pos_sale', $idSale)
                       ->first();
        
        return (float) ($result['total'] ?? 0.00);
    }
    
    /**
     * Estatísticas de descontos por período
     */
    public function getStatsByPeriod(string $dateFrom, string $dateTo): array
    {
        $builder = $this->builder();
        
        return $builder->select('
                type,
                COUNT(*) as total_discounts,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MAX(amount) as max_amount
            ')
            ->where('created_at >=', $dateFrom)
            ->where('created_at <=', $dateTo)
            ->groupBy('type')
            ->orderBy('total_amount', 'DESC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Descontos por operador
     */
    public function getByOperator(int $operatorId, string $dateFrom, string $dateTo): array
    {
        return $this->where('applied_by', $operatorId)
                    ->where('created_at >=', $dateFrom)
                    ->where('created_at <=', $dateTo)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Top cupons mais usados
     */
    public function getTopCoupons(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $builder = $this->db->table($this->table . ' d');
        
        return $builder->select('c.code, c.description, COUNT(*) as usage_count, SUM(d.amount) as total_discount')
                       ->join('coupons c', 'c.id_coupon = d.id_coupon')
                       ->where('d.created_at >=', $dateFrom)
                       ->where('d.created_at <=', $dateTo)
                       ->where('d.type', 'coupon')
                       ->groupBy(['c.code', 'c.description'])
                       ->orderBy('usage_count', 'DESC')
                       ->limit($limit)
                       ->get()
                       ->getResultArray();
    }
}

