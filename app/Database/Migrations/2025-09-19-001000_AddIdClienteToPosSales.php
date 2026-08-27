<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdClienteToPosSales extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('pos_sales')) return;
        $fields = array_map(function ($f) { return $f->name; }, $this->db->getFieldData('pos_sales'));
        if (! in_array('id_cliente', $fields, true)) {
            $this->forge->addColumn('pos_sales', [
                'id_cliente' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'payment_type'
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('pos_sales')) {
            try { $this->forge->dropColumn('pos_sales', 'id_cliente'); } catch (\Throwable $e) {}
        }
    }
}



