<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando para Executar Otimizações de Performance
 * Aplica todas as otimizações de banco, cache e queries
 */
class OptimizePerformance extends BaseCommand
{
    protected $group = 'optimize';
    protected $name = 'optimize:performance';
    protected $description = 'Executa otimizações completas de performance multi-tenant';
    protected $usage = 'optimize:performance [--analyze] [--indexes] [--cache] [--mysql]';

    public function run(array $params)
    {
        $analyze = CLI::getOption('analyze') !== null;
        $indexes = CLI::getOption('indexes') !== null;
        $cache = CLI::getOption('cache') !== null;
        $mysql = CLI::getOption('mysql') !== null;
        
        // Se nenhuma opção específica, executar tudo
        if (!$analyze && !$indexes && !$cache && !$mysql) {
            $analyze = $indexes = $cache = $mysql = true;
        }
        
        CLI::write('=== OTIMIZAÇÃO DE PERFORMANCE PDV MULTI-TENANT ===', 'yellow');
        CLI::newLine();
        
        if ($analyze) {
            $this->analyzeDatabase();
        }
        
        if ($indexes) {
            $this->createOptimizedIndexes();
        }
        
        if ($cache) {
            $this->setupCache();
        }
        
        if ($mysql) {
            $this->optimizeMySQL();
        }
        
        $this->generateReport();
        
        CLI::newLine();
        CLI::write('=== OTIMIZAÇÃO CONCLUÍDA ===', 'green');
    }
    
    private function analyzeDatabase(): void
    {
        CLI::write('1. Analisando performance do banco de dados...', 'blue');
        
        try {
            $optimizer = new \App\Libraries\DatabaseOptimizer();
            
            // Habilitar slow query log
            $slowQueryResult = $optimizer->enableSlowQueryLog();
            if ($slowQueryResult['success']) {
                CLI::write('✓ Slow query log habilitado', 'green');
                if (!$slowQueryResult['was_enabled']) {
                    CLI::write('  - Configurado para queries > 1 segundo', 'light_gray');
                }
            } else {
                CLI::write('✗ Erro ao habilitar slow query log: ' . $slowQueryResult['error'], 'red');
            }
            
            // Analisar queries
            $analysis = $optimizer->analyzeQueryPerformance();
            if ($analysis['success']) {
                CLI::write('✓ Análise de queries concluída', 'green');
                CLI::write('  - Queries lentas encontradas: ' . count($analysis['top_slow_queries']), 'light_gray');
                CLI::write('  - Queries sem índices: ' . count($analysis['queries_without_indexes']), 'light_gray');
                
                // Mostrar top 3 queries mais lentas
                if (!empty($analysis['top_slow_queries'])) {
                    CLI::write('  Top 3 queries mais lentas:', 'yellow');
                    $topQueries = array_slice($analysis['top_slow_queries'], 0, 3);
                    foreach ($topQueries as $i => $query) {
                        $avgTime = number_format($query['avg_time_sec'], 4);
                        CLI::write("    " . ($i + 1) . ". {$avgTime}s - " . substr($query['query_text'], 0, 80) . '...', 'light_gray');
                    }
                }
            } else {
                CLI::write('✗ Erro na análise: ' . $analysis['error'], 'red');
            }
            
        } catch (\Throwable $e) {
            CLI::write('✗ Erro na análise do banco: ' . $e->getMessage(), 'red');
        }
        
        CLI::newLine();
    }
    
    private function createOptimizedIndexes(): void
    {
        CLI::write('2. Criando índices otimizados...', 'blue');
        
        try {
            $optimizer = new \App\Libraries\DatabaseOptimizer();
            $results = $optimizer->createOptimizedIndexes();
            
            $created = 0;
            $exists = 0;
            $errors = 0;
            
            foreach ($results as $table => $result) {
                switch ($result['status']) {
                    case 'created':
                        $created++;
                        CLI::write("✓ {$table}: Índice criado", 'green');
                        break;
                    case 'exists':
                        $exists++;
                        CLI::write("- {$table}: Índice já existe", 'light_gray');
                        break;
                    case 'error':
                        $errors++;
                        CLI::write("✗ {$table}: " . $result['error'], 'red');
                        break;
                    case 'skipped':
                        CLI::write("⚠ {$table}: Tabela não existe", 'yellow');
                        break;
                }
            }
            
            CLI::write("Resumo: {$created} criados, {$exists} existentes, {$errors} erros", 'cyan');
            
        } catch (\Throwable $e) {
            CLI::write('✗ Erro ao criar índices: ' . $e->getMessage(), 'red');
        }
        
        CLI::newLine();
    }
    
    private function setupCache(): void
    {
        CLI::write('3. Configurando sistema de cache...', 'blue');
        
        try {
            // Testar conexão Redis
            $cache = \Config\Services::cache();
            
            // Testar operações básicas
            $testKey = 'performance_test_' . time();
            $testData = ['test' => 'data', 'timestamp' => time()];
            
            if ($cache->save($testKey, $testData, 60)) {
                CLI::write('✓ Cache Redis funcionando', 'green');
                
                $retrieved = $cache->get($testKey);
                if ($retrieved && $retrieved['test'] === 'data') {
                    CLI::write('✓ Leitura do cache funcionando', 'green');
                } else {
                    CLI::write('✗ Erro na leitura do cache', 'red');
                }
                
                $cache->delete($testKey);
                CLI::write('✓ Cache de teste limpo', 'green');
                
            } else {
                CLI::write('✗ Erro ao salvar no cache - usando fallback file cache', 'yellow');
            }
            
            // Testar TenantCache
            $tenantCache = new \App\Libraries\TenantCache(1, 1);
            $tenantTestKey = 'tenant_performance_test';
            
            if ($tenantCache->set($tenantTestKey, $testData, 60)) {
                CLI::write('✓ TenantCache funcionando', 'green');
                $tenantCache->delete($tenantTestKey);
            } else {
                CLI::write('✗ Erro no TenantCache', 'red');
            }
            
        } catch (\Throwable $e) {
            CLI::write('✗ Erro no cache: ' . $e->getMessage(), 'red');
        }
        
        CLI::newLine();
    }
    
    private function optimizeMySQL(): void
    {
        CLI::write('4. Otimizando configuração MySQL...', 'blue');
        
        try {
            $optimizer = new \App\Libraries\DatabaseOptimizer();
            $result = $optimizer->optimizeMySQLConfig();
            
            if ($result['success']) {
                $updated = 0;
                $optimal = 0;
                $errors = 0;
                
                foreach ($result['optimizations'] as $var => $optimization) {
                    switch ($optimization['status']) {
                        case 'updated':
                            $updated++;
                            CLI::write("✓ {$var}: {$optimization['old_value']} → {$optimization['new_value']}", 'green');
                            break;
                        case 'already_optimal':
                            $optimal++;
                            CLI::write("- {$var}: Já otimizado ({$optimization['value']})", 'light_gray');
                            break;
                        case 'error':
                            $errors++;
                            CLI::write("✗ {$var}: " . $optimization['error'], 'red');
                            break;
                    }
                }
                
                CLI::write("Resumo: {$updated} atualizados, {$optimal} já otimizados, {$errors} erros", 'cyan');
                
                if ($updated > 0) {
                    CLI::write('⚠ Algumas mudanças podem requerer restart do MySQL', 'yellow');
                }
                
            } else {
                CLI::write('✗ Erro na otimização MySQL: ' . $result['error'], 'red');
            }
            
        } catch (\Throwable $e) {
            CLI::write('✗ Erro na otimização MySQL: ' . $e->getMessage(), 'red');
        }
        
        CLI::newLine();
    }
    
    private function generateReport(): void
    {
        CLI::write('5. Gerando relatório de performance...', 'blue');
        
        try {
            $optimizer = new \App\Libraries\DatabaseOptimizer();
            $report = $optimizer->generatePerformanceReport();
            
            // Salvar relatório
            $reportPath = WRITEPATH . 'logs/performance_report_' . date('Y-m-d_H-i-s') . '.json';
            
            if (!is_dir(dirname($reportPath))) {
                mkdir(dirname($reportPath), 0755, true);
            }
            
            file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            CLI::write('✓ Relatório salvo em: ' . $reportPath, 'green');
            
            // Mostrar resumo
            if (isset($report['database_info'])) {
                CLI::write('Informações do banco:', 'cyan');
                CLI::write('  - Versão: ' . $report['database_info']['version'], 'light_gray');
                CLI::write('  - Uptime: ' . gmdate('H:i:s', $report['database_info']['uptime_seconds']), 'light_gray');
                CLI::write('  - Conexões: ' . number_format($report['database_info']['total_connections']), 'light_gray');
            }
            
            // Recomendações principais
            CLI::write('Recomendações principais:', 'cyan');
            CLI::write('  - Monitorar slow query log diariamente', 'light_gray');
            CLI::write('  - Executar ANALYZE TABLE semanalmente', 'light_gray');
            CLI::write('  - Considerar particionamento por tenant_id', 'light_gray');
            CLI::write('  - Implementar connection pooling', 'light_gray');
            
        } catch (\Throwable $e) {
            CLI::write('✗ Erro ao gerar relatório: ' . $e->getMessage(), 'red');
        }
    }
}
