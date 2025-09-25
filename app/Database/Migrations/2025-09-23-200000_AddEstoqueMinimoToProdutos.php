<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEstoqueMinimoToProdutos extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('estoque_minimo', 'produtos')) {
            $this->forge->addColumn('produtos', [
                'estoque_minimo' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,3',
                    'default'    => 0,
                    'after'      => 'estoque',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('estoque_minimo', 'produtos')) {
            $this->forge->dropColumn('produtos', 'estoque_minimo');
        }
    }
}


