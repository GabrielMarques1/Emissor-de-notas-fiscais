<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePixTransactions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_pix_transaction' => [
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
                'comment'    => 'FK para pos_sales',
            ],
            'txid' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
                'comment'    => 'Transaction ID único (BACEN)',
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'mercadopago, pagseguro, banco',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'qr_code' => [
                'type' => 'TEXT',
                'comment' => 'BR Code (copia e cola)',
            ],
            'qr_code_image' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Base64 da imagem QR Code',
            ],
            'pix_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Chave PIX do recebedor',
            ],
            'e2e_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'End to End ID (confirmação)',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'confirmed', 'expired', 'cancelled'],
                'default'    => 'pending',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'comment' => 'Data/hora de expiração',
            ],
            'confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'cancelled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'webhook_data' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON do webhook de confirmação',
            ],
            'request_payload' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON do request (debug)',
            ],
            'response_payload' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON do response (debug)',
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
        
        $this->forge->addKey('id_pix_transaction', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey('status');
        $this->forge->addKey('expires_at');
        
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('pix_transactions');
        
        // Adicionar configurações PIX na tabela empresas
        $this->forge->addColumn('empresas', [
            'pix_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Provedor PIX: mercadopago, pagseguro, banco',
                'after'      => 'tef_max_installments',
            ],
            'pix_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Chave PIX da empresa',
                'after'      => 'pix_provider',
            ],
            'pix_access_token' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Token de acesso API (CRIPTOGRAFADO)',
                'after' => 'pix_key',
            ],
            'pix_webhook_secret' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Secret para validar webhooks',
                'after'      => 'pix_access_token',
            ],
            'pix_expiration_minutes' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 15,
                'comment'    => 'Tempo de expiração do QR Code em minutos',
                'after'      => 'pix_webhook_secret',
            ],
        ]);
        
        // Adicionar campo PIX em pos_sales
        $this->forge->addColumn('pos_sales', [
            'id_pix_transaction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para pix_transactions',
                'after'      => 'id_tef_transaction',
            ],
        ]);
        
        $this->forge->addForeignKey(
            'pos_sales',
            'id_pix_transaction',
            'pix_transactions',
            'id_pix_transaction',
            'SET NULL',
            'CASCADE',
            'fk_pos_sales_pix'
        );
    }
    
    public function down()
    {
        // Remover coluna de pos_sales (FK será removida automaticamente)
        $this->forge->dropColumn('pos_sales', 'id_pix_transaction');
        
        // Remover colunas de empresas
        $this->forge->dropColumn('empresas', [
            'pix_provider',
            'pix_key',
            'pix_access_token',
            'pix_webhook_secret',
            'pix_expiration_minutes',
        ]);
        
        $this->forge->dropTable('pix_transactions');
    }
}

