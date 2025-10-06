<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para Tabela de Auditoria de Registros Deletados
 * Armazena snapshot de todos os registros deletados para auditoria
 */
class CreateAuditDeletedRecordsTable extends Migration
{
    public function up()
    {
        // Tabela para auditoria de registros deletados
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'table_name' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
                'comment' => 'Nome da tabela onde o registro foi deletado'
            ],
            'record_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment' => 'ID do registro deletado'
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'ID do tenant (formato: id_contador:id_empresa)'
            ],
            'id_contador' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID do contador'
            ],
            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID da empresa'
            ],
            'record_data' => [
                'type' => 'LONGTEXT',
                'null' => false,
                'comment' => 'Snapshot completo do registro em JSON'
            ],
            'deleted_by_user' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Usuário que deletou (via variável de sessão)'
            ],
            'deleted_by_ip' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'comment' => 'IP de onde veio a deleção'
            ],
            'deletion_reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Motivo da deleção se informado'
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'comment' => 'Timestamp da deleção'
            ],
            'can_restore' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => '1 = pode ser restaurado, 0 = deleção permanente'
            ],
            'restored_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Timestamp da restauração se aplicável'
            ],
            'restored_by_user' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Usuário que restaurou o registro'
            ]
        ]);

        // Chave primária
        $this->forge->addKey('id', true);
        
        // Índices para consultas rápidas
        $this->forge->addKey(['table_name', 'record_id']);
        $this->forge->addKey(['tenant_id', 'deleted_at']);
        $this->forge->addKey(['id_contador', 'id_empresa', 'deleted_at']);
        $this->forge->addKey('deleted_at');
        $this->forge->addKey(['table_name', 'deleted_at']);
        $this->forge->addKey('can_restore');

        $this->forge->createTable('audit_deleted_records');
        
        echo "✓ Tabela audit_deleted_records criada para auditoria de deleções\n";
    }

    public function down()
    {
        $this->forge->dropTable('audit_deleted_records');
        echo "✓ Tabela audit_deleted_records removida\n";
    }
}
