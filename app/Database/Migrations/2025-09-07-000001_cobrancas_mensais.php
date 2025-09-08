<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CobrancasMensais extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id_cobranca' => [
				'type'           => 'INT',
				'constraint'     => 9,
				'unsigned'       => TRUE,
				'auto_increment' => TRUE
			],

			'mes_referencia' => [
				'type'       => 'INT',
				'constraint' => 2
			],

			'ano_referencia' => [
				'type'       => 'INT',
				'constraint' => 4
			],

			'data_vencimento' => [
				'type' => 'DATE'
			],

			'valor_cobranca' => [
				'type' => 'DOUBLE'
			],

			'status' => [
				'type'       => 'ENUM',
				'constraint' => ['Pendente', 'Pago', 'Vencido', 'Cancelado'],
				'default'    => 'Pendente'
			],

			'data_pagamento' => [
				'type' => 'DATE',
				'null' => TRUE
			],

			'observacoes' => [
				'type'       => 'TEXT',
				'null'       => TRUE
			],

			'id_contador' => [
				'type' => 'INT'
			],

			'id_empresa' => [
				'type' => 'INT'
			],

			'created_at' => [
				'type' => 'DATETIME'
			],

			'updated_at' => [
				'type' => 'DATETIME'
			],

			'deleted_at' => [
				'type' => 'DATETIME',
				'null' => TRUE
			]
		]);

		$this->forge->addKey('id_cobranca', TRUE);
		$this->forge->addKey(['id_empresa', 'mes_referencia', 'ano_referencia']);
		$this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
		$this->forge->createTable('cobrancas_mensais');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('cobrancas_mensais');
	}
}


