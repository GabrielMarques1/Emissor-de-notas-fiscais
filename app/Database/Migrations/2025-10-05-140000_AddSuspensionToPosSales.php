<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSuspensionToPosSales extends Migration
{
    public function up()
    {
        // Adicionar campos de suspensão em pos_sales
        $this->forge->addColumn('pos_sales', [
            'is_suspended' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => 'Venda está suspensa (pausada)',
                'after'   => 'status',
            ],
            'suspended_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Data/hora de suspensão',
                'after'   => 'is_suspended',
            ],
            'suspended_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'ID do operador que suspendeu',
                'after'      => 'suspended_at',
            ],
            'suspended_reason' => [
                'type'    => 'VARCHAR',
                'constraint' => 255,
                'null'    => true,
                'comment' => 'Motivo da suspensão',
                'after'   => 'suspended_by',
            ],
            'resumed_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Data/hora de retomada',
                'after'   => 'suspended_reason',
            ],
            'resumed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'ID do operador que retomou',
                'after'      => 'resumed_at',
            ],
            'suspension_expires_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Suspensão expira automaticamente após X horas',
                'after'   => 'resumed_by',
            ],
        ]);
        
        // Adicionar índices para otimizar consultas
        $this->db->query('ALTER TABLE pos_sales ADD INDEX idx_is_suspended (is_suspended, id_contador, id_empresa)');
        $this->db->query('ALTER TABLE pos_sales ADD INDEX idx_suspended_at (suspended_at)');
        $this->db->query('ALTER TABLE pos_sales ADD INDEX idx_suspension_expires (suspension_expires_at)');
        
        // Adicionar configuração de timeout em empresas
        $this->forge->addColumn('empresas', [
            'suspension_timeout_hours' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 24,
                'comment'    => 'Horas até expirar suspensão automática (0 = nunca expira)',
                'after'      => 'pix_expiration_minutes',
            ],
            'max_suspended_sales' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 10,
                'comment'    => 'Máximo de vendas suspensas simultaneamente',
                'after'      => 'suspension_timeout_hours',
            ],
        ]);
    }
    
    public function down()
    {
        // Remover índices
        $this->db->query('ALTER TABLE pos_sales DROP INDEX idx_is_suspended');
        $this->db->query('ALTER TABLE pos_sales DROP INDEX idx_suspended_at');
        $this->db->query('ALTER TABLE pos_sales DROP INDEX idx_suspension_expires');
        
        // Remover colunas de pos_sales
        $this->forge->dropColumn('pos_sales', [
            'is_suspended',
            'suspended_at',
            'suspended_by',
            'suspended_reason',
            'resumed_at',
            'resumed_by',
            'suspension_expires_at',
        ]);
        
        // Remover colunas de empresas
        $this->forge->dropColumn('empresas', [
            'suspension_timeout_hours',
            'max_suspended_sales',
        ]);
    }
}

