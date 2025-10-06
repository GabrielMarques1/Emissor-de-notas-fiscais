<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Criar tabela de sangria e suprimento de caixa
 * 
 * Funcionalidade CRÍTICA para gestão de caixa profissional
 */
class CreateCashMovements extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_movement' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_shift' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para shifts (turno atual)',
            ],
            'id_cash_register' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para cash_registers',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['withdrawal', 'supply'],
                'comment'    => 'withdrawal = sangria, supply = suprimento',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor do movimento (sempre positivo)',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Motivo da sangria/suprimento',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Observações adicionais',
            ],
            'performed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para logins (operador)',
            ],
            'authorized_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para logins (gerente que autorizou)',
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 9,
                'unsigned'   => true,
                'comment'    => 'Multi-tenant: id_contador',
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 9,
                'unsigned'   => true,
                'comment'    => 'Multi-tenant: id_empresa',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id_movement', true);
        $this->forge->addKey('id_shift');
        $this->forge->addKey('id_cash_register');
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('type');
        $this->forge->addKey('created_at');
        
        $this->forge->createTable('cash_movements');
    }

    public function down()
    {
        $this->forge->dropTable('cash_movements', true);
    }
}

