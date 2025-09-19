<?php

namespace App\Models;

use CodeIgniter\Model;

class PosSaleItemModel extends Model
{
    protected $table = 'pos_sale_items';
    protected $primaryKey = 'id_item';
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_item','id_pos_sale','id_produto','nome','codigo_de_barras','unidade','quantidade','valor_unitario','desconto','CFOP_NFe','CFOP_NFCe','CFOP_Externo','NCM','CSOSN'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}


