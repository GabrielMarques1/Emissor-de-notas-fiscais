<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePosSalePayments extends Migration
{
    public function up()
    {
        // Tabela para múltiplas formas de pagamento por venda
        $this->forge->addField([
            'id_payment' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para pos_sales',
            ],
            'payment_type' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'credit', 'debit', 'pix', 'voucher', 'check'],
                'comment'    => 'Tipo de pagamento',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor pago nesta forma',
            ],
            'installments' => [
                'type'       => 'INT',
                'constraint' => 2,
                'default'    => 1,
                'comment'    => 'Parcelas (para cartão)',
            ],
            'id_tef_transaction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para tef_transactions (se cartão)',
            ],
            'id_pix_transaction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para pix_transactions (se PIX)',
            ],
            'change_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
                'comment'    => 'Troco (apenas para dinheiro)',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'confirmed', 'failed', 'refunded'],
                'default'    => 'pending',
            ],
            'confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON com dados extras (bandeira, NSU, etc)',
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
        
        $this->forge->addKey('id_payment', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey('payment_type');
        $this->forge->addKey('status');
        
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_tef_transaction', 'tef_transactions', 'id_tef_transaction', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_pix_transaction', 'pix_transactions', 'id_pix_transaction', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('pos_sale_payments');
        
        // Adicionar flag de múltiplas formas em pos_sales
        $this->forge->addColumn('pos_sales', [
            'is_multi_payment' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'comment'    => 'Indica se usa múltiplas formas de pagamento',
                'after'      => 'payment_type',
            ],
            'total_paid' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'Soma dos pagamentos (validação)',
                'after'      => 'is_multi_payment',
            ],
        ]);
    }
    
    public function down()
    {
        $this->forge->dropColumn('pos_sales', ['is_multi_payment', 'total_paid']);
        $this->forge->dropTable('pos_sale_payments');
    }
}

