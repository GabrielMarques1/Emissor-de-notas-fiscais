<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para Tabela de Auditoria de Segurança
 * Registra todas as violações de segurança do TenantFilter
 */
class CreateSecurityAuditTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'violation_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Tipo de violação: TENANT_NOT_IDENTIFIED, TENANT_INACTIVE, etc.'
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => false,
                'comment' => 'IP do cliente que tentou o acesso'
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'User agent do navegador'
            ],
            'uri' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'URI que foi tentada acessar'
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'ID do tenant (formato: id_contador:id_empresa)'
            ],
            'context_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados contextuais da violação em JSON'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['violation_type', 'created_at']);
        $this->forge->addKey(['ip_address', 'created_at']);
        $this->forge->addKey(['tenant_id', 'created_at']);
        $this->forge->addKey('created_at');

        $this->forge->createTable('security_audit');

        echo "✓ Tabela security_audit criada para auditoria de segurança\n";
    }

    public function down()
    {
        $this->forge->dropTable('security_audit');
        echo "✓ Tabela security_audit removida\n";
    }
}
