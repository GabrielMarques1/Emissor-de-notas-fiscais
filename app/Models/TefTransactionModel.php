<?php

namespace App\Models;

class TefTransactionModel extends BaseAppModel
{
    protected $table = 'tef_transactions';
    protected $primaryKey = 'id_tef_transaction';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_pos_sale', 'acquirer', 'card_type', 'card_brand', 'card_last4',
        'amount', 'installments', 'authorization_code', 'nsu', 'tid', 'status',
        'acquirer_response_code', 'acquirer_response_message', 'request_payload',
        'response_payload', 'id_contador', 'id_empresa', 'authorized_at',
        'confirmed_at', 'cancelled_at',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'acquirer' => 'required|in_list[cielo,stone,rede,getnet]',
        'card_type' => 'required|in_list[credit,debit]',
        'amount' => 'required|decimal|greater_than[0]',
        'installments' => 'required|integer|greater_than[0]|less_than_equal_to[12]',
    ];
    
    protected $validationMessages = [
        'acquirer' => [
            'in_list' => 'Adquirente inválida. Use: cielo, stone, rede ou getnet',
        ],
        'installments' => [
            'less_than_equal_to' => 'Máximo de 12 parcelas',
        ],
    ];
    
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
     * Buscar por NSU
     */
    public function findByNsu(string $nsu): ?array
    {
        return $this->where('nsu', $nsu)->first();
    }
    
    /**
     * Buscar por código de autorização
     */
    public function findByAuthCode(string $authCode): ?array
    {
        return $this->where('authorization_code', $authCode)->first();
    }
    
    /**
     * Buscar transações pendentes (para retry)
     */
    public function findPending(int $minutes = 30): array
    {
        $timeLimit = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));
        
        return $this->where('status', 'pending')
                    ->where('created_at >', $timeLimit)
                    ->findAll();
    }
    
    /**
     * Estatísticas de transações por período
     */
    public function getStatsByPeriod(string $dateFrom, string $dateTo): array
    {
        $builder = $this->builder();
        
        return $builder->select('
                acquirer,
                card_type,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            ')
            ->where('created_at >=', $dateFrom)
            ->where('created_at <=', $dateTo)
            ->groupBy(['acquirer', 'card_type'])
            ->get()
            ->getResultArray();
    }
}

