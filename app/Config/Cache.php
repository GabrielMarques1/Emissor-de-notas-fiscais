<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cache Configuration with Multi-Tenant Isolation
 */
class Cache extends BaseConfig
{
    /**
     * Handler primário: Redis para performance multi-tenant
     * DESENVOLVIMENTO: file cache como fallback
     * TEMPORÁRIO: dummy apenas até implementar Redis adequadamente
     * IMPORTANTE: Não compromete segurança, apenas performance
     */
    public string $handler = 'dummy';
    
    /**
     * Diretório para file cache
     */
    public string $storePath = WRITEPATH . 'cache/';
    
    /**
     * Backup handler (fallback)
     */
    public string $backupHandler = 'file';
    
    /**
     * Valid cache handlers
     * OBRIGATÓRIO para CodeIgniter 4
     */
    public array $validHandlers = [
        'dummy'     => \CodeIgniter\Cache\Handlers\DummyHandler::class,
        'file'      => \CodeIgniter\Cache\Handlers\FileHandler::class,
        'memcached' => \CodeIgniter\Cache\Handlers\MemcachedHandler::class,
        'predis'    => \CodeIgniter\Cache\Handlers\PredisHandler::class,
        'redis'     => \CodeIgniter\Cache\Handlers\RedisHandler::class,
        'wincache'  => \CodeIgniter\Cache\Handlers\WincacheHandler::class,
    ];
    
    /**
     * File cache settings
     */
    public array $file = [
        'storePath' => WRITEPATH . 'cache/',
        'mode'      => 0640,
    ];
    
    /**
     * Memcached settings (para produção)
     */
    public array $memcached = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];
    
    /**
     * Redis settings (recomendado para multi-tenant)
     */
    public array $redis = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];
    
    /**
     * Prefixo de chave para isolamento
     * IMPORTANTE: Sempre incluir tenant_id nas chaves de cache
     * Exemplo: "produto_barcode_{$idEmpresa}_{$idContador}_{$ean}"
     */
    public string $prefix = '';
    
    /**
     * TTL padrão (segundos)
     */
    public int $ttl = 60;
    
    /**
     * Reserved characters for cache keys (não usar)
     */
    public string $reservedCharacters = '{}()/\@:';
    
    /**
     * Cache query string
     * Se true, considera query strings diferentes como páginas diferentes
     */
    public bool $cacheQueryString = false;
}
