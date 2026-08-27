<?php

namespace App\Libraries;

use CodeIgniter\Log\Logger;
use CodeIgniter\Config\Services;

/**
 * TenantLogger - Sistema de Auditoria Completo Multi-Tenant
 * 
 * Funcionalidades:
 * - Logs separados por tenant
 * - Formato JSON estruturado
 * - Rotação automática
 * - Auditoria completa de ações
 * - Alertas automáticos
 */
class TenantLogger
{
    protected $session;
    protected $request;
    protected $config;
    protected $alertThresholds;
    
    // Níveis de log
    const LEVEL_SECURITY = 'security';
    const LEVEL_AUDIT = 'audit';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';
    
    // Tipos de eventos
    const EVENT_AUTH = 'authentication';
    const EVENT_CRUD = 'crud_operation';
    const EVENT_ACCESS_DENIED = 'access_denied';
    const EVENT_CONFIG = 'configuration';
    const EVENT_FINANCIAL = 'financial';
    const EVENT_SECURITY = 'security';
    
    public function __construct()
    {
        $this->session = session();
        $this->request = service('request');
        $this->config = config('App');
        
        // Configurar thresholds para alertas
        $this->alertThresholds = [
            'login_failures' => 5,      // 5 falhas em 15 minutos
            'cross_tenant_attempts' => 3, // 3 tentativas cross-tenant
            'operations_per_minute' => 100, // 100 operações por minuto
            'off_hours_operations' => 10    // 10 operações fora do horário
        ];
        
        $this->ensureLogDirectories();
    }
    
    /**
     * Log principal com contexto de tenant
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $logData = $this->buildLogEntry($level, $message, $context);
        
        // Escrever no arquivo específico do tenant
        $this->writeToTenantLog($logData);
        
        // Escrever no log geral se for crítico
        if (in_array($level, [self::LEVEL_SECURITY, self::LEVEL_ERROR])) {
            $this->writeToGeneralLog($logData);
        }
        
        // Verificar se precisa de alerta
        $this->checkForAlerts($logData);
    }
    
    /**
     * Log de eventos de autenticação
     */
    public function logAuth(string $action, array $context = []): void
    {
        $context['event_type'] = self::EVENT_AUTH;
        $context['action'] = $action;
        
        $level = ($action === 'login_failure') ? self::LEVEL_SECURITY : self::LEVEL_AUDIT;
        
        $this->log($level, "Authentication event: {$action}", $context);
    }
    
    /**
     * Log de operações CRUD
     */
    public function logCrud(string $operation, string $entity, $entityId, array $changes = []): void
    {
        $context = [
            'event_type' => self::EVENT_CRUD,
            'operation' => $operation,
            'entity_type' => $entity,
            'entity_id' => $entityId,
            'changes' => $changes
        ];
        
        $this->log(self::LEVEL_AUDIT, "CRUD operation: {$operation} on {$entity}#{$entityId}", $context);
    }
    
    /**
     * Log de acessos negados
     */
    public function logAccessDenied(string $reason, array $context = []): void
    {
        $context['event_type'] = self::EVENT_ACCESS_DENIED;
        $context['denial_reason'] = $reason;
        
        $this->log(self::LEVEL_SECURITY, "Access denied: {$reason}", $context);
    }
    
    /**
     * Log de mudanças de configuração
     */
    public function logConfig(string $setting, $oldValue, $newValue): void
    {
        $context = [
            'event_type' => self::EVENT_CONFIG,
            'setting' => $setting,
            'old_value' => $this->sanitizeValue($oldValue),
            'new_value' => $this->sanitizeValue($newValue)
        ];
        
        $this->log(self::LEVEL_AUDIT, "Configuration changed: {$setting}", $context);
    }
    
    /**
     * Log de operações financeiras
     */
    public function logFinancial(string $operation, float $amount, array $context = []): void
    {
        $context['event_type'] = self::EVENT_FINANCIAL;
        $context['operation'] = $operation;
        $context['amount'] = $amount;
        
        $this->log(self::LEVEL_AUDIT, "Financial operation: {$operation} - R$ " . number_format($amount, 2), $context);
    }
    
    /**
     * Log de eventos de segurança
     */
    public function logSecurity(string $event, array $context = []): void
    {
        $context['event_type'] = self::EVENT_SECURITY;
        
        $this->log(self::LEVEL_SECURITY, "Security event: {$event}", $context);
    }
    
    /**
     * Construir entrada de log estruturada
     */
    protected function buildLogEntry(string $level, string $message, array $context = []): array
    {
        $tenantData = $this->getCurrentTenantData();
        
        return [
            'timestamp' => date('c'), // ISO 8601
            'level' => $level,
            'message' => $message,
            'tenant_id' => $tenantData['tenant_id'],
            'id_contador' => $tenantData['id_contador'],
            'id_empresa' => $tenantData['id_empresa'],
            'user_id' => $tenantData['user_id'],
            'username' => $tenantData['username'],
            'ip_address' => $this->getClientIP(),
            'user_agent' => $this->request->getUserAgent()->getAgentString() ?? 'unknown',
            'uri' => $this->request->getUri()->getPath() ?? 'unknown',
            'method' => $this->request->getMethod() ?? 'unknown',
            'session_id' => session_id(),
            'context' => $context,
            'environment' => ENVIRONMENT,
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown'
        ];
    }
    
    /**
     * Obter dados do tenant atual
     */
    protected function getCurrentTenantData(): array
    {
        $idContador = (int) ($this->session->get('id_contador') ?? 0);
        $idEmpresa = (int) ($this->session->get('id_empresa') ?? 0);
        $userId = (int) ($this->session->get('id_usuario') ?? 0);
        $username = (string) ($this->session->get('usuario') ?? 'anonymous');
        
        // Se não tem dados na sessão, tentar resolver
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador, $idEmpresa] = resolve_tenant_ids();
        }
        
        $tenantId = ($idContador && $idEmpresa) ? "{$idContador}:{$idEmpresa}" : 'unknown';
        
        return [
            'tenant_id' => $tenantId,
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
            'user_id' => $userId,
            'username' => $username
        ];
    }
    
    /**
     * Obter IP do cliente
     */
    protected function getClientIP(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Se tem múltiplos IPs, pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Escrever no log específico do tenant
     */
    protected function writeToTenantLog(array $logData): void
    {
        $tenantDir = $this->getTenantLogDirectory($logData['id_contador'], $logData['id_empresa']);
        $logFile = $tenantDir . 'app-' . date('Y-m-d') . '.log';
        
        $logLine = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        // Escrever de forma thread-safe
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Verificar se precisa rotacionar
        $this->checkLogRotation($tenantDir);
    }
    
    /**
     * Escrever no log geral (para eventos críticos)
     */
    protected function writeToGeneralLog(array $logData): void
    {
        $generalLogFile = WRITEPATH . 'logs/general-' . date('Y-m-d') . '.log';
        
        $logLine = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        file_put_contents($generalLogFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obter diretório de logs do tenant
     */
    protected function getTenantLogDirectory(int $idContador, int $idEmpresa): string
    {
        $tenantDir = WRITEPATH . "logs/tenant_{$idContador}_{$idEmpresa}/";
        
        if (!is_dir($tenantDir)) {
            mkdir($tenantDir, 0755, true);
        }
        
        return $tenantDir;
    }
    
    /**
     * Garantir que diretórios de log existam
     */
    protected function ensureLogDirectories(): void
    {
        $baseLogDir = WRITEPATH . 'logs/';
        
        if (!is_dir($baseLogDir)) {
            mkdir($baseLogDir, 0755, true);
        }
        
        // Criar .htaccess para proteger logs
        $htaccessFile = $baseLogDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Deny from all\n");
        }
    }
    
    /**
     * Verificar rotação de logs
     */
    protected function checkLogRotation(string $tenantDir): void
    {
        $files = glob($tenantDir . 'app-*.log');
        
        foreach ($files as $file) {
            $fileDate = filemtime($file);
            $daysDiff = (time() - $fileDate) / (24 * 60 * 60);
            
            // Comprimir logs com mais de 7 dias
            if ($daysDiff > 7 && !file_exists($file . '.gz')) {
                $this->compressLogFile($file);
            }
            
            // Remover logs com mais de 90 dias
            if ($daysDiff > 90) {
                unlink($file);
                if (file_exists($file . '.gz')) {
                    unlink($file . '.gz');
                }
            }
        }
    }
    
    /**
     * Comprimir arquivo de log
     */
    protected function compressLogFile(string $file): void
    {
        if (function_exists('gzencode')) {
            $content = file_get_contents($file);
            $compressed = gzencode($content, 9);
            file_put_contents($file . '.gz', $compressed);
            unlink($file);
        }
    }
    
    /**
     * Verificar se precisa enviar alertas
     */
    protected function checkForAlerts(array $logData): void
    {
        // Verificar falhas de login
        if ($logData['context']['action'] ?? '' === 'login_failure') {
            $this->checkLoginFailures($logData);
        }
        
        // Verificar tentativas cross-tenant
        if ($logData['level'] === self::LEVEL_SECURITY && 
            strpos($logData['message'], 'cross-tenant') !== false) {
            $this->checkCrossTenantAttempts($logData);
        }
        
        // Verificar volume de operações
        $this->checkOperationVolume($logData);
        
        // Verificar operações fora do horário
        $this->checkOffHoursOperations($logData);
    }
    
    /**
     * Verificar múltiplas falhas de login
     */
    protected function checkLoginFailures(array $logData): void
    {
        $ip = $logData['ip_address'];
        $cacheKey = "login_failures_{$ip}";
        
        $cache = service('cache');
        $failures = (int) $cache->get($cacheKey, 0);
        $failures++;
        
        $cache->save($cacheKey, $failures, 900); // 15 minutos
        
        if ($failures >= $this->alertThresholds['login_failures']) {
            $this->sendAlert('Multiple Login Failures', [
                'ip' => $ip,
                'failures' => $failures,
                'tenant_id' => $logData['tenant_id']
            ]);
        }
    }
    
    /**
     * Verificar tentativas cross-tenant
     */
    protected function checkCrossTenantAttempts(array $logData): void
    {
        $userId = $logData['user_id'];
        $cacheKey = "cross_tenant_attempts_{$userId}";
        
        $cache = service('cache');
        $attempts = (int) $cache->get($cacheKey, 0);
        $attempts++;
        
        $cache->save($cacheKey, $attempts, 3600); // 1 hora
        
        if ($attempts >= $this->alertThresholds['cross_tenant_attempts']) {
            $this->sendAlert('Cross-Tenant Access Attempts', [
                'user_id' => $userId,
                'attempts' => $attempts,
                'tenant_id' => $logData['tenant_id']
            ]);
        }
    }
    
    /**
     * Verificar volume de operações
     */
    protected function checkOperationVolume(array $logData): void
    {
        $tenantId = $logData['tenant_id'];
        $cacheKey = "operations_volume_{$tenantId}_" . date('Y-m-d-H-i');
        
        $cache = service('cache');
        $operations = (int) $cache->get($cacheKey, 0);
        $operations++;
        
        $cache->save($cacheKey, $operations, 60); // 1 minuto
        
        if ($operations >= $this->alertThresholds['operations_per_minute']) {
            $this->sendAlert('High Operation Volume', [
                'tenant_id' => $tenantId,
                'operations_per_minute' => $operations
            ]);
        }
    }
    
    /**
     * Verificar operações fora do horário
     */
    protected function checkOffHoursOperations(array $logData): void
    {
        $hour = (int) date('H');
        
        // Considerar fora do horário: 22h às 6h
        if ($hour >= 22 || $hour <= 6) {
            $tenantId = $logData['tenant_id'];
            $cacheKey = "off_hours_ops_{$tenantId}_" . date('Y-m-d');
            
            $cache = service('cache');
            $offHoursOps = (int) $cache->get($cacheKey, 0);
            $offHoursOps++;
            
            $cache->save($cacheKey, $offHoursOps, 86400); // 24 horas
            
            if ($offHoursOps >= $this->alertThresholds['off_hours_operations']) {
                $this->sendAlert('Off-Hours Operations', [
                    'tenant_id' => $tenantId,
                    'operations' => $offHoursOps,
                    'hour' => $hour
                ]);
            }
        }
    }
    
    /**
     * Enviar alerta
     */
    protected function sendAlert(string $alertType, array $data): void
    {
        // Log do alerta
        $this->log(self::LEVEL_SECURITY, "ALERT: {$alertType}", $data);
        
        // Aqui você pode implementar:
        // - Envio de email
        // - Notificação Slack
        // - Webhook
        // - SMS
        
        // Por enquanto, apenas salvar na tabela de alertas
        try {
            $db = \Config\Database::connect();
            $db->table('security_alerts')->insert([
                'alert_type' => $alertType,
                'tenant_id' => $data['tenant_id'] ?? 'unknown',
                'alert_data' => json_encode($data),
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'pending'
            ]);
        } catch (\Throwable $e) {
            // Se falhar, pelo menos logar
            error_log("Failed to save alert: " . $e->getMessage());
        }
    }
    
    /**
     * Sanitizar valores para log (remover senhas, etc.)
     */
    protected function sanitizeValue($value)
    {
        if (is_string($value)) {
            // Lista de campos sensíveis
            $sensitiveFields = ['password', 'senha', 'token', 'secret', 'key'];
            
            foreach ($sensitiveFields as $field) {
                if (stripos($value, $field) !== false) {
                    return '[REDACTED]';
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Buscar logs do tenant
     */
    public function searchLogs(int $idContador, int $idEmpresa, array $filters = []): array
    {
        $tenantDir = $this->getTenantLogDirectory($idContador, $idEmpresa);
        $logs = [];
        
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d');
        
        $period = new \DatePeriod(
            new \DateTime($dateFrom),
            new \DateInterval('P1D'),
            new \DateTime($dateTo . ' +1 day')
        );
        
        foreach ($period as $date) {
            $logFile = $tenantDir . 'app-' . $date->format('Y-m-d') . '.log';
            
            if (file_exists($logFile)) {
                $logs = array_merge($logs, $this->parseLogFile($logFile, $filters));
            }
        }
        
        // Ordenar por timestamp (mais recente primeiro)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return $logs;
    }
    
    /**
     * Parsear arquivo de log
     */
    protected function parseLogFile(string $file, array $filters = []): array
    {
        $logs = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $logEntry = json_decode($line, true);
            
            if ($logEntry && $this->matchesFilters($logEntry, $filters)) {
                $logs[] = $logEntry;
            }
        }
        
        return $logs;
    }
    
    /**
     * Verificar se log corresponde aos filtros
     */
    protected function matchesFilters(array $logEntry, array $filters): bool
    {
        if (!empty($filters['level']) && $logEntry['level'] !== $filters['level']) {
            return false;
        }
        
        if (!empty($filters['user_id']) && $logEntry['user_id'] != $filters['user_id']) {
            return false;
        }
        
        if (!empty($filters['event_type']) && 
            ($logEntry['context']['event_type'] ?? '') !== $filters['event_type']) {
            return false;
        }
        
        if (!empty($filters['search']) && 
            stripos($logEntry['message'], $filters['search']) === false) {
            return false;
        }
        
        return true;
    }
}
