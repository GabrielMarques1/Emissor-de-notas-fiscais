<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuração do Sistema de Cache Multi-Tenant
 * 
 * Define TTLs, políticas de limpeza e configurações de segurança
 */
class TenantCache extends BaseConfig
{
    /**
     * TTL padrão em segundos (1 hora)
     */
    public int $defaultTTL = 3600;
    
    /**
     * Habilitar validação de tenant_id (proteção anti-poisoning)
     */
    public bool $enableTenantValidation = true;
    
    /**
     * Habilitar estatísticas de cache
     */
    public bool $enableStats = true;
    
    /**
     * Intervalo de operações para limpeza automática
     */
    public int $cleanupInterval = 100;
    
    /**
     * Tempo máximo sem uso antes de deletar (1 hora)
     */
    public int $maxUnusedTime = 3600;
    
    /**
     * TTLs específicos por tipo de chave
     */
    public array $ttls = [
        // Produtos
        'product:' => 3600,           // 1 hora
        'products:' => 3600,          // 1 hora
        'products_list' => 1800,      // 30 minutos
        'products_active' => 1800,    // 30 minutos
        'products_search' => 600,     // 10 minutos
        
        // Clientes
        'customer:' => 3600,          // 1 hora
        'customers:' => 3600,         // 1 hora
        'customers_list' => 1800,     // 30 minutos
        'customers_search' => 600,    // 10 minutos
        
        // Configurações
        'config:' => 86400,           // 24 horas
        'configuration:' => 86400,    // 24 horas
        'settings:' => 86400,         // 24 horas
        'tenant_config' => 86400,     // 24 horas
        
        // Dashboard
        'dashboard:' => 300,          // 5 minutos
        'dashboard_stats' => 300,     // 5 minutos
        'dashboard_summary' => 300,   // 5 minutos
        'dashboard_today' => 180,     // 3 minutos
        
        // Relatórios
        'report:' => 1800,            // 30 minutos
        'reports:' => 1800,           // 30 minutos
        'report_monthly' => 3600,     // 1 hora
        'report_daily' => 1800,       // 30 minutos
        
        // Sessão e usuários
        'session:' => 7200,           // 2 horas
        'user:' => 1800,              // 30 minutos
        'users:' => 1800,             // 30 minutos
        'user_permissions' => 3600,   // 1 hora
        
        // Queries e buscas
        'query:' => 600,              // 10 minutos
        'search:' => 600,             // 10 minutos
        'filter:' => 600,             // 10 minutos
        
        // Vendas
        'sale:' => 1800,              // 30 minutos
        'sales:' => 1800,             // 30 minutos
        'sales_today' => 300,         // 5 minutos
        'sales_month' => 1800,        // 30 minutos
        
        // Estoque
        'stock:' => 1800,             // 30 minutos
        'inventory:' => 1800,         // 30 minutos
        'low_stock' => 600,           // 10 minutos
        
        // Contadores
        'counter:' => 300,            // 5 minutos
        'count:' => 300,              // 5 minutos
        'total:' => 300,              // 5 minutos
        
        // Temporários
        'temp:' => 300,               // 5 minutos
        'tmp:' => 300,                // 5 minutos
        'cache:' => 600,              // 10 minutos
    ];
    
    /**
     * Configurações de limpeza automática
     */
    public array $cleanup = [
        'enabled' => true,
        'max_files_per_tenant' => 1000,      // Máximo de arquivos por tenant
        'max_size_mb' => 100,                // Máximo 100MB por tenant
        'cleanup_probability' => 10,         // 10% de chance a cada operação
        'force_cleanup_age_hours' => 24,     // Forçar limpeza após 24h
    ];
    
    /**
     * Configurações de segurança
     */
    public array $security = [
        'validate_tenant_id' => true,        // Sempre validar tenant_id
        'log_poisoning_attempts' => true,    // Log tentativas de poisoning
        'auto_delete_corrupted' => true,     // Deletar cache corrompido automaticamente
        'audit_cache_access' => false,       // Auditar acessos ao cache (pode impactar performance)
        'encrypt_sensitive_data' => false,   // Criptografar dados sensíveis (experimental)
    ];
    
    /**
     * Configurações de performance
     */
    public array $performance = [
        'enable_compression' => false,       // Comprimir dados do cache
        'compression_threshold' => 1024,     // Comprimir apenas dados > 1KB
        'max_key_length' => 250,            // Máximo caracteres na chave
        'serialize_method' => 'serialize',   // serialize, json, igbinary
        'file_locking' => true,              // Usar LOCK_EX ao escrever
    ];
    
    /**
     * Configurações de monitoramento
     */
    public array $monitoring = [
        'track_hit_rate' => true,            // Rastrear taxa de acerto
        'track_performance' => true,         // Rastrear tempo de operações
        'alert_low_hit_rate' => 50,         // Alertar se hit rate < 50%
        'alert_high_miss_rate' => 70,       // Alertar se miss rate > 70%
        'log_slow_operations' => true,      // Log operações > 100ms
        'slow_operation_threshold' => 100,  // Threshold em ms
    ];
    
    /**
     * Padrões de invalidação em grupo
     */
    public array $groupPatterns = [
        'products' => [
            'product:*',
            'products:*',
            'products_*',
            'dashboard_*',  // Dashboard pode depender de produtos
        ],
        
        'customers' => [
            'customer:*',
            'customers:*',
            'customers_*',
        ],
        
        'sales' => [
            'sale:*',
            'sales:*',
            'sales_*',
            'dashboard_*',  // Dashboard depende de vendas
            'report_*',     // Relatórios dependem de vendas
        ],
        
        'config' => [
            'config:*',
            'configuration:*',
            'settings:*',
            'tenant_config*',
        ],
        
        'dashboard' => [
            'dashboard:*',
            'dashboard_*',
        ],
        
        'reports' => [
            'report:*',
            'reports:*',
            'report_*',
        ],
        
        'users' => [
            'user:*',
            'users:*',
            'user_*',
            'session:*',
        ],
    ];
    
    /**
     * Obter TTL para uma chave específica
     */
    public function getTTLForKey(string $key): int
    {
        foreach ($this->ttls as $pattern => $ttl) {
            if (strpos($key, $pattern) === 0) {
                return $ttl;
            }
        }
        
        return $this->defaultTTL;
    }
    
    /**
     * Verificar se deve executar limpeza automática
     */
    public function shouldRunCleanup(): bool
    {
        if (!$this->cleanup['enabled']) {
            return false;
        }
        
        $probability = $this->cleanup['cleanup_probability'];
        return (rand(1, 100) <= $probability);
    }
    
    /**
     * Obter padrões de invalidação para um grupo
     */
    public function getGroupPatterns(string $group): array
    {
        return $this->groupPatterns[$group] ?? [];
    }
    
    /**
     * Verificar se uma chave corresponde a um padrão
     */
    public function matchesPattern(string $key, string $pattern): bool
    {
        // Converter padrão com * para regex
        $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
        return preg_match($regex, $key) === 1;
    }
    
    /**
     * Obter configuração de serialização
     */
    public function getSerializationMethod(): string
    {
        $method = $this->performance['serialize_method'] ?? 'serialize';
        
        // Verificar se método está disponível
        switch ($method) {
            case 'igbinary':
                if (!extension_loaded('igbinary')) {
                    return 'serialize';
                }
                break;
            case 'json':
                if (!function_exists('json_encode')) {
                    return 'serialize';
                }
                break;
        }
        
        return $method;
    }
    
    /**
     * Verificar se deve comprimir dados
     */
    public function shouldCompress($data): bool
    {
        if (!$this->performance['enable_compression']) {
            return false;
        }
        
        $serialized = serialize($data);
        $threshold = $this->performance['compression_threshold'];
        
        return strlen($serialized) > $threshold;
    }
    
    /**
     * Validar configuração
     */
    public function validate(): array
    {
        $errors = [];
        
        if ($this->defaultTTL < 1) {
            $errors[] = 'defaultTTL deve ser maior que 0';
        }
        
        if ($this->cleanupInterval < 1) {
            $errors[] = 'cleanupInterval deve ser maior que 0';
        }
        
        if ($this->maxUnusedTime < 60) {
            $errors[] = 'maxUnusedTime deve ser pelo menos 60 segundos';
        }
        
        foreach ($this->ttls as $pattern => $ttl) {
            if ($ttl < 1) {
                $errors[] = "TTL para padrão '{$pattern}' deve ser maior que 0";
            }
        }
        
        if ($this->performance['max_key_length'] < 10) {
            $errors[] = 'max_key_length deve ser pelo menos 10';
        }
        
        return $errors;
    }
    
    /**
     * Obter configuração otimizada para ambiente
     */
    public function getOptimizedConfig(string $environment = 'production'): array
    {
        $config = [
            'defaultTTL' => $this->defaultTTL,
            'enableStats' => $this->enableStats,
            'cleanupInterval' => $this->cleanupInterval,
        ];
        
        switch ($environment) {
            case 'development':
                $config['enableStats'] = true;
                $config['cleanupInterval'] = 10; // Limpeza mais frequente
                $config['security']['audit_cache_access'] = true;
                break;
                
            case 'testing':
                $config['defaultTTL'] = 60; // TTL curto para testes
                $config['cleanupInterval'] = 1; // Limpeza a cada operação
                $config['enableStats'] = true;
                break;
                
            case 'production':
                $config['enableStats'] = $this->monitoring['track_hit_rate'];
                $config['cleanupInterval'] = max(100, $this->cleanupInterval);
                $config['security']['audit_cache_access'] = false; // Performance
                break;
        }
        
        return $config;
    }
}
