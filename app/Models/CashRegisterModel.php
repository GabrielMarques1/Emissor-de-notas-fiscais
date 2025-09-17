<?php

namespace App\Models;

use CodeIgniter\Model;

class CashRegisterModel extends Model
{
    protected $table = 'cash_registers';
    protected $primaryKey = 'id_cash_register';
    protected $returnType = 'App\\Entities\\CashRegister';
    protected $allowedFields = [
        'id_cash_register',
        'name',
        'location',
        'status', // open|closed|disabled
        'id_contador',
        'id_empresa',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[128]',
        'status' => 'required|in_list[open,closed,disabled]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}


