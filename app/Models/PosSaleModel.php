<?php

namespace App\Models;

use CodeIgniter\Model;

class PosSaleModel extends BaseAppModel
{
    protected $table = 'pos_sales';
    protected $primaryKey = 'id_pos_sale';
    protected $returnType = 'App\\Entities\\PosSale';
    protected $allowedFields = [
        'id_pos_sale',
        'id_shift',
        'id_caixa_sessao',
        'id_cash_register',
        'sale_number',
        'total',
        'discount',
        'paid_amount',
        'change_amount',
        'payment_type', // cash|debit|credit|pix|voucher
        'id_cliente',
        'notes',
        'status', // draft|finalized|cancelled
        'id_nfce',
        'chave_nfce',
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
        'id_shift' => 'required|is_natural_no_zero',
        'id_cash_register' => 'required|is_natural_no_zero',
        'sale_number' => 'required|min_length[1]|max_length[32]',
        'status' => 'required|in_list[draft,finalized,cancelled]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}


