<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PosSale extends Entity
{
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'id_pos_sale' => 'integer',
        'id_shift' => 'integer',
        'id_cash_register' => 'integer',
        'total' => 'float',
        'discount' => 'float',
        'paid_amount' => 'float',
        'change_amount' => 'float',
        'id_contador' => 'integer',
        'id_empresa' => 'integer'
    ];
}


