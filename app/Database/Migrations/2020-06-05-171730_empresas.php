<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Empresas extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id_empresa' => [
				'type'           => 'INT',
				'constraint'     => 9,
				'usigned'        => TRUE,
				'auto_increment' => TRUE
			],

			'status' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'CNPJ' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],
			
			'xNome' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'xFant' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'IE' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'dia_do_pagamento' => [
				'type' => 'INT'
			],

			'CEP' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'xLgr' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'nro' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'xCpl' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'xBairro' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'fone' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'natOp' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'serie' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'verProc' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'nNF_homologacao' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'nNF_producao' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'tpAmb_NFe' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'nNFC_homologacao' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'nNFC_producao' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'tpAmb_NFCe' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'CSC_Id' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'CSC' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'certificado' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'senha_do_certificado' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'id_login' => [
				'type' => 'INT'
			],

			'id_contador' => [
				'type' => 'INT'
			],

			'id_uf' => [
				'type' => 'INT'
			],

			'id_municipio' => [
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

		$this->forge->addKey('id_empresa', TRUE);
		$this->forge->addForeignKey('id_login', 'logins', 'id_login', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_uf', 'ufs', 'id_uf', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_municipio', 'municipios', 'id_municipio', 'CASCADE', 'CASCADE');
		$this->forge->createTable('empresas');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('empresas');
	}
}
