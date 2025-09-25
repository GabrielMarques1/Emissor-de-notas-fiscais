<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UsuariosCaixa extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_login' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
            'nome_completo' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['ativo', 'inativo'],
                'default' => 'ativo',
            ],
            'ultimo_acesso' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('id_login');
        $this->forge->addKey('id_empresa');
        $this->forge->addKey(['id_empresa', 'status']);
        
        $this->forge->createTable('usuarios_caixa');
    }

    public function down()
    {
        $this->forge->dropTable('usuarios_caixa');
    }
}
