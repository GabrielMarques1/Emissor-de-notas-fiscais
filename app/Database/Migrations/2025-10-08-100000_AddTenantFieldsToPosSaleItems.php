<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Adicionar campos de tenant em pos_sale_items
 * 
 * CRÍTICO: pos_sale_items precisa de campos de tenant para:
 * 1. Isolamento em queries diretas
 * 2. Auditoria e rastreamento
 * 3. Relatórios e análises por tenant
 */
class AddTenantFieldsToPosSaleItems extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('pos_sale_items')) {
            return;
        }
        
        $fields = array_map(fn($f) => $f->name, $this->db->getFieldData('pos_sale_items'));
        
        // Adicionar id_contador
        if (!in_array('id_contador', $fields, true)) {
            $this->forge->addColumn('pos_sale_items', [
                'id_contador' => [
                    'type'       => 'INT',
                    'constraint' => 9,
                    'unsigned'   => true,
                    'null'       => true, // Temporariamente null para migração de dados existentes
                    'after'      => 'id_pos_sale',
                ],
            ]);
        }
        
        // Adicionar id_empresa
        if (!in_array('id_empresa', $fields, true)) {
            $this->forge->addColumn('pos_sale_items', [
                'id_empresa' => [
                    'type'       => 'INT',
                    'constraint' => 9,
                    'unsigned'   => true,
                    'null'       => true, // Temporariamente null para migração de dados existentes
                    'after'      => 'id_contador',
                ],
            ]);
        }
        
        // Migrar dados existentes: copiar tenant de pos_sales
        $db = \Config\Database::connect();
        $db->query("
            UPDATE pos_sale_items psi
            JOIN pos_sales ps ON ps.id_pos_sale = psi.id_pos_sale
            SET psi.id_contador = ps.id_contador,
                psi.id_empresa = ps.id_empresa
            WHERE psi.id_contador IS NULL OR psi.id_empresa IS NULL
        ");
        
        // Criar índice composto para performance
        $db->query("
            CREATE INDEX IF NOT EXISTS idx_pos_sale_items_tenant 
            ON pos_sale_items(id_empresa, id_contador)
        ");
        
        // Criar índice para join otimizado
        $db->query("
            CREATE INDEX IF NOT EXISTS idx_pos_sale_items_sale_tenant 
            ON pos_sale_items(id_pos_sale, id_empresa, id_contador)
        ");
    }

    public function down()
    {
        if ($this->db->tableExists('pos_sale_items')) {
            $db = \Config\Database::connect();
            
            try {
                $db->query("DROP INDEX IF EXISTS idx_pos_sale_items_tenant ON pos_sale_items");
            } catch (\Throwable $e) {
                // Ignora erro se índice não existe
            }
            
            try {
                $db->query("DROP INDEX IF EXISTS idx_pos_sale_items_sale_tenant ON pos_sale_items");
            } catch (\Throwable $e) {
                // Ignora erro se índice não existe
            }
            
            try {
                $this->forge->dropColumn('pos_sale_items', 'id_contador');
            } catch (\Throwable $e) {
                // Ignora erro
            }
            
            try {
                $this->forge->dropColumn('pos_sale_items', 'id_empresa');
            } catch (\Throwable $e) {
                // Ignora erro
            }
        }
    }
}

