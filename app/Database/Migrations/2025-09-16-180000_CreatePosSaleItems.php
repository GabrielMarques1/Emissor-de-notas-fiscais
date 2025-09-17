<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePosSaleItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_item' => [ 'type' => 'INT', 'constraint' => 9, 'unsigned' => true, 'auto_increment' => true ],
            'id_pos_sale' => [ 'type' => 'INT', 'constraint' => 9, 'unsigned' => true ],
            'nome' => [ 'type' => 'VARCHAR', 'constraint' => 255 ],
            'codigo_de_barras' => [ 'type' => 'VARCHAR', 'constraint' => 32, 'null' => true ],
            'unidade' => [ 'type' => 'VARCHAR', 'constraint' => 10, 'null' => true ],
            'quantidade' => [ 'type' => 'DECIMAL', 'constraint' => '10,3', 'default' => 1 ],
            'valor_unitario' => [ 'type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0 ],
            'desconto' => [ 'type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0 ],
            'CFOP_NFe' => [ 'type' => 'VARCHAR', 'constraint' => 4, 'null' => true ],
            'CFOP_NFCe' => [ 'type' => 'VARCHAR', 'constraint' => 4, 'null' => true ],
            'CFOP_Externo' => [ 'type' => 'VARCHAR', 'constraint' => 4, 'null' => true ],
            'NCM' => [ 'type' => 'VARCHAR', 'constraint' => 8, 'null' => true ],
            'CSOSN' => [ 'type' => 'VARCHAR', 'constraint' => 4, 'null' => true ],
            'created_at' => [ 'type' => 'DATETIME' ],
            'updated_at' => [ 'type' => 'DATETIME' ],
        ]);
        $this->forge->addKey('id_item', true);
        $this->forge->addKey('id_pos_sale');
        $this->forge->createTable('pos_sale_items');
    }

    public function down()
    {
        $this->forge->dropTable('pos_sale_items');
    }
}


