<?php

namespace App\Models;

use CodeIgniter\Model;

class FornecedorModel extends Model
{
    protected $table = 'fornecedores';
    protected $primaryKey = 'id_fornecedor';
    protected $allowedFields = [
        'id_fornecedor',
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
        'id_uf',
        'id_municipio',
        'id_contador',
        'id_empresa',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
