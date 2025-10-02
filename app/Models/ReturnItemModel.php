<?php

namespace App\Models;

class ReturnItemModel extends BaseAppModel
{
    protected $table = 'return_items';
    protected $primaryKey = 'id_return_item';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_return', 'id_original_item', 'id_produto', 'quantity',
        'unit_price', 'total_price', 'condition', 'restock',
        'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;
    
    protected $validationRules = [
        'id_return' => 'required|is_natural_no_zero',
        'id_original_item' => 'required|is_natural_no_zero',
        'id_produto' => 'required|is_natural_no_zero',
        'quantity' => 'required|is_natural_no_zero',
        'unit_price' => 'required|decimal|greater_than[0]',
        'total_price' => 'required|decimal|greater_than[0]',
        'condition' => 'required|in_list[perfect,good,damaged,defective]',
    ];
    
    /**
     * Buscar itens por devolução
     */
    public function getByReturn(int $idReturn): array
    {
        return $this->where('id_return', $idReturn)
                    ->findAll();
    }
    
    /**
     * Buscar itens para reposição de estoque
     */
    public function getForRestock(int $idReturn): array
    {
        return $this->where('id_return', $idReturn)
                    ->where('restock', true)
                    ->findAll();
    }
}

