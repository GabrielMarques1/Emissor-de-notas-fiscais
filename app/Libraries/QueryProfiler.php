<?php

namespace App\Libraries;

/**
 * Query Profiler para Análise de Performance
 * 
 * Uso:
 * $profiler = new QueryProfiler();
 * $profiler->start('busca_produtos');
 * // ... query ...
 * $profiler->end('busca_produtos');
 * $profiler->report();
 */
class QueryProfiler
{
    protected array $queries = [];
    protected array $timers = [];
    protected int $totalQueries = 0;
    protected float $totalTime = 0;
    
    /**
     * Iniciar profiling de uma query
     */
    public function start(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }
    
    /**
     * Finalizar profiling de uma query
     */
    public function end(string $name, ?string $sql = null): void
    {
        if (!isset($this->timers[$name])) {
            return;
        }
        
        $duration = microtime(true) - $this->timers[$name];
        
        $this->queries[] = [
            'name' => $name,
            'duration' => $duration,
            'sql' => $sql,
            'memory' => memory_get_usage(true),
        ];
        
        $this->totalQueries++;
        $this->totalTime += $duration;
        
        unset($this->timers[$name]);
    }
    
    /**
     * Gerar relatório de performance
     */
    public function report(): array
    {
        // Ordenar por duração (mais lentas primeiro)
        $sorted = $this->queries;
        usort($sorted, function($a, $b) {
            return $b['duration'] <=> $a['duration'];
        });
        
        return [
            'total_queries' => $this->totalQueries,
            'total_time' => round($this->totalTime * 1000, 2) . 'ms',
            'avg_time' => $this->totalQueries > 0 
                ? round(($this->totalTime / $this->totalQueries) * 1000, 2) . 'ms'
                : '0ms',
            'slowest_queries' => array_slice($sorted, 0, 10),
            'queries' => $this->queries,
        ];
    }
    
    /**
     * Logar relatório
     */
    public function log(): void
    {
        $report = $this->report();
        
        log_message('info', '[QueryProfiler] Total Queries: ' . $report['total_queries']);
        log_message('info', '[QueryProfiler] Total Time: ' . $report['total_time']);
        log_message('info', '[QueryProfiler] Avg Time: ' . $report['avg_time']);
        
        foreach ($report['slowest_queries'] as $i => $query) {
            $duration = round($query['duration'] * 1000, 2);
            log_message('warning', "[QueryProfiler] #{$i} {$query['name']}: {$duration}ms");
            
            if ($query['sql']) {
                log_message('debug', "[QueryProfiler] SQL: {$query['sql']}");
            }
        }
    }
    
    /**
     * Resetar profiler
     */
    public function reset(): void
    {
        $this->queries = [];
        $this->timers = [];
        $this->totalQueries = 0;
        $this->totalTime = 0;
    }
    
    /**
     * Analisar query com EXPLAIN
     */
    public function explain(string $sql): array
    {
        $db = \Config\Database::connect();
        
        try {
            $result = $db->query("EXPLAIN {$sql}")->getResultArray();
            
            return [
                'sql' => $sql,
                'explain' => $result,
                'uses_index' => $this->checkIndexUsage($result),
                'estimated_rows' => $this->getEstimatedRows($result),
            ];
        } catch (\Exception $e) {
            return [
                'sql' => $sql,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Verificar se query usa índice
     */
    protected function checkIndexUsage(array $explainResult): bool
    {
        foreach ($explainResult as $row) {
            if ($row['key'] === null || $row['key'] === '') {
                return false; // Não usa índice
            }
        }
        
        return true;
    }
    
    /**
     * Obter número estimado de rows
     */
    protected function getEstimatedRows(array $explainResult): int
    {
        $total = 0;
        
        foreach ($explainResult as $row) {
            $total += (int) ($row['rows'] ?? 0);
        }
        
        return $total;
    }
}

