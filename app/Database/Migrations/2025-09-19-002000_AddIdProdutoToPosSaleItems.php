<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdProdutoToPosSaleItems extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('pos_sale_items')) return;
        $fields = array_map(function ($f) { return $f->name; }, $this->db->getFieldData('pos_sale_items'));
        if (! in_array('id_produto', $fields, true)) {
            $this->forge->addColumn('pos_sale_items', [
                'id_produto' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'id_pos_sale'
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('pos_sale_items')) {
            try { $this->forge->dropColumn('pos_sale_items', 'id_produto'); } catch (\Throwable $e) {}
        }
    }
}



