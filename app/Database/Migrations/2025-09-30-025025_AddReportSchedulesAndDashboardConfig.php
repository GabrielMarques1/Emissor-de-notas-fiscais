<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReportSchedulesAndDashboardConfig extends Migration
{
    public function up()
    {
        // Tabela de agendamentos de relatórios
        $this->forge->addField([
            'id_schedule' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
            'id_contador' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
            'report_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'vendas, produtos, turnos, fiscal, clientes',
            ],
            'frequency' => [
                'type' => 'ENUM',
                'constraint' => ['daily', 'weekly', 'monthly'],
                'default' => 'weekly',
            ],
            'day_of_week' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => true,
                'comment' => '0=domingo, 6=sabado',
            ],
            'day_of_month' => [
                'type' => 'INT',
                'constraint' => 2,
                'null' => true,
                'comment' => '1-31',
            ],
            'hour' => [
                'type' => 'INT',
                'constraint' => 2,
                'default' => 8,
                'comment' => '0-23',
            ],
            'email_recipients' => [
                'type' => 'TEXT',
                'comment' => 'JSON com lista de emails',
            ],
            'filters' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON com filtros do relatório',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default' => 'active',
            ],
            'last_sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id_schedule');
        $this->forge->addKey('id_empresa');
        $this->forge->addKey(['id_empresa', 'status']);
        $this->forge->createTable('report_schedules');

        // Tabela de configurações do dashboard
        $this->forge->addField([
            'id_config' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
            'id_login' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
            'widgets' => [
                'type' => 'TEXT',
                'comment' => 'JSON com widgets ativos e posições',
            ],
            'layout' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'default',
                'comment' => 'default, compact, expanded',
            ],
            'theme' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'light',
                'comment' => 'light, dark',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id_config');
        $this->forge->addKey(['id_empresa', 'id_login']);
        $this->forge->createTable('dashboard_configs');

        // Tabela de alertas de estoque
        $this->forge->addField([
            'id_alert' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_empresa' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
            'id_produto' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
            'alert_type' => [
                'type' => 'ENUM',
                'constraint' => ['low_stock', 'out_of_stock', 'reorder_point'],
                'default' => 'low_stock',
            ],
            'threshold' => [
                'type' => 'INT',
                'constraint' => 9,
                'comment' => 'Quantidade mínima para alerta',
            ],
            'current_stock' => [
                'type' => 'INT',
                'constraint' => 9,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'resolved', 'ignored'],
                'default' => 'active',
            ],
            'notified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id_alert');
        $this->forge->addKey('id_empresa');
        $this->forge->addKey(['id_empresa', 'status']);
        $this->forge->createTable('stock_alerts');
    }

    public function down()
    {
        $this->forge->dropTable('report_schedules');
        $this->forge->dropTable('dashboard_configs');
        $this->forge->dropTable('stock_alerts');
    }
}