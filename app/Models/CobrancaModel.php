<?php

namespace App\Models;

class CobrancaModel extends BaseAppModel
{
	protected $table = 'cobrancas_mensais';
	protected $primaryKey = 'id_cobranca';
	protected $allowedFields = [
		'id_cobranca',
		'mes_referencia',
		'ano_referencia',
		'data_vencimento',
		'valor_cobranca',
		'status',
		'data_pagamento',
		'observacoes',
		'id_contador',
		'id_empresa',
	];
	protected $useTimestamps = true;
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';
	protected $deletedField = 'deleted_at';
}


