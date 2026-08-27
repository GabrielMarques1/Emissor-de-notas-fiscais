<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTefTransactions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_tef_transaction' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para pos_sales (null se pré-autorização)',
            ],
            'acquirer' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'cielo, stone, rede, getnet',
            ],
            'card_type' => [
                'type'       => 'ENUM',
                'constraint' => ['credit', 'debit'],
            ],
            'card_brand' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Visa, Mastercard, Elo, etc',
            ],
            'card_last4' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'installments' => [
                'type'       => 'INT',
                'constraint' => 2,
                'default'    => 1,
            ],
            'authorization_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'nsu' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Número Sequencial Único',
            ],
            'tid' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Transaction ID',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'authorized', 'confirmed', 'cancelled', 'failed'],
                'default'    => 'pending',
            ],
            'acquirer_response_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'acquirer_response_message' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'request_payload' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON do request (para debug)',
            ],
            'response_payload' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON do response (para debug)',
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
            'authorized_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'cancelled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id_tef_transaction', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey('status');
        $this->forge->addKey('authorization_code');
        $this->forge->addKey('nsu');
        
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('tef_transactions');
        
        // Adicionar configurações TEF na tabela empresas
        $this->forge->addColumn('empresas', [
            'tef_acquirer' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Adquirente preferencial: cielo, stone, rede, getnet',
                'after'      => 'xFant',
            ],
            'tef_merchant_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'ID do estabelecimento na adquirente',
                'after'      => 'tef_acquirer',
            ],
            'tef_merchant_key' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => 'Chave de acesso (CRIPTOGRAFADA)',
                'after'      => 'tef_merchant_id',
            ],
            'tef_environment' => [
                'type'       => 'ENUM',
                'constraint' => ['sandbox', 'production'],
                'default'    => 'sandbox',
                'after'      => 'tef_merchant_key',
            ],
            'tef_timeout' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 30,
                'comment'    => 'Timeout em segundos',
                'after'      => 'tef_environment',
            ],
            'tef_max_installments' => [
                'type'       => 'INT',
                'constraint' => 2,
                'default'    => 12,
                'after'      => 'tef_timeout',
            ],
        ]);
    }
    
    public function down()
    {
        $this->forge->dropColumn('empresas', [
            'tef_acquirer',
            'tef_merchant_id',
            'tef_merchant_key',
            'tef_environment',
            'tef_timeout',
            'tef_max_installments',
        ]);
        
        $this->forge->dropTable('tef_transactions');
    }
}

