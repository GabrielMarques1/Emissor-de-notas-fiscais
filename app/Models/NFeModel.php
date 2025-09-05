<?php

namespace App\Models;

use CodeIgniter\Model;

class NFeModel extends Model
{
    protected $table = 'nfes';
    protected $primaryKey = 'id_nfe';
    protected $allowedFields = [
        'id_nfe',
        'numero',
        'chave',
        'valor_da_nota',
        'data',
        'hora',
        'xml',
        'protocolo',
        'status',
        'tipo',
        'id_contador',
        'id_empresa'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
