<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration with Performance Optimizations
 */
class Database extends Config
{
    /**
     * Configuração principal do banco (cloud/produção)
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'root',
        'password'     => '',
        'database'     => 'erp_local',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,  // Persistent connections desabilitadas (multi-tenant)
        'DBDebug'      => true,   // Modo desenvolvimento
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_unicode_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => true,   // OTIMIZAÇÃO: Compressão de dados MySQL
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        
        // PERFORMANCE: Query caching
        'cacheOn'      => false,  // Usar cache da aplicação (não do MySQL)
        'cacheDir'     => '',
        
        // PERFORMANCE: Connection pooling
        'numberNative' => false,
        
        // SECURITY & PERFORMANCE
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];
    
    /**
     * Configuração secundária (backup local para modo offline)
     */
    public array $local_backup = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'root',
        'password'     => '',
        'database'     => 'erp_offline',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_unicode_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => true,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
    ];
    
    /**
     * Grupo padrão
     */
    public string $defaultGroup = 'default';
}
