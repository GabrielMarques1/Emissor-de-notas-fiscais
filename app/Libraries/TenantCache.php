<?php

namespace App\Libraries;

use Config\TenantCache as TenantCacheConfig;
use Exception;

/**
 * Sistema de Cache Multi-Tenant Baseado em Arquivo
 * 
 * Isolamento completo de cache por tenant com proteção anti-poisoning
 * Performance otimizada com validação de integridade
 */
class TenantCache
{
    /**
     * Configuração do cache
     */
    protected TenantCacheConfig $config;
    
    /**
     * ID do tenant atual
     */
    protected string $tenantId;
    
    /**
     * Prefixo para chaves do cache
     */
    protected string $tenantPrefix;
    
    /**
     * Diretório base do cache
     */
    protected string $cacheDir;
    
    /**
     * Estatísticas de uso
     */
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'saves' => 0,
        'deletes' => 0,
        'poisoning_detected' => 0
    ];
    
    /**
     * Contador de operações para limpeza automática
     */
    protected int $operationCount = 0;
    
    public function __construct(?int $idContador = null, ?int $idEmpresa = null)
    {
        try {
            $this->config = new TenantCacheConfig();
            
            // Resolver tenant automaticamente se não fornecido
            if ($idContador === null || $idEmpresa === null) {
                [$idContador, $idEmpresa] = $this->resolveTenantIds();
            }
            
            $this->tenantId = "{$idContador}:{$idEmpresa}";
            $this->tenantPrefix = "tenant:{$idContador}:{$idEmpresa}:";
            $this->cacheDir = WRITEPATH . 'cache/';
            
            // Criar diretório se não existir
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0755, true);
            }
            
            log_message('debug', '[TenantCache] Inicializado para tenant', [
                'tenant' => $this->tenantId,
                'prefix' => $this->tenantPrefix,
                'cache_dir' => $this->cacheDir
            ]);
            
        } catch (Exception $e) {
            log_message('error', '[TenantCache] Erro na inicialização', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Salvar dados no cache com validação de tenant
     */
    public function save(string $key, $value, ?int $ttl = null): bool
    {
        return $this->set($key, $value, $ttl);
    }
    
    /**
     * Armazena dados no cache com isolamento de tenant
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        try {
            $this->incrementOperationCount();
            
            $ttl = $ttl ?? $this->getTTLForKey($key);
            $cacheData = $this->buildCacheData($value, $ttl);
            $filename = $this->getCacheFilename($key);
            
            $success = file_put_contents($filename, serialize($cacheData), LOCK_EX) !== false;
            
            if ($success) {
                chmod($filename, 0644);
                $this->stats['saves']++;
                
                log_message('debug', '[TenantCache] Cache salvo', [
                    'key' => $key,
                    'filename' => basename($filename),
                    'ttl' => $ttl,
                    'tenant' => $this->tenantId
                ]);
            }
            
            return $success;
            
        } catch (Exception $e) {
            log_message('error', '[TenantCache] Erro ao salvar cache', [
                'key' => $key,
                'tenant' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Recupera dados do cache com proteção anti-poisoning
     */
    public function get(string $key, $default = null)
    {
        try {
            $this->incrementOperationCount();
            
            $filename = $this->getCacheFilename($key);
            
            if (!file_exists($filename)) {
                $this->stats['misses']++;
                log_message('debug', '[TenantCache] Cache miss - arquivo não existe', [
                    'key' => $key,
                    'tenant' => $this->tenantId
                ]);
                return $default;
            }
            
            $serializedData = file_get_contents($filename);
            if ($serializedData === false) {
                $this->stats['misses']++;
                return $default;
            }
            
            $cacheData = unserialize($serializedData);
            
            // Validação anti-poisoning
            if (!$this->validateCacheData($cacheData)) {
                $this->handleCachePoisoning($key, $filename);
                return $default;
            }
            
            // Verificar expiração
            if ($this->isCacheExpired($cacheData)) {
                unlink($filename);
                $this->stats['misses']++;
                log_message('debug', '[TenantCache] Cache expirado', [
                    'key' => $key,
                    'tenant' => $this->tenantId
                ]);
                return $default;
            }
            
            $this->stats['hits']++;
            log_message('debug', '[TenantCache] Cache hit', [
                'key' => $key,
                'tenant' => $this->tenantId
            ]);
            
            return $cacheData['value'];
            
        } catch (Exception $e) {
            log_message('error', '[TenantCache] Erro ao recuperar cache', [
                'key' => $key,
                'tenant' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }
    
    /**
     * Remove item do cache
     */
    public function delete(string $key): bool
    {
        try {
            $filename = $this->getCacheFilename($key);
            
            if (file_exists($filename)) {
                $success = unlink($filename);
                if ($success) {
                    $this->stats['deletes']++;
                    log_message('debug', '[TenantCache] Cache deletado', [
                        'key' => $key,
                        'tenant' => $this->tenantId
                    ]);
                }
                return $success;
            }
            
            return true; // Já não existe
            
        } catch (Exception $e) {
            log_message('error', '[TenantCache] Erro ao deletar cache', [
                'key' => $key,
                'tenant' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Limpa todo o cache do tenant
     */
    public function flush(): bool
    {
        try {
            $pattern = $this->cacheDir . $this->sanitizeTenantId($this->tenantId) . '_*.cache';
            $files = glob($pattern);
            $deleted = 0;
            
            foreach ($files as $file) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
            
            log_message('info', '[TenantCache] Cache do tenant limpo', [
                'tenant' => $this->tenantId,
                'files_deleted' => $deleted
            ]);
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', '[TenantCache] Erro ao limpar cache', [
                'tenant' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Cache com callback para geração automática
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $data = $this->get($key);
        
        if ($data === null) {
            $data = $callback();
            if ($data !== null) {
                $this->set($key, $data, $ttl);
            }
        }
        
        return $data;
    }
    
    /**
     * Incrementar contador
     */
    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }
    
    /**
     * Decrementar contador
     */
    public function decrement(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = max(0, $current - $value);
        $this->set($key, $new);
        return $new;
    }
    
    /**
     * Invalidar grupo de cache
     */
    public function invalidateGroup(string $group): int
    {
        try {
            $pattern = $this->cacheDir . $this->sanitizeTenantId($this->tenantId) . '_' . $group . '_*.cache';
            $files = glob($pattern);
            $deleted = 0;
            
            foreach ($files as $file) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
            
            log_message('debug', '[TenantCache] Grupo invalidado', [
                'group' => $group,
                'tenant' => $this->tenantId,
                'files_deleted' => $deleted
            ]);
            
            return $deleted;
            
        } catch (Exception $e) {
            log_message('error', '[TenantCache] Erro ao invalidar grupo', [
                'group' => $group,
                'tenant' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Obter múltiplos valores
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }
    
    /**
     * Salvar múltiplos valores
     */
    public function saveMultiple(array $values, ?int $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * Obter estatísticas de uso
     */
    public function getStats(): array
    {
        $pattern = $this->cacheDir . $this->sanitizeTenantId($this->tenantId) . '_*.cache';
        $files = glob($pattern);
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
        }
        
        $hitRate = ($this->stats['hits'] + $this->stats['misses']) > 0 
                  ? ($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses'])) * 100 
                  : 0;
        
        return [
            'tenant_id' => $this->tenantId,
            'cache_files' => count($files),
            'total_size_bytes' => $totalSize,
            'total_size_formatted' => $this->formatFileSize($totalSize),
            'hit_rate_percent' => round($hitRate, 2),
            'stats' => $this->stats,
            'oldest_cache' => $oldestFile ? date('Y-m-d H:i:s', filemtime($oldestFile)) : null,
            'newest_cache' => $newestFile ? date('Y-m-d H:i:s', filemtime($newestFile)) : null
        ];
    }
    
    /**
     * Construir dados do cache com metadados
     */
    protected function buildCacheData($value, int $ttl): array
    {
        return [
            '_tenant_id' => $this->tenantId,
            '_cached_at' => time(),
            '_expires_at' => time() + $ttl,
            '_ttl' => $ttl,
            'value' => $value
        ];
    }
    
    /**
     * Validar dados do cache (anti-poisoning)
     */
    protected function validateCacheData($cacheData): bool
    {
        if (!is_array($cacheData)) {
            return false;
        }
        
        if (!isset($cacheData['_tenant_id']) || $cacheData['_tenant_id'] !== $this->tenantId) {
            return false;
        }
        
        if (!isset($cacheData['_cached_at']) || !isset($cacheData['_expires_at'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verificar se cache expirou
     */
    protected function isCacheExpired($cacheData): bool
    {
        return time() > $cacheData['_expires_at'];
    }
    
    /**
     * Tratar cache poisoning
     */
    protected function handleCachePoisoning(string $key, string $filename): void
    {
        $this->stats['poisoning_detected']++;
        
        // Deletar arquivo corrompido
        if (file_exists($filename)) {
            unlink($filename);
        }
        
        // Log de segurança
        log_message('critical', '[TenantCache] CACHE POISONING DETECTADO', [
            'key' => $key,
            'tenant' => $this->tenantId,
            'filename' => basename($filename),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Registrar no audit de segurança se disponível
        if (function_exists('audit_security')) {
            audit_security('cache_poisoning', [
                'cache_key' => $key,
                'tenant_id' => $this->tenantId,
                'filename' => basename($filename)
            ]);
        }
    }
    
    /**
     * Obter nome do arquivo de cache
     */
    protected function getCacheFilename(string $key): string
    {
        $sanitizedTenant = $this->sanitizeTenantId($this->tenantId);
        $sanitizedKey = $this->sanitizeKey($key);
        return $this->cacheDir . "{$sanitizedTenant}_{$sanitizedKey}.cache";
    }
    
    /**
     * Sanitizar tenant ID para nome de arquivo
     */
    protected function sanitizeTenantId(string $tenantId): string
    {
        return str_replace([':', '/', '\\', ' '], '_', $tenantId);
    }
    
    /**
     * Sanitizar chave para nome de arquivo
     */
    protected function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }
    
    /**
     * Obter TTL para uma chave
     */
    protected function getTTLForKey(string $key): int
    {
        $ttls = $this->config->ttls ?? [];
        
        foreach ($ttls as $pattern => $ttl) {
            if (strpos($key, $pattern) === 0) {
                return $ttl;
            }
        }
        
        return $this->config->defaultTTL ?? 3600;
    }
    
    /**
     * Incrementar contador de operações e executar limpeza se necessário
     */
    protected function incrementOperationCount(): void
    {
        $this->operationCount++;
        
        $cleanupInterval = $this->config->cleanupInterval ?? 100;
        if ($this->operationCount % $cleanupInterval === 0) {
            $this->cleanupExpiredCache();
        }
    }
    
    /**
     * Limpeza automática de cache expirado
     */
    protected function cleanupExpiredCache(): void
    {
        try {
            $pattern = $this->cacheDir . $this->sanitizeTenantId($this->tenantId) . '_*.cache';
            $files = glob($pattern);
            $cleaned = 0;
            $maxAge = $this->config->maxUnusedTime ?? 3600;
            
            foreach ($files as $file) {
                if (time() - filemtime($file) > $maxAge) {
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }
            
            if ($cleaned > 0) {
                log_message('debug', '[TenantCache] Limpeza automática executada', [
                    'tenant' => $this->tenantId,
                    'files_cleaned' => $cleaned
                ]);
            }
            
        } catch (Exception $e) {
            log_message('error', '[TenantCache] Erro na limpeza automática', [
                'tenant' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Resolver tenant IDs da sessão
     */
    protected function resolveTenantIds(): array
    {
        try {
            if (function_exists('resolve_tenant_ids')) {
                return resolve_tenant_ids();
            }
            
            $session = session();
            if ($session) {
                return [
                    (int) ($session->get('id_contador') ?? 0),
                    (int) ($session->get('id_empresa') ?? 0)
                ];
            }
            
            // Fallback para CLI ou contextos sem sessão
            return [0, 0];
            
        } catch (Exception $e) {
            log_message('warning', '[TenantCache] Erro ao resolver tenant IDs', [
                'error' => $e->getMessage()
            ]);
            return [0, 0];
        }
    }
    
    /**
     * Formatar tamanho de arquivo
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
