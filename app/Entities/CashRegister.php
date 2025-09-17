<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CashRegister extends Entity
{
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'id_cash_register' => 'integer',
        'id_contador' => 'integer',
        'id_empresa' => 'integer'
    ];
}


