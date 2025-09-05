<?php

namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';
    protected $allowedFields = [
        'id_cliente',
        'tipo',
        'nome',
        'cpf',
        'cnpj',
        'razao_social',
        'isento',
        'ie',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cep',
        'fone',
        'id_uf',
        'id_municipio',
        'id_contador',
        'id_empresa'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
