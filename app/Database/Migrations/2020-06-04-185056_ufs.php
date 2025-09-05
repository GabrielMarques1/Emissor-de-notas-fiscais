<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Ufs extends Migration
{
	public function up()
	{
		$this->forge->addField([
            'id_uf' => [
                'type' => 'INT',
                'constraint' => 9,
                'usigned' => true,
                'auto_increment' => true,
            ],

            'codigo_uf' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
            ],

            'estado' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
            ],

            'uf' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
            ],

            'created_at' => [
                'type' => 'DATETIME',
            ],

            'updated_at' => [
                'type' => 'DATETIME',
            ],

            'deleted_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id_uf', true);
        $this->forge->createTable('ufs');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('ufs');
	}
}
