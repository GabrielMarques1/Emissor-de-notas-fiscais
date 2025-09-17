<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFullPosSchema extends Migration
{
    public function up()
    {
        // cash_registers
        $this->forge->addField([
            'id_cash_register' => [
                'type'           => 'INT',
                'constraint'     => 9,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE
            ],

            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 128
            ],

            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 128
            ],

            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20
            ],

            'id_contador' => [
                'type' => 'INT',
                'constraint' => 9
            ],

            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 9
            ],

            'created_at' => [
                'type' => 'DATETIME'
            ],

            'updated_at' => [
                'type' => 'DATETIME'
            ],

            'deleted_at' => [
                'type' => 'DATETIME'
            ],
        ]);
        $this->forge->addKey('id_cash_register', TRUE);
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cash_registers');

        // shifts
        $this->forge->addField([
            'id_shift' => [
                'type'           => 'INT',
                'constraint'     => 9,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE
            ],

            'id_cash_register' => [
                'type'       => 'INT',
                'constraint' => 9,
                'unsigned'   => TRUE
            ],

            'opened_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 64
            ],

            'closed_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 64
            ],

            'opened_at' => [
                'type' => 'DATETIME'
            ],

            'closed_at' => [
                'type' => 'DATETIME'
            ],

            'opening_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2'
            ],

            'closing_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2'
            ],

            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20
            ],

            'id_contador' => [
                'type' => 'INT',
                'constraint' => 9
            ],

            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 9
            ],

            'created_at' => [
                'type' => 'DATETIME'
            ],

            'updated_at' => [
                'type' => 'DATETIME'
            ],

            'deleted_at' => [
                'type' => 'DATETIME'
            ],
        ]);
        $this->forge->addKey('id_shift', TRUE);
        $this->forge->addForeignKey('id_cash_register', 'cash_registers', 'id_cash_register', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        $this->forge->createTable('shifts');

        // pos_sales
        $this->forge->addField([
            'id_pos_sale' => [
                'type'           => 'INT',
                'constraint'     => 9,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE
            ],

            'id_shift' => [
                'type'       => 'INT',
                'constraint' => 9,
                'unsigned'   => TRUE
            ],

            'id_cash_register' => [
                'type'       => 'INT',
                'constraint' => 9,
                'unsigned'   => TRUE
            ],

            'sale_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 32
            ],

            'total' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2'
            ],

            'discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2'
            ],

            'paid_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2'
            ],

            'change_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2'
            ],

            'payment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 16
            ],

            'notes' => [
                'type'       => 'VARCHAR',
                'constraint' => 255
            ],

            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20
            ],

            'id_contador' => [
                'type' => 'INT',
                'constraint' => 9
            ],

            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 9
            ],

            'created_at' => [
                'type' => 'DATETIME'
            ],

            'updated_at' => [
                'type' => 'DATETIME'
            ],

            'deleted_at' => [
                'type' => 'DATETIME'
            ],
        ]);
        $this->forge->addKey('id_pos_sale', TRUE);
        $this->forge->addForeignKey('id_shift', 'shifts', 'id_shift', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pos_sales');
    }

    //--------------------------------------------------------------------

    public function down()
    {
        $this->forge->dropTable('pos_sales');
        $this->forge->dropTable('shifts');
        $this->forge->dropTable('cash_registers');
    }
}


