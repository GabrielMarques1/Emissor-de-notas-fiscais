<?php

namespace App\Models;

class EmpresaModel extends BaseAppModel
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
        'id_contador',
        'valor_mensalidade',
        'data_bloqueio',
        'motivo_bloqueio',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'stripe_product_id',
        'stripe_status',
        'trial_ends_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
