<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmpresaSettings extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('empresa_settings')) {
            $this->forge->addField([
                'id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true ],
                'id_empresa' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
                'chave' => [ 'type' => 'VARCHAR', 'constraint' => 64 ],
                'valor' => [ 'type' => 'TEXT', 'null' => true ], // JSON string
                'created_at' => [ 'type' => 'DATETIME', 'null' => true ],
                'updated_at' => [ 'type' => 'DATETIME', 'null' => true ],
                'deleted_at' => [ 'type' => 'DATETIME', 'null' => true ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['id_empresa','chave']);
            $this->forge->createTable('empresa_settings');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('empresa_settings')) {
            $this->forge->dropTable('empresa_settings');
        }
    }
}


