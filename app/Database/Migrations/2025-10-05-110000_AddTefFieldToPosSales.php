<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTefFieldToPosSales extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pos_sales', [
            'id_tef_transaction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para tef_transactions',
                'after'      => 'payment_type',
            ],
        ]);
        
        // Adicionar FK
        $this->forge->addForeignKey(
            'pos_sales',
            'id_tef_transaction',
            'tef_transactions',
            'id_tef_transaction',
            'SET NULL',
            'CASCADE',
            'fk_pos_sales_tef'
        );
    }
    
    public function down()
    {
        // Remover coluna (FK será removida automaticamente)
        $this->forge->dropColumn('pos_sales', 'id_tef_transaction');
    }
}

