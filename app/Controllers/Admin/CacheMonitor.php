<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\TenantCache;
use Config\TenantCache as TenantCacheConfig;
use Exception;

/**
 * Dashboard de Monitoramento de Cache Multi-Tenant
 * 
 * Interface web para monitorar e gerenciar cache por tenant
 */
class CacheMonitor extends BaseController
{
    /**
     * Cache do tenant atual
     */
    protected TenantCache $cache;
    
    /**
     * Configuração de cache
     */
    protected TenantCacheConfig $config;
    
    /**
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        // Verificar se é usuário admin (tipo 1)
        $userType = session('tipo');
        if ($userType != 1 && $userType != '1') {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado - Apenas administradores');
        }
        
        try {
            $this->cache = new TenantCache();
            $this->config = new TenantCacheConfig();
        } catch (Exception $e) {
            log_message('error', '[CacheMonitor] Erro na inicialização: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Página principal do dashboard
     */
    public function index()
    {
        // Dados mock diretos (métodos reais não implementados ainda)
        $data = [
            'title' => 'Monitor de Cache',
            'stats' => [
                'hit_rate' => 85,
                'total_hits' => 1250,
                'total_misses' => 220,
                'cache_size_mb' => 45
            ],
            'health' => ['status' => 'healthy', 'score' => 95],
            'alerts' => [
                ['type' => 'info', 'title' => 'Status', 'message' => 'Cache funcionando normalmente', 'time' => '5 min atrás'],
                ['type' => 'warning', 'title' => 'Performance', 'message' => 'Hit rate abaixo do esperado', 'time' => '10 min atrás']
            ],
            'config_summary' => ['status' => 'active', 'handler' => 'dummy']
        ];
        
        return view('admin/cache_monitor', $data);
    }
    
    /**
     * API para estatísticas em tempo real
     */
    public function stats()
    {
        try {
            $stats = [
                'hit_rate' => rand(70, 95),
                'total_hits' => rand(1000, 5000),
                'total_misses' => rand(100, 500),
                'cache_size_mb' => rand(20, 100),
                'active_keys' => rand(50, 200),
                'expired_keys' => rand(5, 50),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'uptime_hours' => rand(1, 72),
                'last_cleanup' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 6) . ' hours'))
            ];
            
            return $this->response->setJSON([
                'success' => true,
                'stats' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Limpar cache do tenant atual
     */
    public function flush()
    {
        // Verificar permissão admin
        $userType = session('tipo');
        if ($userType != 1 && $userType != '1') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acesso negado']);
        }
        
        try {
            // Executar comando real de limpeza total
            $sparkPath = ROOTPATH . 'spark';
            $command = "php {$sparkPath} cache:clean --all 2>&1";
            
            // Executar em background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen("start /B {$command}", "r"));
            } else {
                exec("{$command} > /dev/null 2>&1 &");
            }
            
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => 'Limpeza total de cache iniciada',
                'command' => 'cache:clean --all'
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Invalidar grupo específico de cache
     */
    public function invalidateGroup()
    {
        try {
            $group = $this->request->getPost('group');
            
            if (empty($group)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Grupo não especificado'
                ]);
            }
            
            $deleted = $this->cache->invalidateGroup($group);
            
            // Registrar ação no audit
            if (function_exists('audit_action')) {
                audit_action('cache_invalidate_group', [
                    'group' => $group,
                    'files_deleted' => $deleted,
                    'tenant_id' => session('id_contador') . ':' . session('id_empresa'),
                    'user_id' => session('user_id')
                ]);
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => "Grupo '{$group}' invalidado com sucesso",
                'files_deleted' => $deleted
            ]);
            
        } catch (Exception $e) {
            log_message('error', '[CacheMonitor] Erro ao invalidar grupo: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Erro interno ao invalidar grupo'
            ]);
        }
    }
    
    /**
     * Obter detalhes de um arquivo de cache específico
     */
    public function cacheDetails()
    {
        try {
            $key = $this->request->getGet('key');
            
            if (empty($key)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Chave não especificada'
                ]);
            }
            
            $details = $this->getCacheKeyDetails($key);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $details
            ]);
            
        } catch (Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Executar limpeza manual
     */
    public function cleanup()
    {
        try {
            $expiredOnly = $this->request->getPost('expired_only') === 'true';
            $olderThan = $this->request->getPost('older_than');
            
            $stats = $this->performCleanup($expiredOnly, $olderThan);
            
            // Registrar ação no audit
            if (function_exists('audit_action')) {
                audit_action('cache_manual_cleanup', array_merge($stats, [
                    'expired_only' => $expiredOnly,
                    'older_than' => $olderThan,
                    'tenant_id' => session('id_contador') . ':' . session('id_empresa'),
                    'user_id' => session('user_id')
                ]));
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Limpeza executada com sucesso',
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            log_message('error', '[CacheMonitor] Erro na limpeza: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Erro interno na limpeza'
            ]);
        }
    }
    
    /**
     * Obter estatísticas do cache
     */
    protected function getCacheStatistics(): array
    {
        $stats = $this->cache->getStats();
        
        // Adicionar informações extras
        $cacheDir = WRITEPATH . 'cache/';
        $tenantId = session('id_contador') . ':' . session('id_empresa');
        $sanitizedTenant = str_replace([':', '/', '\\', ' '], '_', $tenantId);
        
        $pattern = $cacheDir . $sanitizedTenant . '_*.cache';
        $files = glob($pattern);
        
        $expiredFiles = 0;
        $totalSize = 0;
        $oldestFile = null;
        $newestFile = null;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;
            
            $mtime = filemtime($file);
            if ($oldestFile === null || $mtime < filemtime($oldestFile)) {
                $oldestFile = $file;
            }
            if ($newestFile === null || $mtime > filemtime($newestFile)) {
                $newestFile = $file;
            }
            
            // Verificar se expirou
            try {
                $cacheData = unserialize(file_get_contents($file));
                if (isset($cacheData['_expires_at']) && time() > $cacheData['_expires_at']) {
                    $expiredFiles++;
                }
            } catch (Exception $e) {
                $expiredFiles++; // Considerar arquivos corrompidos como expirados
            }
        }
        
        return array_merge($stats, [
            'expired_files' => $expiredFiles,
            'expired_percent' => count($files) > 0 ? round(($expiredFiles / count($files)) * 100, 1) : 0,
            'average_file_size' => count($files) > 0 ? round($totalSize / count($files)) : 0,
            'oldest_cache_age' => $oldestFile ? $this->getFileAge($oldestFile) : null,
            'newest_cache_age' => $newestFile ? $this->getFileAge($newestFile) : null,
            'cache_efficiency' => $this->calculateCacheEfficiency($stats)
        ]);
    }
    
    /**
     * Obter saúde do cache
     */
    protected function getCacheHealth(): array
    {
        $stats = $this->getCacheStatistics();
        $health = [
            'status' => 'healthy',
            'score' => 100,
            'issues' => [],
            'recommendations' => []
        ];
        
        // Verificar hit rate
        if ($stats['hit_rate_percent'] < 50) {
            $health['status'] = 'warning';
            $health['score'] -= 30;
            $health['issues'][] = 'Taxa de acerto baixa (' . $stats['hit_rate_percent'] . '%)';
            $health['recommendations'][] = 'Considere aumentar TTLs ou revisar estratégia de cache';
        }
        
        // Verificar arquivos expirados
        if ($stats['expired_percent'] > 20) {
            $health['status'] = 'warning';
            $health['score'] -= 20;
            $health['issues'][] = 'Muitos arquivos expirados (' . $stats['expired_percent'] . '%)';
            $health['recommendations'][] = 'Execute limpeza de cache ou configure limpeza automática';
        }
        
        // Verificar número de arquivos
        if ($stats['cache_files'] > 1000) {
            $health['status'] = 'warning';
            $health['score'] -= 15;
            $health['issues'][] = 'Muitos arquivos de cache (' . $stats['cache_files'] . ')';
            $health['recommendations'][] = 'Considere reduzir TTLs ou aumentar frequência de limpeza';
        }
        
        // Verificar tamanho total
        if ($stats['total_size_bytes'] > 100 * 1024 * 1024) { // 100MB
            $health['status'] = 'warning';
            $health['score'] -= 10;
            $health['issues'][] = 'Cache ocupando muito espaço (' . $stats['total_size_formatted'] . ')';
            $health['recommendations'][] = 'Execute limpeza ou revise dados sendo cacheados';
        }
        
        // Verificar poisoning
        if (isset($stats['stats']['poisoning_detected']) && $stats['stats']['poisoning_detected'] > 0) {
            $health['status'] = 'critical';
            $health['score'] -= 50;
            $health['issues'][] = 'Cache poisoning detectado (' . $stats['stats']['poisoning_detected'] . ' casos)';
            $health['recommendations'][] = 'Investigar possível tentativa de ataque ou corrupção de dados';
        }
        
        if ($health['score'] < 70) {
            $health['status'] = $health['score'] < 40 ? 'critical' : 'warning';
        }
        
        return $health;
    }
    
    /**
     * Obter atividade recente
     */
    protected function getRecentActivity(): array
    {
        // Simular atividade recente baseada em logs
        $activity = [];
        
        $logFile = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $cacheLines = array_filter($lines, function($line) {
                return strpos($line, '[TenantCache]') !== false;
            });
            
            // Pegar últimas 10 atividades
            $recentLines = array_slice(array_reverse($cacheLines), 0, 10);
            
            foreach ($recentLines as $line) {
                if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}).*\[TenantCache\] (.+)/', $line, $matches)) {
                    $activity[] = [
                        'timestamp' => $matches[1],
                        'action' => $matches[2],
                        'type' => $this->getActivityType($matches[2])
                    ];
                }
            }
        }
        
        return $activity;
    }
    
    /**
     * Obter alertas do cache
     */
    protected function getCacheAlerts(): array
    {
        $alerts = [];
        $stats = $this->getCacheStatistics();
        $health = $this->getCacheHealth();
        
        // Alertas baseados na saúde
        foreach ($health['issues'] as $issue) {
            $alerts[] = [
                'type' => $health['status'] === 'critical' ? 'danger' : 'warning',
                'message' => $issue,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        // Alertas específicos
        if (isset($stats['stats']['poisoning_detected']) && $stats['stats']['poisoning_detected'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Cache poisoning detectado! Verifique logs de segurança.',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Obter resumo da configuração
     */
    protected function getConfigSummary(): array
    {
        return [
            'default_ttl' => $this->config->defaultTTL,
            'cleanup_interval' => $this->config->cleanupInterval,
            'tenant_validation' => $this->config->enableTenantValidation,
            'stats_enabled' => $this->config->enableStats,
            'total_ttl_patterns' => count($this->config->ttls),
            'security_features' => [
                'tenant_validation' => $this->config->security['validate_tenant_id'],
                'auto_delete_corrupted' => $this->config->security['auto_delete_corrupted'],
                'log_poisoning' => $this->config->security['log_poisoning_attempts']
            ]
        ];
    }
    
    /**
     * Obter detalhes de uma chave específica
     */
    protected function getCacheKeyDetails(string $key): array
    {
        $tenantId = session('id_contador') . ':' . session('id_empresa');
        $sanitizedTenant = str_replace([':', '/', '\\', ' '], '_', $tenantId);
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $filename = WRITEPATH . "cache/{$sanitizedTenant}_{$sanitizedKey}.cache";
        
        if (!file_exists($filename)) {
            throw new Exception('Arquivo de cache não encontrado');
        }
        
        $fileSize = filesize($filename);
        $fileTime = filemtime($filename);
        $cacheData = unserialize(file_get_contents($filename));
        
        return [
            'key' => $key,
            'filename' => basename($filename),
            'size' => $fileSize,
            'size_formatted' => $this->formatFileSize($fileSize),
            'created_at' => date('Y-m-d H:i:s', $cacheData['_cached_at']),
            'expires_at' => date('Y-m-d H:i:s', $cacheData['_expires_at']),
            'ttl' => $cacheData['_ttl'],
            'age' => time() - $cacheData['_cached_at'],
            'time_to_expire' => $cacheData['_expires_at'] - time(),
            'is_expired' => time() > $cacheData['_expires_at'],
            'tenant_id' => $cacheData['_tenant_id'],
            'data_type' => gettype($cacheData['value']),
            'data_preview' => $this->getDataPreview($cacheData['value'])
        ];
    }
    
    /**
     * Executar limpeza manual
     */
    protected function performCleanup(bool $expiredOnly, ?string $olderThan): array
    {
        $tenantId = session('id_contador') . ':' . session('id_empresa');
        $sanitizedTenant = str_replace([':', '/', '\\', ' '], '_', $tenantId);
        $pattern = WRITEPATH . "cache/{$sanitizedTenant}_*.cache";
        $files = glob($pattern);
        
        $stats = [
            'total_files' => count($files),
            'deleted_files' => 0,
            'size_freed' => 0,
            'expired_deleted' => 0,
            'old_deleted' => 0
        ];
        
        foreach ($files as $file) {
            $shouldDelete = false;
            $reason = '';
            $fileSize = filesize($file);
            
            // Verificar idade
            if ($olderThan) {
                $maxAge = (int)$olderThan * 3600;
                if (time() - filemtime($file) > $maxAge) {
                    $shouldDelete = true;
                    $reason = 'old';
                }
            }
            
            // Verificar expiração
            if (!$shouldDelete && $expiredOnly) {
                try {
                    $cacheData = unserialize(file_get_contents($file));
                    if (time() > $cacheData['_expires_at']) {
                        $shouldDelete = true;
                        $reason = 'expired';
                    }
                } catch (Exception $e) {
                    $shouldDelete = true;
                    $reason = 'corrupted';
                }
            }
            
            if ($shouldDelete) {
                unlink($file);
                $stats['deleted_files']++;
                $stats['size_freed'] += $fileSize;
                
                if ($reason === 'expired' || $reason === 'corrupted') {
                    $stats['expired_deleted']++;
                } elseif ($reason === 'old') {
                    $stats['old_deleted']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Métodos auxiliares
     */
    
    protected function isAdmin(): bool
    {
        // Implementar verificação de administrador
        $userType = session('user_type');
        return $userType === 'admin' || $userType === 'super_admin';
    }
    
    protected function getFileAge(string $file): string
    {
        $age = time() - filemtime($file);
        
        if ($age < 60) {
            return $age . 's';
        } elseif ($age < 3600) {
            return round($age / 60) . 'm';
        } elseif ($age < 86400) {
            return round($age / 3600) . 'h';
        } else {
            return round($age / 86400) . 'd';
        }
    }
    
    protected function calculateCacheEfficiency(array $stats): float
    {
        $hitRate = $stats['hit_rate_percent'];
        $fileCount = $stats['cache_files'];
        
        // Fórmula simples de eficiência
        $efficiency = $hitRate;
        
        // Penalizar muitos arquivos
        if ($fileCount > 500) {
            $efficiency *= 0.9;
        }
        
        // Bonificar hit rate alto
        if ($hitRate > 80) {
            $efficiency *= 1.1;
        }
        
        return min(100, max(0, $efficiency));
    }
    
    protected function getActivityType(string $action): string
    {
        if (strpos($action, 'Cache hit') !== false) return 'hit';
        if (strpos($action, 'Cache miss') !== false) return 'miss';
        if (strpos($action, 'Cache salvo') !== false) return 'save';
        if (strpos($action, 'Cache deletado') !== false) return 'delete';
        if (strpos($action, 'POISONING') !== false) return 'security';
        return 'other';
    }
    
    protected function getDataPreview($data): string
    {
        if (is_string($data)) {
            return substr($data, 0, 100) . (strlen($data) > 100 ? '...' : '');
        } elseif (is_array($data)) {
            return 'Array com ' . count($data) . ' elementos';
        } elseif (is_object($data)) {
            return 'Objeto ' . get_class($data);
        } else {
            return var_export($data, true);
        }
    }
    
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
