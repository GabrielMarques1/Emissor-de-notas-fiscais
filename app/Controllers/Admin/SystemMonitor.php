<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SystemMonitor extends BaseController
{
    /**
     * Dashboard de monitoramento em tempo real
     */
    public function index()
    {
        // Verificar permissão admin
        $userType = session('tipo');
        if ($userType != 1 && $userType != '1') {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado - Apenas administradores');
        }
        
        $data = [
            'title' => 'Monitor do Sistema',
            'system_status' => $this->getSystemStatus(),
            'active_processes' => $this->getActiveProcesses(),
            'log_summary' => $this->getLogSummary(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ];
        
        return view('admin/system_monitor', $data);
    }
    
    /**
     * API: Status do sistema em tempo real
     */
    public function status()
    {
        try {
            $status = [
                'timestamp' => date('Y-m-d H:i:s'),
                'system' => $this->getSystemStatus(),
                'processes' => $this->getActiveProcesses(),
                'logs' => $this->getRecentLogs(),
                'performance' => $this->getPerformanceMetrics()
            ];
            
            return $this->response->setJSON($status);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Obter status geral do sistema
     */
    protected function getSystemStatus(): array
    {
        return [
            'uptime' => $this->getSystemUptime(),
            'disk_usage' => $this->getDiskUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'database_status' => $this->getDatabaseStatus(),
            'cache_status' => $this->getCacheStatus(),
            'backup_status' => $this->getBackupStatus()
        ];
    }
    
    /**
     * Obter processos ativos relacionados ao sistema
     */
    protected function getActiveProcesses(): array
    {
        $processes = [];
        
        // Verificar se há comandos spark rodando
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV', $output);
        } else {
            // Linux/Unix
            exec('ps aux | grep -E "(php.*spark|backup|cache)" | grep -v grep', $output);
        }
        
        foreach ($output as $line) {
            if (strpos($line, 'spark') !== false) {
                $processes[] = [
                    'command' => $line,
                    'type' => $this->identifyProcessType($line),
                    'status' => 'running'
                ];
            }
        }
        
        return $processes;
    }
    
    /**
     * Obter resumo dos logs recentes
     */
    protected function getLogSummary(): array
    {
        $logDir = WRITEPATH . 'logs/';
        $summary = [
            'errors_today' => 0,
            'warnings_today' => 0,
            'backups_today' => 0,
            'cache_cleans_today' => 0
        ];
        
        $today = date('Y-m-d');
        $logFile = $logDir . 'log-' . $today . '.log';
        
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $summary['errors_today'] = substr_count($content, 'ERROR');
            $summary['warnings_today'] = substr_count($content, 'WARNING');
            $summary['backups_today'] = substr_count($content, 'backup:');
            $summary['cache_cleans_today'] = substr_count($content, 'cache:clean');
        }
        
        return $summary;
    }
    
    /**
     * Obter logs recentes para API
     */
    protected function getRecentLogs(): array
    {
        $logDir = WRITEPATH . 'logs/';
        $today = date('Y-m-d');
        $logFile = $logDir . 'log-' . $today . '.log';
        
        $logs = [];
        
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES);
            $recentLines = array_slice($lines, -10); // Últimas 10 linhas
            
            foreach ($recentLines as $line) {
                if (preg_match('/(\w+) - (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) --> (.+)/', $line, $matches)) {
                    $logs[] = [
                        'level' => $matches[1],
                        'timestamp' => $matches[2],
                        'message' => $matches[3]
                    ];
                }
            }
        }
        
        return $logs;
    }
    
    /**
     * Obter métricas de performance
     */
    protected function getPerformanceMetrics(): array
    {
        return [
            'php_memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'php_memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'php_memory_limit' => ini_get('memory_limit'),
            'execution_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3),
            'loaded_extensions' => count(get_loaded_extensions()),
            'active_sessions' => $this->getActiveSessionsCount()
        ];
    }
    
    /**
     * Utilitários privados
     */
    private function getSystemUptime(): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'N/A (Windows)';
        } else {
            $uptime = shell_exec('uptime -p');
            return trim($uptime ?: 'N/A');
        }
    }
    
    private function getDiskUsage(): array
    {
        $bytes = disk_free_space('.');
        $total = disk_total_space('.');
        
        return [
            'free_gb' => round($bytes / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_percent' => round((($total - $bytes) / $total) * 100, 1)
        ];
    }
    
    private function getMemoryUsage(): array
    {
        return [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit')
        ];
    }
    
    private function getDatabaseStatus(): string
    {
        try {
            $db = \Config\Database::connect();
            $query = $db->query('SELECT 1');
            return $query ? 'online' : 'offline';
        } catch (\Exception $e) {
            return 'offline';
        }
    }
    
    private function getCacheStatus(): string
    {
        $cacheDir = WRITEPATH . 'cache/';
        return is_writable($cacheDir) ? 'active' : 'inactive';
    }
    
    private function getBackupStatus(): array
    {
        $backupDir = WRITEPATH . 'backups/';
        $files = glob($backupDir . '*.sql.enc');
        
        return [
            'total_backups' => count($files),
            'last_backup' => $files ? date('Y-m-d H:i:s', filemtime(end($files))) : 'N/A',
            'directory_writable' => is_writable($backupDir)
        ];
    }
    
    private function identifyProcessType(string $command): string
    {
        if (strpos($command, 'backup:') !== false) return 'backup';
        if (strpos($command, 'cache:') !== false) return 'cache';
        if (strpos($command, 'sync:') !== false) return 'sync';
        return 'other';
    }
    
    private function getActiveSessionsCount(): int
    {
        $sessionDir = WRITEPATH . 'session/';
        if (is_dir($sessionDir)) {
            return count(glob($sessionDir . 'ci_session*'));
        }
        return 0;
    }
}
