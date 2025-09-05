<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Produtos extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id_produto' => [
				'type'           => 'INT',
				'constraint'     => 9,
				'usigned'        => TRUE,
				'auto_increment' => TRUE
			],

			'nome' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'codigo_de_barras' => [
				'type'       => 'VARCHAR',
				'constraint' => 13
			],

			'valor_unitario' => [
				'type' => 'DOUBLE'
			],

			'CFOP_NFe' => [
				'type'       => 'VARCHAR',
				'constraint' => 4
			],

			'CFOP_NFCe' => [
				'type'       => 'VARCHAR',
				'constraint' => 4
			],

			'CFOP_Externo' => [
				'type'       => 'VARCHAR',
				'constraint' => 4
			],

			'NCM' => [
				'type'       => 'VARCHAR',
				'constraint' => 8
			],

			'CSOSN' => [
				'type'       => 'VARCHAR',
				'constraint' => 3
			],

			'id_unidade' => [
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

		$this->forge->addKey('id_produto', TRUE);
		$this->forge->addForeignKey('id_unidade', 'unidades', 'id_unidade', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
		$this->forge->createTable('produtos');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('produtos');
	}
}
