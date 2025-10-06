<?php

/**
 * Helper de Auditoria - Funções Globais para Logging
 * Substitui log_message() padrão com contexto de tenant
 */

use App\Libraries\TenantLogger;

if (!function_exists('tenant_log')) {
    /**
     * Log com contexto de tenant
     * 
     * @param string $level Nível do log
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     */
    function tenant_log(string $level, string $message, array $context = []): void
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new TenantLogger();
        }
        
        $logger->log($level, $message, $context);
    }
}

if (!function_exists('audit_auth')) {
    /**
     * Log de eventos de autenticação
     */
    function audit_auth(string $action, array $context = []): void
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new TenantLogger();
        }
        
        $logger->logAuth($action, $context);
    }
}

if (!function_exists('audit_crud')) {
    /**
     * Log de operações CRUD
     */
    function audit_crud(string $operation, string $entity, $entityId, array $changes = []): void
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new TenantLogger();
        }
        
        $logger->logCrud($operation, $entity, $entityId, $changes);
    }
}

if (!function_exists('audit_access_denied')) {
    /**
     * Log de acessos negados
     */
    function audit_access_denied(string $reason, array $context = []): void
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new TenantLogger();
        }
        
        $logger->logAccessDenied($reason, $context);
    }
}

if (!function_exists('audit_config')) {
    /**
     * Log de mudanças de configuração
     */
    function audit_config(string $setting, $oldValue, $newValue): void
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new TenantLogger();
        }
        
        $logger->logConfig($setting, $oldValue, $newValue);
    }
}

if (!function_exists('audit_financial')) {
    /**
     * Log de operações financeiras
     */
    function audit_financial(string $operation, float $amount, array $context = []): void
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new TenantLogger();
        }
        
        $logger->logFinancial($operation, $amount, $context);
    }
}

if (!function_exists('audit_security')) {
    /**
     * Log de eventos de segurança
     */
    function audit_security(string $event, array $context = []): void
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new TenantLogger();
        }
        
        $logger->logSecurity($event, $context);
    }
}

if (!function_exists('audit_search_logs')) {
    /**
     * Buscar logs do tenant atual
     */
    function audit_search_logs(array $filters = []): array
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new TenantLogger();
        }
        
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        
        if ($idContador && $idEmpresa) {
            return $logger->searchLogs($idContador, $idEmpresa, $filters);
        }
        
        return [];
    }
}

if (!function_exists('audit_performance')) {
    /**
     * Log de performance (tempo de execução)
     */
    function audit_performance(string $operation, float $executionTime, array $context = []): void
    {
        $context['execution_time'] = $executionTime;
        $context['operation'] = $operation;
        
        $level = ($executionTime > 5.0) ? 'warning' : 'info';
        
        tenant_log($level, "Performance: {$operation} took " . number_format($executionTime, 3) . "s", $context);
    }
}

if (!function_exists('audit_api_call')) {
    /**
     * Log de chamadas de API
     */
    function audit_api_call(string $endpoint, string $method, int $statusCode, array $context = []): void
    {
        $context['endpoint'] = $endpoint;
        $context['method'] = $method;
        $context['status_code'] = $statusCode;
        $context['event_type'] = 'api_call';
        
        $level = ($statusCode >= 400) ? 'warning' : 'info';
        
        tenant_log($level, "API Call: {$method} {$endpoint} - {$statusCode}", $context);
    }
}

if (!function_exists('audit_database_query')) {
    /**
     * Log de queries críticas do banco
     */
    function audit_database_query(string $query, float $executionTime, int $affectedRows = 0): void
    {
        // Apenas logar queries que modificam dados ou são lentas
        $isModifying = preg_match('/^(INSERT|UPDATE|DELETE|DROP|ALTER|CREATE)/i', trim($query));
        $isSlow = $executionTime > 1.0;
        
        if ($isModifying || $isSlow) {
            $context = [
                'query_type' => strtoupper(explode(' ', trim($query))[0]),
                'execution_time' => $executionTime,
                'affected_rows' => $affectedRows,
                'event_type' => 'database_query'
            ];
            
            $level = $isSlow ? 'warning' : 'info';
            
            tenant_log($level, "Database Query: " . substr($query, 0, 100) . "...", $context);
        }
    }
}

if (!function_exists('audit_file_operation')) {
    /**
     * Log de operações de arquivo
     */
    function audit_file_operation(string $operation, string $filePath, array $context = []): void
    {
        $context['operation'] = $operation;
        $context['file_path'] = basename($filePath); // Apenas o nome do arquivo por segurança
        $context['event_type'] = 'file_operation';
        
        tenant_log('audit', "File Operation: {$operation} on " . basename($filePath), $context);
    }
}

if (!function_exists('audit_email_sent')) {
    /**
     * Log de emails enviados
     */
    function audit_email_sent(string $to, string $subject, bool $success, array $context = []): void
    {
        $context['recipient'] = $to;
        $context['subject'] = $subject;
        $context['success'] = $success;
        $context['event_type'] = 'email_sent';
        
        $level = $success ? 'info' : 'warning';
        
        tenant_log($level, "Email " . ($success ? 'sent' : 'failed') . " to {$to}: {$subject}", $context);
    }
}

if (!function_exists('audit_export_data')) {
    /**
     * Log de exportação de dados
     */
    function audit_export_data(string $dataType, int $recordCount, string $format, array $context = []): void
    {
        $context['data_type'] = $dataType;
        $context['record_count'] = $recordCount;
        $context['export_format'] = $format;
        $context['event_type'] = 'data_export';
        
        tenant_log('audit', "Data Export: {$recordCount} {$dataType} records exported as {$format}", $context);
    }
}

if (!function_exists('audit_import_data')) {
    /**
     * Log de importação de dados
     */
    function audit_import_data(string $dataType, int $recordCount, int $successCount, int $errorCount, array $context = []): void
    {
        $context['data_type'] = $dataType;
        $context['total_records'] = $recordCount;
        $context['success_count'] = $successCount;
        $context['error_count'] = $errorCount;
        $context['event_type'] = 'data_import';
        
        tenant_log('audit', "Data Import: {$successCount}/{$recordCount} {$dataType} records imported successfully", $context);
    }
}

if (!function_exists('audit_backup_operation')) {
    /**
     * Log de operações de backup
     */
    function audit_backup_operation(string $operation, bool $success, array $context = []): void
    {
        $context['operation'] = $operation;
        $context['success'] = $success;
        $context['event_type'] = 'backup_operation';
        
        $level = $success ? 'info' : 'error';
        
        tenant_log($level, "Backup Operation: {$operation} " . ($success ? 'completed' : 'failed'), $context);
    }
}

if (!function_exists('audit_system_event')) {
    /**
     * Log de eventos do sistema
     */
    function audit_system_event(string $event, array $context = []): void
    {
        $context['event_type'] = 'system_event';
        
        tenant_log('info', "System Event: {$event}", $context);
    }
}
