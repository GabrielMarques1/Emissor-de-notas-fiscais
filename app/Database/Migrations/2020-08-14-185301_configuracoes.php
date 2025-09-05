<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Configuracoes extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id_config' => [
				'type'           => 'INT',
				'constraint'     => 9,
				'usigned'        => TRUE,
				'auto_increment' => TRUE
			],

			'nome_do_app' => [
				'type' => 'VARCHAR',
				'constraint' => 128
			],

			'mensagem_suporte' => [
				'type' => 'VARCHAR',
				'constraint' => 512
			],

			'contato_suporte' => [
				'type' => 'VARCHAR',
				'constraint' => 32
			],

			'contato_suporte_formatado' => [
				'type' => 'VARCHAR',
				'constraint' => 32
			],

			'outras_opcoes_de_pagamento' => [
				'type' => 'TEXT'
			],

			'created_at' => [
				'type' => 'DATETIME'
			],

			'updated_at' => [
				'type' => 'DATETIME'
			],

			'deleted_at' => [
				'type' => 'DATETIME'
			]
		]);

		$this->forge->addKey('id_config', TRUE);
		$this->forge->createTable('configuracoes');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('configuracoes');
	}
}
