<?php

namespace App\Models;

use CodeIgniter\Model;

class EmpresaModel extends Model
{
    protected $table = 'empresas';
    protected $primaryKey = 'id_empresa';
    protected $allowedFields = [
        'id_empresa',
        'status',
        'CNPJ',
        'xNome',
        'xFant',
        'IE',
        'dia_do_pagamento',
        'CRT',
        'CEP',
        'xLgr',
        'nro',
        'xCpl',
        'xBairro',
        'fone',
        'natOp',
        'serie',
        'verProc',
        'nNF_homologacao',
        'nNF_producao',
        'tpAmb_NFe',
        'nNFC_homologacao',
        'nNFC_producao',
        'tpAmb_NFCe',
        'CSC_Id',
        'CSC',
        'certificado',
        'senha_do_certificado',
        'id_uf',
        'id_municipio',
        'id_login',
        'id_contador'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
