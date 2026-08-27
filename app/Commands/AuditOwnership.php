<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando de Auditoria de Ownership
 * Verifica integridade de dados multi-tenant e identifica possíveis vazamentos
 */
class AuditOwnership extends BaseCommand
{
    protected $group = 'audit';
    protected $name = 'audit:ownership';
    protected $description = 'Audita integridade de ownership multi-tenant';
    protected $usage = 'audit:ownership [--fix] [--table=table_name]';

    public function run(array $params)
    {
        $fix = CLI::getOption('fix') !== null;
        $specificTable = CLI::getOption('table');
        
        CLI::write('=== AUDITORIA DE OWNERSHIP MULTI-TENANT ===', 'yellow');
        CLI::newLine();
        
        $this->auditDatabase($fix, $specificTable);
        
        CLI::newLine();
        CLI::write('=== AUDITORIA CONCLUÍDA ===', 'green');
    }
    
    private function auditDatabase(bool $fix, ?string $specificTable): void
    {
        $db = \Config\Database::connect();
        
        // Tabelas críticas para auditoria
        $criticalTables = [
            'pos_sales' => ['id_contador', 'id_empresa'],
            'pos_sale_items' => ['id_contador', 'id_empresa'],
            'pos_sale_payments' => ['id_contador', 'id_empresa'],
            'produtos' => ['id_contador', 'id_empresa'],
            'clientes' => ['id_contador', 'id_empresa'],
            'fornecedores' => ['id_contador', 'id_empresa'],
            'usuarios' => ['id_contador', 'id_empresa'],
            'configuracoes' => ['id_contador', 'id_empresa'],
            'cash_registers' => ['id_contador', 'id_empresa'],
            'cash_movements' => ['id_contador', 'id_empresa'],
            'inventory_movements' => ['id_contador', 'id_empresa']
        ];
        
        if ($specificTable) {
            if (isset($criticalTables[$specificTable])) {
                $criticalTables = [$specificTable => $criticalTables[$specificTable]];
            } else {
                CLI::write("Tabela '{$specificTable}' não está na lista de auditoria", 'red');
                return;
            }
        }
        
        $totalIssues = 0;
        $totalFixed = 0;
        
        foreach ($criticalTables as $table => $tenantFields) {
            CLI::write("Auditando tabela: {$table}", 'blue');
            
            if (!$db->tableExists($table)) {
                CLI::write("  ⚠ Tabela não existe, pulando", 'yellow');
                continue;
            }
            
            $issues = $this->auditTable($db, $table, $tenantFields, $fix);
            $totalIssues += $issues['found'];
            $totalFixed += $issues['fixed'];
        }
        
        CLI::newLine();
        CLI::write("RESUMO DA AUDITORIA:", 'cyan');
        CLI::write("- Problemas encontrados: {$totalIssues}", $totalIssues > 0 ? 'red' : 'green');
        CLI::write("- Problemas corrigidos: {$totalFixed}", $totalFixed > 0 ? 'green' : 'light_gray');
        
        if ($totalIssues > 0 && !$fix) {
            CLI::write("Execute com --fix para corrigir automaticamente", 'yellow');
        }
    }
    
    private function auditTable($db, string $table, array $tenantFields, bool $fix): array
    {
        $issues = ['found' => 0, 'fixed' => 0];
        
        try {
            // 1. Verificar registros órfãos (sem tenant válido)
            $orphanedRecords = $this->findOrphanedRecords($db, $table, $tenantFields);
            if (!empty($orphanedRecords)) {
                CLI::write("  ✗ {$table}: " . count($orphanedRecords) . " registros órfãos encontrados", 'red');
                $issues['found'] += count($orphanedRecords);
                
                if ($fix) {
                    $fixed = $this->fixOrphanedRecords($db, $table, $orphanedRecords);
                    $issues['fixed'] += $fixed;
                    CLI::write("    → {$fixed} registros órfãos corrigidos", 'green');
                }
            }
            
            // 2. Verificar integridade referencial
            $referentialIssues = $this->checkReferentialIntegrity($db, $table, $tenantFields);
            if (!empty($referentialIssues)) {
                CLI::write("  ✗ {$table}: " . count($referentialIssues) . " problemas de integridade", 'red');
                $issues['found'] += count($referentialIssues);
                
                if ($fix) {
                    $fixed = $this->fixReferentialIssues($db, $table, $referentialIssues);
                    $issues['fixed'] += $fixed;
                    CLI::write("    → {$fixed} problemas de integridade corrigidos", 'green');
                }
            }
            
            // 3. Verificar consistência de tenant entre tabelas relacionadas
            $consistencyIssues = $this->checkTenantConsistency($db, $table, $tenantFields);
            if (!empty($consistencyIssues)) {
                CLI::write("  ✗ {$table}: " . count($consistencyIssues) . " inconsistências de tenant", 'red');
                $issues['found'] += count($consistencyIssues);
                
                if ($fix) {
                    $fixed = $this->fixTenantConsistency($db, $table, $consistencyIssues);
                    $issues['fixed'] += $fixed;
                    CLI::write("    → {$fixed} inconsistências corrigidas", 'green');
                }
            }
            
            if ($issues['found'] === 0) {
                CLI::write("  ✓ {$table}: Sem problemas encontrados", 'green');
            }
            
        } catch (\Throwable $e) {
            CLI::write("  ✗ {$table}: Erro na auditoria - " . $e->getMessage(), 'red');
        }
        
        return $issues;
    }
    
    private function findOrphanedRecords($db, string $table, array $tenantFields): array
    {
        $builder = $db->table($table);
        
        // Buscar registros com campos de tenant nulos ou zerados
        $builder->groupStart();
        foreach ($tenantFields as $field) {
            $builder->orWhere($field, null)
                   ->orWhere($field, 0)
                   ->orWhere($field, '');
        }
        $builder->groupEnd();
        
        return $builder->get()->getResultArray();
    }
    
    private function checkReferentialIntegrity($db, string $table, array $tenantFields): array
    {
        $issues = [];
        
        try {
            // Verificar se empresas/contadores existem
            if (in_array('id_empresa', $tenantFields)) {
                $invalidEmpresas = $db->query("
                    SELECT t.*, 'invalid_empresa' as issue_type
                    FROM {$table} t
                    LEFT JOIN empresas e ON e.id_empresa = t.id_empresa AND e.id_contador = t.id_contador
                    WHERE t.id_empresa IS NOT NULL 
                    AND t.id_empresa > 0 
                    AND e.id_empresa IS NULL
                    LIMIT 100
                ")->getResultArray();
                
                $issues = array_merge($issues, $invalidEmpresas);
            }
            
            if (in_array('id_contador', $tenantFields)) {
                $invalidContadores = $db->query("
                    SELECT t.*, 'invalid_contador' as issue_type
                    FROM {$table} t
                    LEFT JOIN contadores c ON c.id_contador = t.id_contador
                    WHERE t.id_contador IS NOT NULL 
                    AND t.id_contador > 0 
                    AND c.id_contador IS NULL
                    LIMIT 100
                ")->getResultArray();
                
                $issues = array_merge($issues, $invalidContadores);
            }
            
        } catch (\Throwable $e) {
            // Tabelas podem não existir em alguns ambientes
        }
        
        return $issues;
    }
    
    private function checkTenantConsistency($db, string $table, array $tenantFields): array
    {
        $issues = [];
        
        try {
            // Verificar se há registros com tenant inconsistente
            // Por exemplo, pos_sale_items deve ter mesmo tenant que pos_sales
            if ($table === 'pos_sale_items') {
                $inconsistent = $db->query("
                    SELECT psi.*, 'tenant_mismatch' as issue_type
                    FROM pos_sale_items psi
                    INNER JOIN pos_sales ps ON ps.id_pos_sale = psi.id_pos_sale
                    WHERE (psi.id_contador != ps.id_contador OR psi.id_empresa != ps.id_empresa)
                    LIMIT 100
                ")->getResultArray();
                
                $issues = array_merge($issues, $inconsistent);
            }
            
            // Verificar outros relacionamentos conforme necessário
            
        } catch (\Throwable $e) {
            // Ignorar erros de tabelas que não existem
        }
        
        return $issues;
    }
    
    private function fixOrphanedRecords($db, string $table, array $orphanedRecords): int
    {
        $fixed = 0;
        
        foreach ($orphanedRecords as $record) {
            try {
                // Estratégia: Atribuir ao primeiro tenant ativo disponível
                $firstTenant = $db->table('empresas')
                    ->select('id_contador, id_empresa')
                    ->where('status', 'ativo')
                    ->orderBy('id_contador', 'ASC')
                    ->orderBy('id_empresa', 'ASC')
                    ->limit(1)
                    ->get()
                    ->getFirstRow('array');
                
                if ($firstTenant) {
                    $primaryKey = $this->getPrimaryKey($db, $table);
                    if ($primaryKey && isset($record[$primaryKey])) {
                        $updateData = [
                            'id_contador' => $firstTenant['id_contador'],
                            'id_empresa' => $firstTenant['id_empresa']
                        ];
                        
                        $db->table($table)
                            ->where($primaryKey, $record[$primaryKey])
                            ->update($updateData);
                        
                        $fixed++;
                    }
                }
                
            } catch (\Throwable $e) {
                CLI::write("    Erro ao corrigir registro: " . $e->getMessage(), 'red');
            }
        }
        
        return $fixed;
    }
    
    private function fixReferentialIssues($db, string $table, array $issues): int
    {
        $fixed = 0;
        
        foreach ($issues as $issue) {
            try {
                $primaryKey = $this->getPrimaryKey($db, $table);
                if (!$primaryKey || !isset($issue[$primaryKey])) {
                    continue;
                }
                
                if ($issue['issue_type'] === 'invalid_empresa' || $issue['issue_type'] === 'invalid_contador') {
                    // Estratégia: Remover registros com referências inválidas
                    $db->table($table)
                        ->where($primaryKey, $issue[$primaryKey])
                        ->delete();
                    
                    $fixed++;
                }
                
            } catch (\Throwable $e) {
                CLI::write("    Erro ao corrigir integridade: " . $e->getMessage(), 'red');
            }
        }
        
        return $fixed;
    }
    
    private function fixTenantConsistency($db, string $table, array $issues): int
    {
        $fixed = 0;
        
        foreach ($issues as $issue) {
            try {
                if ($issue['issue_type'] === 'tenant_mismatch' && $table === 'pos_sale_items') {
                    // Corrigir tenant do item para coincidir com a venda
                    $sale = $db->table('pos_sales')
                        ->select('id_contador, id_empresa')
                        ->where('id_pos_sale', $issue['id_pos_sale'])
                        ->get()
                        ->getFirstRow('array');
                    
                    if ($sale) {
                        $db->table('pos_sale_items')
                            ->where('id_pos_sale_item', $issue['id_pos_sale_item'])
                            ->update([
                                'id_contador' => $sale['id_contador'],
                                'id_empresa' => $sale['id_empresa']
                            ]);
                        
                        $fixed++;
                    }
                }
                
            } catch (\Throwable $e) {
                CLI::write("    Erro ao corrigir consistência: " . $e->getMessage(), 'red');
            }
        }
        
        return $fixed;
    }
    
    private function getPrimaryKey($db, string $table): ?string
    {
        try {
            $fields = $db->getFieldData($table);
            foreach ($fields as $field) {
                if ($field->primary_key) {
                    return $field->name;
                }
            }
        } catch (\Throwable $e) {
            // Fallback para nomes comuns
            $commonPrimaryKeys = [
                'pos_sales' => 'id_pos_sale',
                'pos_sale_items' => 'id_pos_sale_item',
                'produtos' => 'id_produto',
                'clientes' => 'id_cliente',
                'usuarios' => 'id_usuario'
            ];
            
            return $commonPrimaryKeys[$table] ?? 'id';
        }
        
        return null;
    }
}
