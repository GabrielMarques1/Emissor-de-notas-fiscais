<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Contadores extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id_contador' => [
				'type'           => 'INT',
				'constraint'     => 9,
				'usigned'        => TRUE,
				'auto_increment' => TRUE
			],

			'status' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'nome' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'cnpj' => [
				'type'       => 'VARCHAR',
				'constraint' => 14
			],

			'razao_social' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'nome_fantasia' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'ie' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'dia_do_pagamento' => [
				'type' => 'INT'
			],

			'logradouro' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'numero' => [
				'type'       => 'VARCHAR',
				'constraint' => 9
			],

			'complemento' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'bairro' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'cep' => [
				'type'       => 'VARCHAR',
				'constraint' => 8
			],

			'id_uf' => [
				'type' => 'INT'
			],

			'id_municipio' => [
				'type' => 'INT'
			],

			'fixo' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'celular_1' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'celular_2' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'email' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'id_login' => [
				'type' => 'INT'
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

		$this->forge->addKey('id_contador', TRUE);
		$this->forge->addForeignKey('id_uf', 'ufs', 'id_uf', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_municipio', 'municipios', 'id_municipio', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_login', 'logins', 'id_login', 'CASCADE', 'CASCADE');
		$this->forge->createTable('contadores');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('contadores');
	}
}
