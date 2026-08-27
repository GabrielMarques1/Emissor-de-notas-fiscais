<?php

namespace App\Models;

class PosSalePaymentModel extends BaseAppModel
{
    protected $table = 'pos_sale_payments';
    protected $primaryKey = 'id_payment';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_pos_sale', 'payment_type', 'amount', 'installments',
        'id_tef_transaction', 'id_pix_transaction', 'change_amount',
        'status', 'confirmed_at', 'metadata', 'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'id_pos_sale' => 'required|is_natural_no_zero',
        'payment_type' => 'required|in_list[cash,credit,debit,pix,voucher,check]',
        'amount' => 'required|decimal|greater_than[0]',
        'installments' => 'permit_empty|is_natural_no_zero|less_than_equal_to[99]',
    ];
    
    protected $validationMessages = [
        'payment_type' => [
            'in_list' => 'Tipo de pagamento inválido',
        ],
    ];
    
    /**
     * Buscar pagamentos por venda
     */
    public function getBySale(int $idSale): array
    {
        return $this->where('id_pos_sale', $idSale)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Calcular total pago em uma venda
     */
    public function getTotalBySale(int $idSale): float
    {
        $result = $this->selectSum('amount', 'total')
                       ->where('id_pos_sale', $idSale)
                       ->where('status !=', 'failed')
                       ->first();
        
        return (float) ($result['total'] ?? 0.00);
    }
    
    /**
     * Buscar pagamentos confirmados por venda
     */
    public function getConfirmedBySale(int $idSale): array
    {
        return $this->where('id_pos_sale', $idSale)
                    ->where('status', 'confirmed')
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Buscar pagamentos pendentes por venda
     */
    public function getPendingBySale(int $idSale): array
    {
        return $this->where('id_pos_sale', $idSale)
                    ->where('status', 'pending')
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Contar formas de pagamento de uma venda
     */
    public function countPaymentsBySale(int $idSale): int
    {
        return $this->where('id_pos_sale', $idSale)
                    ->countAllResults();
    }
    
    /**
     * Estatísticas de formas de pagamento por período
     */
    public function getStatsByPeriod(string $dateFrom, string $dateTo): array
    {
        $builder = $this->builder();
        
        return $builder->select('
                payment_type,
                COUNT(*) as total_transactions,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->where('created_at >=', $dateFrom)
            ->where('created_at <=', $dateTo)
            ->groupBy('payment_type')
            ->orderBy('total_amount', 'DESC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Buscar vendas com múltiplas formas de pagamento
     */
    public function getMultiPaymentSales(string $dateFrom, string $dateTo): array
    {
        $builder = $this->db->table($this->table);
        
        return $builder->select('id_pos_sale, COUNT(*) as payment_count, SUM(amount) as total_paid')
                       ->where('created_at >=', $dateFrom)
                       ->where('created_at <=', $dateTo)
                       ->groupBy('id_pos_sale')
                       ->having('payment_count >', 1)
                       ->get()
                       ->getResultArray();
    }
}

