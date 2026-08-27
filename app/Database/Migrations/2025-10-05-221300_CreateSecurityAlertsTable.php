<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para Tabela de Alertas de Segurança
 * Armazena alertas automáticos gerados pelo sistema de auditoria
 */
class CreateSecurityAlertsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'alert_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Tipo do alerta (login_failures, cross_tenant, etc.)'
            ],
            'tenant_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'comment' => 'ID do tenant (formato: id_contador:id_empresa)'
            ],
            'severity' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default' => 'medium',
                'comment' => 'Severidade do alerta'
            ],
            'alert_data' => [
                'type' => 'JSON',
                'null' => false,
                'comment' => 'Dados específicos do alerta em formato JSON'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'acknowledged', 'resolved', 'false_positive'],
                'default' => 'pending',
                'comment' => 'Status do alerta'
            ],
            'acknowledged_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID do usuário que reconheceu o alerta'
            ],
            'acknowledged_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Timestamp do reconhecimento'
            ],
            'resolved_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID do usuário que resolveu o alerta'
            ],
            'resolved_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Timestamp da resolução'
            ],
            'resolution_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Notas sobre a resolução do alerta'
            ],
            'notification_sent' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '1 se notificação foi enviada, 0 caso contrário'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'comment' => 'Timestamp de criação do alerta'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Timestamp da última atualização'
            ]
        ]);

        // Chave primária
        $this->forge->addKey('id', true);
        
        // Índices para consultas rápidas
        $this->forge->addKey(['tenant_id', 'created_at']);
        $this->forge->addKey(['alert_type', 'status']);
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('severity');
        $this->forge->addKey('created_at');

        $this->forge->createTable('security_alerts');
        
        echo "✓ Tabela security_alerts criada para alertas automáticos\n";
    }

    public function down()
    {
        $this->forge->dropTable('security_alerts');
        echo "✓ Tabela security_alerts removida\n";
    }
}
