<?php

namespace App\Models;

class ReturnModel extends BaseAppModel
{
    protected $table = 'returns';
    protected $primaryKey = 'id_return';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_original_sale', 'id_new_sale', 'type', 'reason', 'total_returned',
        'refund_method', 'refund_status', 'processed_by', 'approved_by',
        'notes', 'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'id_original_sale' => 'required|is_natural_no_zero',
        'type' => 'required|in_list[full_return,partial_return,exchange]',
        'reason' => 'required|min_length[5]|max_length[255]',
        'total_returned' => 'required|decimal|greater_than[0]',
        'refund_method' => 'required|in_list[same_method,cash,credit,voucher]',
        'processed_by' => 'required|is_natural_no_zero',
    ];
    
    /**
     * Buscar devoluções por venda original
     */
    public function getBySale(int $idSale): array
    {
        return $this->where('id_original_sale', $idSale)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Buscar devoluções pendentes de estorno
     */
    public function getPendingRefunds(): array
    {
        return $this->where('refund_status', 'pending')
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Estatísticas de devoluções por período
     */
    public function getStatsByPeriod(string $dateFrom, string $dateTo): array
    {
        $builder = $this->builder();
        
        return $builder->select('
                type,
                COUNT(*) as total_returns,
                SUM(total_returned) as total_amount,
                AVG(total_returned) as avg_amount
            ')
            ->where('created_at >=', $dateFrom)
            ->where('created_at <=', $dateTo)
            ->groupBy('type')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Taxa de devolução (devoluções / vendas)
     */
    public function getReturnRate(string $dateFrom, string $dateTo): float
    {
        $totalSales = $this->db->table('pos_sales')
                               ->where('status', 'finalized')
                               ->where('created_at >=', $dateFrom)
                               ->where('created_at <=', $dateTo)
                               ->countAllResults();
        
        if ($totalSales === 0) {
            return 0.0;
        }
        
        $totalReturns = $this->where('created_at >=', $dateFrom)
                             ->where('created_at <=', $dateTo)
                             ->countAllResults();
        
        return ($totalReturns / $totalSales) * 100;
    }
}

