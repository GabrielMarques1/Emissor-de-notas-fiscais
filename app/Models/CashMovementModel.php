<?php

namespace App\Models;

class CashMovementModel extends BaseAppModel
{
    protected $table = 'cash_movements';
    protected $primaryKey = 'id_movement';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_movement',
        'id_shift',
        'id_cash_register',
        'type',
        'amount',
        'reason',
        'notes',
        'performed_by',
        'authorized_by',
        'id_contador',
        'id_empresa',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    
    protected $validationRules = [
        'id_shift' => 'required|is_natural_no_zero',
        'id_cash_register' => 'required|is_natural_no_zero',
        'type' => 'required|in_list[withdrawal,supply]',
        'amount' => 'required|decimal|greater_than[0]',
        'reason' => 'required|min_length[3]|max_length[255]',
        'performed_by' => 'required|is_natural_no_zero',
    ];
    
    protected $validationMessages = [
        'amount' => [
            'greater_than' => 'O valor deve ser maior que zero',
        ],
        'reason' => [
            'required' => 'O motivo é obrigatório',
            'min_length' => 'O motivo deve ter no mínimo 3 caracteres',
        ],
    ];
    
    protected $skipValidation = false;
}

