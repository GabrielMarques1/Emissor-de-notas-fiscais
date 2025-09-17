<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Shift extends Entity
{
    protected $dates = ['opened_at', 'closed_at', 'created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'id_shift' => 'integer',
        'id_cash_register' => 'integer',
        'opening_amount' => 'float',
        'closing_amount' => 'float',
        'id_contador' => 'integer',
        'id_empresa' => 'integer'
    ];
}


