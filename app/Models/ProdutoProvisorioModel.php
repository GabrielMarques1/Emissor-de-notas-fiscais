<?php

namespace App\Models;

class ProdutoProvisorioModel extends BaseAppModel
{
    protected $table = 'produtos_provisorios';
    protected $primaryKey = 'id_produto_provisorio';
    protected $allowedFields = [
        'id_produto_provisorio',
        'id_produto',
        'nome',
        'codigo_de_barras',
        'unidade',
        'quantidade',
        'valor_unitario',
        'desconto',
        'observacao',
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
