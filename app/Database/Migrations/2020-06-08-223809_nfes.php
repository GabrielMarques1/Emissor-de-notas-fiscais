<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Nfes extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id_nfe' => [
				'type'           => 'INT',
				'constraint'     => 9,
				'usigned'        => TRUE,
				'auto_increment' => TRUE
			],

			'numero' => [
				'type' => 'INT'
			],

			'chave' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'valor_da_nota' => [
				'type' => 'DOUBLE'
			],
			
			'data' => [
				'type' => 'DATE'
			],
			
			'hora' => [
				'type' => 'TIME'
			],

			'xml' => [
				'type' => 'TEXT'
			],

			'protocolo' => [
				'type' => 'TEXT'
			],

			'status' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'tipo' => [
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

		$this->forge->addKey('id_nfe', TRUE);
		$this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
		$this->forge->createTable('nfes');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('nfes');
	}
}
