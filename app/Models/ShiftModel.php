<?php

namespace App\Models;

use CodeIgniter\Model;

class ShiftModel extends BaseAppModel
{
    // protected $enforceTenant = true; // Herdado do BaseAppModel
    protected $table = 'shifts';
    protected $primaryKey = 'id_shift';
    protected $returnType = 'App\\Entities\\Shift';
    protected $allowedFields = [
        'id_shift',
        'id_cash_register',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'opening_amount',
        'closing_amount',
        'status', // open|closed
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
        'id_cash_register' => 'required|is_natural_no_zero',
        'status' => 'required|in_list[open,closed]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}


