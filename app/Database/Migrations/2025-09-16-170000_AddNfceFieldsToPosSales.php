<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNfceFieldsToPosSales extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pos_sales', [
            'id_nfce' => [
                'type' => 'INT',
                'constraint' => 9,
                'null' => true,
            ],
            'chave_nfce' => [
                'type' => 'VARCHAR',
                'constraint' => 55,
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pos_sales', 'id_nfce');
        $this->forge->dropColumn('pos_sales', 'chave_nfce');
    }
}


