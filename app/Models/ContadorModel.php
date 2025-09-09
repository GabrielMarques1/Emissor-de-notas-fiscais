<?php

namespace App\Models;

class ContadorModel extends BaseAppModel
{
    protected $table = 'contadores';
    protected $primaryKey = 'id_contador';
    protected $allowedFields = [
        'id_contador',
        'status',
        'nome',
        'cnpj',
        'razao_social',
        'nome_fantasia',
        'ie',
        'dia_do_pagamento',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cep',
        'id_uf',
        'id_municipio',
        'fixo',
        'celular_1',
        'celular_2',
        'email',
        'id_login',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
