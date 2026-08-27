<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateOutboxEventsTable extends Migration
{
    public function up()
    {
        // Verificar se tabela existe, se não, criar
        if (!$this->db->tableExists('outbox_events')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'table_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ],
                'primary_key_json' => [
                    'type' => 'TEXT',
                ],
                'operation' => [
                    'type' => 'ENUM',
                    'constraint' => ['insert', 'update', 'delete'],
                ],
                'payload' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'id_contador' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'id_empresa' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'processed', 'failed', 'retry'],
                    'default' => 'pending',
                ],
                'retry_count' => [
                    'type' => 'INT',
                    'constraint' => 3,
                    'default' => 0,
                ],
                'error_message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                ],
                'processed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'last_attempt_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            
            $this->forge->addKey('id', true);
            $this->forge->addKey(['id_contador', 'id_empresa', 'status']);
            $this->forge->addKey('created_at');
            $this->forge->addKey('status');
            
            $this->forge->createTable('outbox_events');
        } else {
            // Adicionar colunas se não existirem
            $fields = $this->db->getFieldNames('outbox_events');
            
            if (!in_array('id_contador', $fields)) {
                $this->forge->addColumn('outbox_events', [
                    'id_contador' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'default' => 0,
                        'after' => 'payload'
                    ],
                ]);
            }
            
            if (!in_array('id_empresa', $fields)) {
                $this->forge->addColumn('outbox_events', [
                    'id_empresa' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'default' => 0,
                        'after' => 'id_contador'
                    ],
                ]);
            }
            
            if (!in_array('status', $fields)) {
                $this->forge->addColumn('outbox_events', [
                    'status' => [
                        'type' => 'ENUM',
                        'constraint' => ['pending', 'processed', 'failed', 'retry'],
                        'default' => 'pending',
                        'after' => 'id_empresa'
                    ],
                ]);
            }
            
            if (!in_array('retry_count', $fields)) {
                $this->forge->addColumn('outbox_events', [
                    'retry_count' => [
                        'type' => 'INT',
                        'constraint' => 3,
                        'default' => 0,
                        'after' => 'status'
                    ],
                ]);
            }
            
            if (!in_array('error_message', $fields)) {
                $this->forge->addColumn('outbox_events', [
                    'error_message' => [
                        'type' => 'TEXT',
                        'null' => true,
                        'after' => 'retry_count'
                    ],
                ]);
            }
            
            if (!in_array('last_attempt_at', $fields)) {
                $this->forge->addColumn('outbox_events', [
                    'last_attempt_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'processed_at'
                    ],
                ]);
            }
            
            // Adicionar índices
            $this->db->query('CREATE INDEX IF NOT EXISTS idx_tenant_status ON outbox_events(id_contador, id_empresa, status)');
        }
    }

    public function down()
    {
        // Remover colunas adicionadas (opcional, mantém dados)
        if ($this->db->tableExists('outbox_events')) {
            $this->forge->dropColumn('outbox_events', ['id_contador', 'id_empresa', 'status', 'retry_count', 'error_message', 'last_attempt_at']);
        }
    }
}
