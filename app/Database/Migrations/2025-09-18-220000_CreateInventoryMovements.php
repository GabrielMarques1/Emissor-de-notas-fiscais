<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInventoryMovements extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_inventory_movement' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE,
            ],

            'id_produto' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],

            'tipo' => [ // 'saida' | 'entrada'
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => false,
            ],

            'quantidade' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,4',
                'null'       => false,
                'default'    => '0.0000',
            ],

            'motivo' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => true,
            ],

            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],

            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
            ],

            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
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
        $this->forge->addKey('id_inventory_movement', TRUE);
        $this->forge->addKey(['id_produto']);
        $this->forge->addKey(['id_pos_sale']);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->createTable('inventory_movements', true);
    }

    public function down()
    {
        $this->forge->dropTable('inventory_movements', true);
    }
}



