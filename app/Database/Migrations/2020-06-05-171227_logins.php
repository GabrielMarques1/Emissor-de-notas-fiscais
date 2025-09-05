<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Logins extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id_login' => [
				'type'           => 'INT',
				'constraint'     => 9,
				'usigned'        => TRUE,
				'auto_increment' => TRUE
			],

			'usuario' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'senha' => [
				'type'       => 'VARCHAR',
				'constraint' => 128
			],

			'tipo' => [
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

		$this->forge->addKey('id_login', TRUE);
		$this->forge->createTable('logins');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('logins');
	}
}
