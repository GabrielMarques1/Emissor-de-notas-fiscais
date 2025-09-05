<?php

namespace App\Models;

use CodeIgniter\Model;

class ProdutoProvisorioModel extends Model
{
    protected $table = 'produtos_provisorios';
    protected $primaryKey = 'id_produto_provisorio';
    protected $allowedFields = [
        'id_produto_provisorio',
        'nome',
        'codigo_de_barras',
        'unidade',
        'quantidade',
        'valor_unitario',
        'desconto',
        'CFOP_NFe',
        'CFOP_NFCe',
        'CFOP_Externo',
        'NCM',
        'CSOSN',
        'id_produto',
        'id_contador',
        'id_empresa'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
