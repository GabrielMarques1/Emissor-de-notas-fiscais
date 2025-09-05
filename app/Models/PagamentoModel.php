<?php

namespace App\Models;

use CodeIgniter\Model;

class PagamentoModel extends Model
{
    protected $table = 'pagamentos_da_empresa';
    protected $primaryKey = 'id_pagamento';
    protected $allowedFields = [
        'id_pagamento',
        'data_do_pagamento',
        'valor',
        'observacoes',
        'id_contador',
        'id_empresa',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
