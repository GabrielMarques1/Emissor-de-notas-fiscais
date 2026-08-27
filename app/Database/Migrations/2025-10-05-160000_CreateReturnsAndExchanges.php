<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReturnsAndExchanges extends Migration
{
    public function up()
    {
        // Tabela de devoluções e trocas
        $this->forge->addField([
            'id_return' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_original_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para venda original',
            ],
            'id_new_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para nova venda (se troca)',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['full_return', 'partial_return', 'exchange'],
                'comment'    => 'Tipo de operação',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Motivo da devolução',
            ],
            'total_returned' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor total devolvido',
            ],
            'refund_method' => [
                'type'       => 'ENUM',
                'constraint' => ['same_method', 'cash', 'credit', 'voucher'],
                'comment'    => 'Método de estorno',
            ],
            'refund_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed'],
                'default'    => 'pending',
            ],
            'processed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'comment'    => 'Operador que processou',
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Gerente que aprovou',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Observações adicionais',
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id_return', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_original_sale');
        $this->forge->addKey('id_new_sale');
        $this->forge->addKey('refund_status');
        $this->forge->addForeignKey('id_original_sale', 'pos_sales', 'id_pos_sale', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_new_sale', 'pos_sales', 'id_pos_sale', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        $this->forge->createTable('returns');
        
        // Tabela de itens devolvidos
        $this->forge->addField([
            'id_return_item' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_return' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para devolução',
            ],
            'id_original_item' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para item original',
            ],
            'id_produto' => [
                'type'       => 'INT',
                'constraint' => 11,
                'comment'    => 'FK para produto',
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'comment'    => 'Quantidade devolvida',
            ],
            'unit_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'total_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'condition' => [
                'type'       => 'ENUM',
                'constraint' => ['perfect', 'good', 'damaged', 'defective'],
                'default'    => 'perfect',
            ],
            'restock' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'comment' => 'Repor em estoque?',
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id_return_item', true);
        $this->forge->addKey('id_return');
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addForeignKey('id_return', 'returns', 'id_return', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_original_item', 'pos_sale_items', 'id_item', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        $this->forge->createTable('return_items');
        
        // Adicionar configurações de devolução em empresas
        $this->forge->addColumn('empresas', [
            'return_days_limit' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 7,
                'comment'    => 'Prazo para devolução (dias)',
                'after'      => 'discount_approval_threshold',
            ],
            'require_return_approval' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'comment' => 'Exige aprovação de gerente',
                'after'   => 'return_days_limit',
            ],
            'allow_partial_returns' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'comment' => 'Permite devolução parcial',
                'after'   => 'require_return_approval',
            ],
            'allow_exchanges' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'comment' => 'Permite trocas',
                'after'   => 'allow_partial_returns',
            ],
        ]);
    }
    
    public function down()
    {
        $this->forge->dropTable('return_items');
        $this->forge->dropTable('returns');
        
        $this->forge->dropColumn('empresas', [
            'return_days_limit',
            'require_return_approval',
            'allow_partial_returns',
            'allow_exchanges',
        ]);
    }
}

