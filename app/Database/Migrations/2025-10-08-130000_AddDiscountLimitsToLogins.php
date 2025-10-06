<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Adicionar limites de desconto por perfil de usuário
 * 
 * Permite controlar desconto máximo que cada operador pode aplicar
 */
class AddDiscountLimitsToLogins extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('logins')) {
            return;
        }
        
        $fields = array_map(fn($f) => $f->name, $this->db->getFieldData('logins'));
        
        // Limite de desconto em percentual
        if (!in_array('max_discount_percentage', $fields, true)) {
            $this->forge->addColumn('logins', [
                'max_discount_percentage' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => '10.00',
                    'comment'    => 'Limite de desconto em % que o usuário pode aplicar',
                    'after'      => 'tipo',
                ],
            ]);
        }
        
        // Limite de desconto em valor fixo
        if (!in_array('max_discount_amount', $fields, true)) {
            $this->forge->addColumn('logins', [
                'max_discount_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                    'comment'    => 'Limite de desconto em R$ (NULL = sem limite)',
                    'after'      => 'max_discount_percentage',
                ],
            ]);
        }
        
        // Permissão para aprovar descontos acima do limite
        if (!in_array('can_approve_discounts', $fields, true)) {
            $this->forge->addColumn('logins', [
                'can_approve_discounts' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'comment'    => '1 = Pode aprovar descontos de outros usuários',
                    'after'      => 'max_discount_amount',
                ],
            ]);
        }
        
        // Atualizar limites padrão por tipo
        $db = \Config\Database::connect();
        
        // Tipo 1 (Admin/Contador): sem limites
        $db->query("
            UPDATE logins 
            SET max_discount_percentage = 100.00,
                max_discount_amount = NULL,
                can_approve_discounts = 1
            WHERE tipo = 1
        ");
        
        // Tipo 3 (Gerente): 30% de desconto
        $db->query("
            UPDATE logins 
            SET max_discount_percentage = 30.00,
                max_discount_amount = 500.00,
                can_approve_discounts = 1
            WHERE tipo = 3
        ");
        
        // Tipo 4 (Operador de Caixa): 10% de desconto
        $db->query("
            UPDATE logins 
            SET max_discount_percentage = 10.00,
                max_discount_amount = 100.00,
                can_approve_discounts = 0
            WHERE tipo = 4
        ");
    }

    public function down()
    {
        if ($this->db->tableExists('logins')) {
            try {
                $this->forge->dropColumn('logins', 'max_discount_percentage');
            } catch (\Throwable $e) {
                // Ignora erro
            }
            
            try {
                $this->forge->dropColumn('logins', 'max_discount_amount');
            } catch (\Throwable $e) {
                // Ignora erro
            }
            
            try {
                $this->forge->dropColumn('logins', 'can_approve_discounts');
            } catch (\Throwable $e) {
                // Ignora erro
            }
        }
    }
}

