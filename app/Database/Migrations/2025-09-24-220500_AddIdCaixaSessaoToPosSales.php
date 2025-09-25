<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdCaixaSessaoToPosSales extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('id_caixa_sessao', 'pos_sales')) {
            $this->forge->addColumn('pos_sales', [
                'id_caixa_sessao' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'id_shift',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('id_caixa_sessao', 'pos_sales')) {
            $this->forge->dropColumn('pos_sales', 'id_caixa_sessao');
        }
    }
}


