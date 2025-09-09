<?php

namespace App\Models;

class NFCeModel extends BaseAppModel
{
    protected $table = 'nfces';
    protected $primaryKey = 'id_nfce';
    protected $allowedFields = [
        'id_nfce',
        'numero',
        'chave',
        'valor_da_nota',
        'data',
        'hora',
        'xml',
        'protocolo',
        'status',
        'id_contador',
        'id_empresa',
        'id_cliente'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
