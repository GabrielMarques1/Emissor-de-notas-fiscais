<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterEmpresasCobranca extends Migration
{
	public function up()
	{
		// Adiciona colunas para cobrança recorrente
		$fields = [
			'valor_mensalidade' => [
				'type'       => 'DOUBLE',
				'null'       => TRUE,
				'default'    => 0.00
			],
			'data_bloqueio' => [
				'type'       => 'DATE',
				'null'       => TRUE
			],
			'motivo_bloqueio' => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
				'null'       => TRUE
			]
		];

		$this->forge->addColumn('empresas', $fields);
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropColumn('empresas', 'valor_mensalidade');
		$this->forge->dropColumn('empresas', 'data_bloqueio');
		$this->forge->dropColumn('empresas', 'motivo_bloqueio');
	}
}


