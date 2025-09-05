<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Fornecedores extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id_fornecedor' => [
				'type'           => 'INT',
				'constraint'     => 9,
				'usigned'        => TRUE,
				'auto_increment' => TRUE
			],

			'tipo' => [
				'type' => 'INT'
			],

			'nome' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'cpf' => [
				'type'       => 'VARCHAR',
				'constraint' => 11
			],

			'cnpj' => [
				'type'       => 'VARCHAR',
				'constraint' => 14
			],

			'razao_social' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'isento' => [
				'type' => 'INT'
			],

			'ie' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
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
				'type' => 'DATETIME'
			]
		]);

		$this->forge->addKey('id_fornecedor', TRUE);
		$this->forge->addForeignKey('id_uf', 'ufs', 'id_uf', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_municipio', 'municipios', 'id_municipio', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
		$this->forge->createTable('fornecedores');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('fornecedores');
	}
}
