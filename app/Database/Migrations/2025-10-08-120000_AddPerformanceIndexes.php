<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Índices de performance para queries de relatórios
 * 
 * Baseado em análise de queries lentas do sistema
 */
class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // pos_sales: Relatórios por período e tenant
        $db->query("
            CREATE INDEX IF NOT EXISTS idx_pos_sales_tenant_date 
            ON pos_sales(id_empresa, id_contador, created_at, status)
        ");
        
        // pos_sales: Busca por número de venda
        $db->query("
            CREATE INDEX IF NOT EXISTS idx_pos_sales_sale_number 
            ON pos_sales(sale_number, id_empresa)
        ");
        
        // pos_sales: Relatórios por cliente
        $db->query("
            CREATE INDEX IF NOT EXISTS idx_pos_sales_cliente 
            ON pos_sales(id_cliente, id_empresa, status)
        ");
        
        // shifts: Relatórios de turnos por período
        $db->query("
            CREATE INDEX IF NOT EXISTS idx_shifts_tenant_date 
            ON shifts(id_empresa, id_contador, opened_at, status)
        ");
        
        // produtos: Busca por código de barras (CRÍTICO para PDV)
        $db->query("
            CREATE INDEX IF NOT EXISTS idx_produtos_barcode 
            ON produtos(codigo_de_barras, id_empresa, id_contador)
        ");
        
        // cash_registers: Caixas ativos por tenant
        $db->query("
            CREATE INDEX IF NOT EXISTS idx_cash_registers_tenant_status 
            ON cash_registers(id_empresa, id_contador, status)
        ");
        
        // clientes: Busca por CPF/CNPJ
        if ($db->tableExists('clientes')) {
            $fields = array_map(fn($f) => $f->name, $db->getFieldData('clientes'));
            
            if (in_array('cpf', $fields, true)) {
                $db->query("
                    CREATE INDEX IF NOT EXISTS idx_clientes_cpf 
                    ON clientes(cpf, id_empresa)
                ");
            }
            
            if (in_array('cnpj', $fields, true)) {
                $db->query("
                    CREATE INDEX IF NOT EXISTS idx_clientes_cnpj 
                    ON clientes(cnpj, id_empresa)
                ");
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        $indexes = [
            'pos_sales' => [
                'idx_pos_sales_tenant_date',
                'idx_pos_sales_sale_number',
                'idx_pos_sales_cliente',
            ],
            'shifts' => [
                'idx_shifts_tenant_date',
            ],
            'produtos' => [
                'idx_produtos_barcode',
            ],
            'cash_registers' => [
                'idx_cash_registers_tenant_status',
            ],
            'clientes' => [
                'idx_clientes_cpf',
                'idx_clientes_cnpj',
            ],
        ];
        
        foreach ($indexes as $table => $tableIndexes) {
            if ($db->tableExists($table)) {
                foreach ($tableIndexes as $index) {
                    try {
                        $db->query("DROP INDEX IF EXISTS {$index} ON {$table}");
                    } catch (\Throwable $e) {
                        // Ignora erro se índice não existe
                    }
                }
            }
        }
    }
}

