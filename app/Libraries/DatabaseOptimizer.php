<?php

namespace App\Libraries;

use Config\Database;

/**
 * Biblioteca de Otimização de Banco de Dados
 * Analisa queries lentas e otimiza performance multi-tenant
 */
class DatabaseOptimizer
{
    private $db;
    private $slowQueryThreshold = 1.0; // 1 segundo
    
    public function __construct()
    {
        $this->db = Database::connect();
    }
    
    /**
     * Habilita slow query log do MySQL
     */
    public function enableSlowQueryLog(): array
    {
        try {
            // Verificar se slow query log está habilitado
            $result = $this->db->query("SHOW VARIABLES LIKE 'slow_query_log'")->getRowArray();
            $isEnabled = $result['Value'] === 'ON';
            
            if (!$isEnabled) {
                // Habilitar slow query log
                $this->db->query("SET GLOBAL slow_query_log = 'ON'");
                $this->db->query("SET GLOBAL long_query_time = {$this->slowQueryThreshold}");
                $this->db->query("SET GLOBAL log_queries_not_using_indexes = 'ON'");
            }
            
            // Obter configurações atuais
            $config = $this->db->query("
                SHOW VARIABLES WHERE Variable_name IN (
                    'slow_query_log', 
                    'slow_query_log_file', 
                    'long_query_time',
                    'log_queries_not_using_indexes'
                )
            ")->getResultArray();
            
            return [
                'success' => true,
                'was_enabled' => $isEnabled,
                'config' => array_column($config, 'Value', 'Variable_name')
            ];
            
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Analisa queries mais executadas
     */
    public function analyzeQueryPerformance(): array
    {
        try {
            // Queries mais executadas
            $topQueries = $this->db->query("
                SELECT 
                    DIGEST_TEXT as query_text,
                    COUNT_STAR as exec_count,
                    AVG_TIMER_WAIT/1000000000000 as avg_time_sec,
                    MAX_TIMER_WAIT/1000000000000 as max_time_sec,
                    SUM_TIMER_WAIT/1000000000000 as total_time_sec,
                    SUM_ROWS_EXAMINED as total_rows_examined,
                    SUM_ROWS_SENT as total_rows_sent
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE DIGEST_TEXT IS NOT NULL
                AND DIGEST_TEXT NOT LIKE '%performance_schema%'
                ORDER BY AVG_TIMER_WAIT DESC 
                LIMIT 20
            ")->getResultArray();
            
            // Queries sem índices
            $noIndexQueries = $this->db->query("
                SELECT 
                    DIGEST_TEXT as query_text,
                    COUNT_STAR as exec_count,
                    SUM_NO_INDEX_USED as no_index_count,
                    SUM_NO_GOOD_INDEX_USED as bad_index_count
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE SUM_NO_INDEX_USED > 0 OR SUM_NO_GOOD_INDEX_USED > 0
                ORDER BY SUM_NO_INDEX_USED DESC 
                LIMIT 10
            ")->getResultArray();
            
            return [
                'success' => true,
                'top_slow_queries' => $topQueries,
                'queries_without_indexes' => $noIndexQueries,
                'analysis_time' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cria índices compostos otimizados para multi-tenant
     */
    public function createOptimizedIndexes(): array
    {
        $results = [];
        
        // Tabelas principais com tenant_id
        $tenantTables = [
            'pos_sales' => ['id_contador', 'id_empresa', 'status', 'created_at'],
            'pos_sale_items' => ['id_contador', 'id_empresa', 'id_pos_sale'],
            'pos_sale_payments' => ['id_contador', 'id_empresa', 'id_pos_sale', 'status'],
            'produtos' => ['id_contador', 'id_empresa', 'status', 'categoria_id'],
            'clientes' => ['id_contador', 'id_empresa', 'cpf_cnpj', 'status'],
            'fornecedores' => ['id_contador', 'id_empresa', 'cnpj', 'status'],
            'empresas' => ['id_contador', 'status'],
            'configuracoes' => ['id_contador', 'id_empresa', 'chave'],
            'cash_registers' => ['id_contador', 'id_empresa', 'status'],
            'cash_movements' => ['id_contador', 'id_empresa', 'cash_register_id', 'created_at'],
            'inventory_movements' => ['id_contador', 'id_empresa', 'product_id', 'created_at'],
            'outbox_events' => ['id_contador', 'id_empresa', 'status', 'created_at'],
            'offline_audit_log' => ['id_contador', 'id_empresa', 'status', 'created_at']
        ];
        
        foreach ($tenantTables as $table => $columns) {
            try {
                if (!$this->db->tableExists($table)) {
                    $results[$table] = ['status' => 'skipped', 'reason' => 'table_not_exists'];
                    continue;
                }
                
                // Criar índice composto principal (tenant + campos mais usados)
                $indexName = "idx_{$table}_tenant_optimized";
                $columnList = implode(', ', $columns);
                
                // Verificar se índice já existe
                $existingIndexes = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")->getResultArray();
                
                if (empty($existingIndexes)) {
                    $sql = "CREATE INDEX {$indexName} ON {$table} ({$columnList})";
                    $this->db->query($sql);
                    $results[$table] = ['status' => 'created', 'index' => $indexName, 'columns' => $columns];
                } else {
                    $results[$table] = ['status' => 'exists', 'index' => $indexName];
                }
                
            } catch (\Throwable $e) {
                $results[$table] = ['status' => 'error', 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Otimiza configuração do MySQL para multi-tenant
     */
    public function optimizeMySQLConfig(): array
    {
        $optimizations = [];
        
        try {
            // Configurações recomendadas para multi-tenant
            $configs = [
                'innodb_buffer_pool_size' => '256M', // Ajustar conforme RAM disponível
                'innodb_log_file_size' => '64M',
                'innodb_flush_log_at_trx_commit' => '2', // Performance vs durabilidade
                'query_cache_size' => '64M',
                'query_cache_type' => 'ON',
                'tmp_table_size' => '32M',
                'max_heap_table_size' => '32M',
                'join_buffer_size' => '2M',
                'sort_buffer_size' => '2M'
            ];
            
            foreach ($configs as $var => $value) {
                try {
                    // Verificar valor atual
                    $current = $this->db->query("SHOW VARIABLES LIKE '{$var}'")->getRowArray();
                    
                    if ($current && $current['Value'] !== $value) {
                        // Tentar alterar (algumas variáveis precisam de restart)
                        $this->db->query("SET GLOBAL {$var} = '{$value}'");
                        $optimizations[$var] = [
                            'status' => 'updated',
                            'old_value' => $current['Value'],
                            'new_value' => $value
                        ];
                    } else {
                        $optimizations[$var] = [
                            'status' => 'already_optimal',
                            'value' => $current['Value'] ?? 'unknown'
                        ];
                    }
                    
                } catch (\Throwable $e) {
                    $optimizations[$var] = [
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'note' => 'May require server restart or different privileges'
                    ];
                }
            }
            
            return [
                'success' => true,
                'optimizations' => $optimizations,
                'note' => 'Some changes may require MySQL restart'
            ];
            
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Analisa uso de índices em queries específicas
     */
    public function analyzeIndexUsage(string $query): array
    {
        try {
            // Usar EXPLAIN para analisar query
            $explain = $this->db->query("EXPLAIN {$query}")->getResultArray();
            
            $analysis = [
                'query' => $query,
                'explain_result' => $explain,
                'recommendations' => []
            ];
            
            foreach ($explain as $row) {
                // Analisar cada linha do EXPLAIN
                if ($row['type'] === 'ALL') {
                    $analysis['recommendations'][] = "Table scan detected on {$row['table']} - consider adding index";
                }
                
                if ($row['key'] === null) {
                    $analysis['recommendations'][] = "No index used for table {$row['table']} - add appropriate index";
                }
                
                if (isset($row['rows']) && $row['rows'] > 1000) {
                    $analysis['recommendations'][] = "High row count ({$row['rows']}) on {$row['table']} - optimize WHERE clause";
                }
                
                if (isset($row['Extra']) && strpos($row['Extra'], 'Using filesort') !== false) {
                    $analysis['recommendations'][] = "Filesort detected on {$row['table']} - consider adding ORDER BY index";
                }
            }
            
            return $analysis;
            
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'query' => $query
            ];
        }
    }
    
    /**
     * Gera relatório completo de performance
     */
    public function generatePerformanceReport(): array
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database_info' => [],
            'slow_query_analysis' => [],
            'index_analysis' => [],
            'recommendations' => []
        ];
        
        try {
            // Informações básicas do banco
            $version = $this->db->query("SELECT VERSION() as version")->getRowArray();
            $uptime = $this->db->query("SHOW STATUS LIKE 'Uptime'")->getRowArray();
            $connections = $this->db->query("SHOW STATUS LIKE 'Connections'")->getRowArray();
            
            $report['database_info'] = [
                'version' => $version['version'],
                'uptime_seconds' => $uptime['Value'],
                'total_connections' => $connections['Value']
            ];
            
            // Análise de queries lentas
            $report['slow_query_analysis'] = $this->analyzeQueryPerformance();
            
            // Status dos índices
            $report['index_analysis'] = $this->createOptimizedIndexes();
            
            // Recomendações gerais
            $report['recommendations'] = [
                'Enable query cache if not already enabled',
                'Consider partitioning large tables by tenant_id',
                'Implement connection pooling for high concurrency',
                'Regular ANALYZE TABLE for updated statistics',
                'Monitor slow query log daily',
                'Consider read replicas for reporting queries'
            ];
            
            return $report;
            
        } catch (\Throwable $e) {
            $report['error'] = $e->getMessage();
            return $report;
        }
    }
}
