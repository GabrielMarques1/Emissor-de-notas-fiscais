<?php

namespace App\Models;

use CodeIgniter\Model;

class ConfiguracaoModel extends Model
{
    protected $table = 'configuracoes';
    protected $primaryKey = 'id_config';
    protected $allowedFields = [
        'id_config',
        'nome_do_app',
        'mensagem_suporte',
        'contato_suporte',
        'contato_suporte_formatado',
        'outras_opcoes_de_pagamento',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
