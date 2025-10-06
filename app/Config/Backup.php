<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuração Avançada de Backup Multi-Tenant
 * 
 * Define configurações para backup, storage remoto, rotação e compliance
 */
class Backup extends BaseConfig
{
    /**
     * Configurações gerais de backup
     */
    public array $general = [
        'enabled' => true,
        'max_execution_time' => 3600,        // 1 hora
        'memory_limit' => '512M',
        'chunk_size' => 1024 * 1024,         // 1MB chunks
        'compression_level' => 6,             // gzip level 6
        'verify_after_backup' => true,       // Verificar integridade após backup
        'test_restore_monthly' => true,      // Teste de restore mensal
        'parallel_backups' => 3,             // Máximo 3 backups simultâneos
    ];
    
    /**
     * Configuração de storage remoto
     */
    public array $remoteStorage = [
        'enabled' => true,
        'type' => 'ftp',                     // ftp, sftp, s3
        'upload_after_backup' => true,       // Upload automático
        'delete_local_after_upload' => false, // Manter cópia local
        'retry_attempts' => 3,               // Tentativas de upload
        'timeout' => 300,                    // 5 minutos timeout
        
        // Configuração FTP
        'ftp' => [
            'host' => env('BACKUP_FTP_HOST', 'ftp.exemplo.com'),
            'username' => env('BACKUP_FTP_USER', 'backup_user'),
            'password' => env('BACKUP_FTP_PASS', ''),
            'port' => env('BACKUP_FTP_PORT', 21),
            'path' => env('BACKUP_FTP_PATH', '/backups/'),
            'passive' => true,
            'ssl' => false
        ],
        
        // Configuração SFTP
        'sftp' => [
            'host' => env('BACKUP_SFTP_HOST', 'sftp.exemplo.com'),
            'username' => env('BACKUP_SFTP_USER', 'backup_user'),
            'password' => env('BACKUP_SFTP_PASS', ''),
            'private_key' => env('BACKUP_SFTP_KEY', ''),
            'port' => env('BACKUP_SFTP_PORT', 22),
            'path' => env('BACKUP_SFTP_PATH', '/backups/')
        ],
        
        // Configuração S3-compatible (DigitalOcean Spaces, AWS S3, etc)
        's3' => [
            'endpoint' => env('BACKUP_S3_ENDPOINT', 'https://nyc3.digitaloceanspaces.com'),
            'region' => env('BACKUP_S3_REGION', 'nyc3'),
            'bucket' => env('BACKUP_S3_BUCKET', 'meu-bucket-backup'),
            'access_key' => env('BACKUP_S3_ACCESS_KEY', ''),
            'secret_key' => env('BACKUP_S3_SECRET_KEY', ''),
            'path_prefix' => env('BACKUP_S3_PREFIX', 'tenant-backups/')
        ]
    ];
    
    /**
     * Política de rotação de backups
     */
    public array $retention = [
        'local' => [
            'daily_backups' => 7,             // Manter 7 backups diários
            'weekly_backups' => 4,            // Manter 4 backups semanais
            'monthly_backups' => 12,          // Manter 12 backups mensais
            'yearly_backups' => 3             // Manter 3 backups anuais
        ],
        
        'remote' => [
            'daily_backups' => 30,            // Manter 30 backups diários remotos
            'weekly_backups' => 12,           // Manter 12 backups semanais remotos
            'monthly_backups' => 24,          // Manter 24 backups mensais remotos
            'yearly_backups' => 5             // Manter 5 backups anuais remotos
        ],
        
        'cleanup_frequency' => 'daily',      // Frequência de limpeza
        'safe_mode' => true,                 // Nunca deletar último backup
        'min_backups_to_keep' => 2          // Mínimo de backups sempre mantidos
    ];
    
    /**
     * Configuração de notificações
     */
    public array $notifications = [
        'enabled' => true,
        'channels' => ['email', 'webhook'],  // email, webhook, slack
        
        'email' => [
            'enabled' => true,
            'from' => env('BACKUP_EMAIL_FROM', 'backup@sistema.com'),
            'admin_emails' => [
                env('BACKUP_ADMIN_EMAIL', 'admin@sistema.com')
            ],
            'tenant_notification' => true,    // Notificar tenant sobre seus backups
        ],
        
        'webhook' => [
            'enabled' => false,
            'url' => env('BACKUP_WEBHOOK_URL', ''),
            'secret' => env('BACKUP_WEBHOOK_SECRET', ''),
            'timeout' => 10
        ],
        
        'slack' => [
            'enabled' => false,
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK', ''),
            'channel' => env('BACKUP_SLACK_CHANNEL', '#backups'),
            'username' => 'BackupBot'
        ],
        
        // Eventos que geram notificação
        'events' => [
            'backup_success' => true,
            'backup_failed' => true,
            'restore_completed' => true,
            'restore_failed' => true,
            'test_restore_failed' => true,
            'disk_space_low' => true,
            'upload_failed' => true,
            'cleanup_completed' => false
        ]
    ];
    
    /**
     * Configuração de compliance LGPD/GDPR
     */
    public array $compliance = [
        'enabled' => true,
        'audit_access' => true,              // Auditar acesso aos backups
        'encryption_required' => true,       // Criptografia obrigatória
        'data_retention_days' => 2555,       // 7 anos (padrão fiscal)
        'right_to_be_forgotten' => true,     // Direito ao esquecimento
        'data_portability' => true,          // Portabilidade de dados
        
        // Configurações específicas por tipo de dado
        'sensitive_data' => [
            'extra_encryption' => true,      // Criptografia adicional para dados sensíveis
            'access_log_required' => true,   // Log obrigatório de acesso
            'retention_days' => 1825         // 5 anos para dados sensíveis
        ],
        
        // Campos considerados sensíveis (serão extra-criptografados)
        'sensitive_fields' => [
            'cpf', 'cnpj', 'rg', 'passport',
            'credit_card', 'bank_account',
            'phone', 'email', 'address'
        ]
    ];
    
    /**
     * Configuração de monitoramento
     */
    public array $monitoring = [
        'enabled' => true,
        'metrics_retention_days' => 90,
        'alert_thresholds' => [
            'backup_duration_minutes' => 60,     // Alerta se backup > 60min
            'backup_size_gb' => 10,              // Alerta se backup > 10GB
            'failed_backups_count' => 3,         // Alerta após 3 falhas consecutivas
            'disk_usage_percent' => 85,          // Alerta se disco > 85%
            'upload_failures_count' => 2        // Alerta após 2 falhas de upload
        ],
        
        'health_check' => [
            'enabled' => true,
            'endpoint' => '/backup/health',
            'check_interval_minutes' => 15,
            'include_metrics' => true
        ]
    ];
    
    /**
     * Configuração de teste automático
     */
    public array $testing = [
        'enabled' => true,
        'monthly_test' => true,               // Teste mensal automático
        'test_database_prefix' => 'backup_test_',
        'test_retention_hours' => 2,          // Manter dados de teste por 2h
        'notify_on_failure' => true,
        'test_sample_size' => 1000,          // Testar com 1000 registros sample
        
        'validation_checks' => [
            'record_count' => true,           // Validar contagem de registros
            'checksum_validation' => true,   // Validar checksums
            'foreign_key_integrity' => true, // Validar integridade referencial
            'data_consistency' => true       // Validar consistência dos dados
        ]
    ];
    
    /**
     * Configuração de segurança
     */
    public array $security = [
        'backup_directory_protection' => true,
        'key_rotation_days' => 90,            // Rotacionar chaves a cada 90 dias
        'access_control' => [
            'require_admin' => true,          // Apenas admins podem fazer backup/restore
            'log_all_access' => true,         // Log todos os acessos
            'ip_whitelist' => [],             // IPs permitidos (vazio = todos)
            'two_factor_required' => false   // 2FA obrigatório
        ],
        
        'file_permissions' => [
            'backup_files' => 0600,           // rw-------
            'key_files' => 0600,              // rw-------
            'directories' => 0700             // rwx------
        ]
    ];
    
    /**
     * Configuração de performance
     */
    public array $performance = [
        'parallel_processing' => true,
        'max_parallel_jobs' => 3,
        'database_chunk_size' => 1000,       // Processar 1000 registros por vez
        'file_chunk_size' => 1024 * 1024,    // 1MB chunks para arquivos
        'compression_enabled' => true,
        'compression_level' => 6,
        
        'optimization' => [
            'skip_empty_tables' => true,     // Pular tabelas vazias
            'exclude_temp_data' => true,     // Excluir dados temporários
            'optimize_queries' => true,      // Otimizar queries de backup
            'use_indexes' => true            // Usar índices para performance
        ]
    ];
    
    /**
     * Tabelas para backup por tenant
     */
    public array $tables = [
        // Tabelas principais (sempre incluídas)
        'core' => [
            'pos_sales' => [
                'tenant_field' => 'id_empresa',
                'date_field' => 'updated_at',
                'sensitive' => false
            ],
            'pos_sale_items' => [
                'tenant_field' => 'id_empresa',
                'date_field' => 'created_at',
                'sensitive' => false
            ],
            'produtos' => [
                'tenant_field' => 'id_empresa',
                'date_field' => 'updated_at',
                'sensitive' => false
            ],
            'clientes' => [
                'tenant_field' => 'id_empresa',
                'date_field' => 'updated_at',
                'sensitive' => true  // Dados pessoais
            ],
            'fornecedores' => [
                'tenant_field' => 'id_empresa',
                'date_field' => 'updated_at',
                'sensitive' => true  // Dados empresariais
            ]
        ],
        
        // Tabelas de sistema (opcionais)
        'system' => [
            'usuarios' => [
                'tenant_field' => 'id_empresa',
                'date_field' => 'updated_at',
                'sensitive' => true  // Dados de usuários
            ],
            'configuracoes' => [
                'tenant_field' => 'id_empresa',
                'date_field' => 'updated_at',
                'sensitive' => false
            ]
        ],
        
        // Tabelas de auditoria (últimos 90 dias apenas)
        'audit' => [
            'audit_logs' => [
                'tenant_field' => 'tenant_id',
                'date_field' => 'created_at',
                'sensitive' => false,
                'retention_days' => 90
            ],
            'security_alerts' => [
                'tenant_field' => 'tenant_id',
                'date_field' => 'created_at',
                'sensitive' => false,
                'retention_days' => 30
            ]
        ]
    ];
    
    /**
     * Obter configuração de storage remoto ativo
     */
    public function getRemoteStorageConfig(): array
    {
        if (!$this->remoteStorage['enabled']) {
            return [];
        }
        
        $type = $this->remoteStorage['type'];
        return $this->remoteStorage[$type] ?? [];
    }
    
    /**
     * Verificar se notificações estão habilitadas para um evento
     */
    public function shouldNotify(string $event): bool
    {
        return $this->notifications['enabled'] && 
               ($this->notifications['events'][$event] ?? false);
    }
    
    /**
     * Obter política de retenção para um tipo de backup
     */
    public function getRetentionPolicy(string $location = 'local'): array
    {
        return $this->retention[$location] ?? $this->retention['local'];
    }
    
    /**
     * Verificar se campo é sensível
     */
    public function isSensitiveField(string $field): bool
    {
        return in_array(strtolower($field), $this->compliance['sensitive_fields']);
    }
    
    /**
     * Obter tabelas para backup
     */
    public function getTablesToBackup(bool $includeAudit = true): array
    {
        $tables = array_merge($this->tables['core'], $this->tables['system']);
        
        if ($includeAudit) {
            $tables = array_merge($tables, $this->tables['audit']);
        }
        
        return $tables;
    }
}
