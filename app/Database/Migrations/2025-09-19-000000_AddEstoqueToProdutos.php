<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEstoqueToProdutos2 extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('produtos')) {
            return;
        }
        // Só adiciona se não existir
        $fields = array_map(function ($f) { return $f->name; }, $this->db->getFieldData('produtos'));
        if (! in_array('estoque', $fields, true)) {
            $this->forge->addColumn('produtos', [
                'estoque' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '14,4',
                    'null'       => false,
                    'default'    => '0.0000',
                    'after'      => 'valor_unitario'
                ]
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('produtos')) {
            try { $this->forge->dropColumn('produtos', 'estoque'); } catch (\Throwable $e) {}
        }
    }
}



