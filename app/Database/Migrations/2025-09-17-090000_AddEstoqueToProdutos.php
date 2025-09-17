<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEstoqueToProdutos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('produtos', [
            'estoque' => [ 'type' => 'DECIMAL', 'constraint' => '10,3', 'default' => 0 ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('produtos', 'estoque');
    }
}


