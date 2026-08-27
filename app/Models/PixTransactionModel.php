<?php

namespace App\Models;

class PixTransactionModel extends BaseAppModel
{
    protected $table = 'pix_transactions';
    protected $primaryKey = 'id_pix_transaction';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_pos_sale', 'txid', 'provider', 'amount', 'qr_code', 'qr_code_image',
        'pix_key', 'e2e_id', 'status', 'expires_at', 'confirmed_at', 'cancelled_at',
        'webhook_data', 'request_payload', 'response_payload', 'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'txid' => 'required|min_length[10]|max_length[100]',
        'provider' => 'required|in_list[mercadopago,pagseguro,banco]',
        'amount' => 'required|decimal|greater_than[0]',
        // qr_code não é obrigatório no insert (gerado depois)
        'expires_at' => 'required|valid_date',
    ];
    
    protected $validationMessages = [
        'provider' => [
            'in_list' => 'Provedor inválido. Use: mercadopago, pagseguro ou banco',
        ],
    ];
    
    /**
     * Buscar transação por TXID
     */
    public function findByTxid(string $txid): ?array
    {
        return $this->where('txid', $txid)->first();
    }
    
    /**
     * Buscar transações por venda
     */
    public function getBySale(int $idSale): array
    {
        return $this->where('id_pos_sale', $idSale)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Buscar transações pendentes prestes a expirar
     */
    public function findExpiring(int $minutes = 2): array
    {
        $expireTime = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
        
        return $this->where('status', 'pending')
                    ->where('expires_at <=', $expireTime)
                    ->findAll();
    }
    
    /**
     * Buscar transações expiradas (status pending mas já passou da data)
     */
    public function findExpired(): array
    {
        $now = date('Y-m-d H:i:s');
        
        return $this->where('status', 'pending')
                    ->where('expires_at <', $now)
                    ->findAll();
    }
    
    /**
     * Buscar transações pendentes (aguardando pagamento)
     */
    public function findPending(int $hoursLimit = 24): array
    {
        $timeLimit = date('Y-m-d H:i:s', strtotime("-{$hoursLimit} hours"));
        
        return $this->where('status', 'pending')
                    ->where('created_at >', $timeLimit)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Estatísticas por período
     */
    public function getStatsByPeriod(string $dateFrom, string $dateTo): array
    {
        $builder = $this->builder();
        
        return $builder->select('
                provider,
                status,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            ')
            ->where('created_at >=', $dateFrom)
            ->where('created_at <=', $dateTo)
            ->groupBy(['provider', 'status'])
            ->get()
            ->getResultArray();
    }
    
    /**
     * Taxa de conversão (confirmados / total)
     */
    public function getConversionRate(string $dateFrom, string $dateTo): float
    {
        $total = $this->where('created_at >=', $dateFrom)
                      ->where('created_at <=', $dateTo)
                      ->countAllResults();
        
        if ($total === 0) {
            return 0.0;
        }
        
        $confirmed = $this->where('created_at >=', $dateFrom)
                          ->where('created_at <=', $dateTo)
                          ->where('status', 'confirmed')
                          ->countAllResults();
        
        return ($confirmed / $total) * 100;
    }
}

