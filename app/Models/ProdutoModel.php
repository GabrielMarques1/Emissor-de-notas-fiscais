<?php

namespace App\Models;

use CodeIgniter\Model;

class ProdutoModel extends Model
{
    protected $table = 'produtos';
    protected $primaryKey = 'id_produto';
    protected $allowedFields = [
        'id_produto',
        'nome',
        'codigo_de_barras',
        'valor_unitario',
        'CFOP_NFe',
        'CFOP_NFCe',
        'CFOP_Externo',
        'NCM',
        'CSOSN',
        'id_unidade',
        'id_contador',
        'id_empresa'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
