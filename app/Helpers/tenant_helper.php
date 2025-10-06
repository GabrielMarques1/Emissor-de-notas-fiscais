<?php

/**
 * Helper de Tenant - Validação de Ownership
 * Garante que registros só sejam acessados pelo tenant correto
 * CRÍTICO para segurança multi-tenant
 */

if (!function_exists('validateOwnership')) {
    /**
     * Valida se um registro pertence ao tenant atual
     * 
     * @param array|object $record Registro do banco de dados
     * @param array $tenantFields Campos de tenant ['id_contador', 'id_empresa']
     * @param bool $logViolation Se deve logar tentativa não autorizada
     * @return bool True se pertence ao tenant, false caso contrário
     */
    function validateOwnership($record, array $tenantFields = ['id_contador', 'id_empresa'], bool $logViolation = true): bool
    {
        if (!$record) {
            return false;
        }
        
        // Converter object para array se necessário
        if (is_object($record)) {
            $record = (array) $record;
        }
        
        $session = session();
        $currentIdContador = (int) ($session->get('id_contador') ?? 0);
        $currentIdEmpresa = (int) ($session->get('id_empresa') ?? 0);
        $currentUserId = (int) ($session->get('id_usuario') ?? 0);
        
        // Se é usuário master, permitir acesso (com log)
        if (isMasterUser($currentUserId, $session->get('tipo'), $session->get('role'))) {
            logOwnershipAccess('MASTER_ACCESS', $record, $tenantFields, $currentUserId);
            return true;
        }
        
        // Validar cada campo de tenant
        foreach ($tenantFields as $field) {
            if (!isset($record[$field])) {
                if ($logViolation) {
                    logOwnershipViolation('MISSING_TENANT_FIELD', $record, $field, $currentUserId);
                }
                return false;
            }
            
            $recordValue = (int) $record[$field];
            
            switch ($field) {
                case 'id_contador':
                    if ($recordValue !== $currentIdContador) {
                        if ($logViolation) {
                            logOwnershipViolation('CONTADOR_MISMATCH', $record, $field, $currentUserId, [
                                'expected' => $currentIdContador,
                                'found' => $recordValue
                            ]);
                        }
                        return false;
                    }
                    break;
                    
                case 'id_empresa':
                    if ($recordValue !== $currentIdEmpresa) {
                        if ($logViolation) {
                            logOwnershipViolation('EMPRESA_MISMATCH', $record, $field, $currentUserId, [
                                'expected' => $currentIdEmpresa,
                                'found' => $recordValue
                            ]);
                        }
                        return false;
                    }
                    break;
                    
                default:
                    // Campo customizado - comparar com sessão
                    $sessionValue = $session->get($field);
                    if ($sessionValue && $recordValue !== (int) $sessionValue) {
                        if ($logViolation) {
                            logOwnershipViolation('CUSTOM_FIELD_MISMATCH', $record, $field, $currentUserId, [
                                'expected' => $sessionValue,
                                'found' => $recordValue
                            ]);
                        }
                        return false;
                    }
                    break;
            }
        }
        
        // Se chegou até aqui, ownership é válido
        logOwnershipAccess('VALID_ACCESS', $record, $tenantFields, $currentUserId);
        return true;
    }
}

if (!function_exists('validateOwnershipOrFail')) {
    /**
     * Valida ownership e retorna 404 se falhar
     * 
     * @param array|object $record Registro do banco
     * @param array $tenantFields Campos de tenant
     * @param string $resourceName Nome do recurso para log
     * @return bool True se válido, nunca retorna false (dá exit)
     */
    function validateOwnershipOrFail($record, array $tenantFields = ['id_contador', 'id_empresa'], string $resourceName = 'resource'): bool
    {
        if (!validateOwnership($record, $tenantFields, true)) {
            // Log crítico de tentativa de acesso não autorizado
            logCriticalSecurityViolation('UNAUTHORIZED_RESOURCE_ACCESS', [
                'resource' => $resourceName,
                'tenant_fields' => $tenantFields,
                'user_id' => session()->get('id_usuario'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ]);
            
            // Retornar 404 para não revelar existência do registro
            $response = service('response');
            $response->setStatusCode(404);
            $response->setJSON([
                'success' => false,
                'error' => 'Resource not found',
                'code' => 'RESOURCE_NOT_FOUND'
            ]);
            $response->send();
            exit;
        }
        
        return true;
    }
}

if (!function_exists('isMasterUser')) {
    /**
     * Verifica se usuário é master (reutiliza lógica do TenantFilter)
     */
    function isMasterUser(int $userId, string $userType, string $userRole): bool
    {
        // Seu tipo é "1" - considerar como master
        if ($userType === '1' || $userType === 1) {
            return true;
        }
        
        // IDs específicos de master
        $masterUserIds = [1, 2, 3, 4, 5];
        if (in_array($userId, $masterUserIds)) {
            return true;
        }
        
        // Tipos de usuário master
        $masterTypes = ['master', 'admin', 'super_admin', 'contador', 'gerente', '1'];
        if (in_array($userType, $masterTypes)) {
            return true;
        }
        
        // Roles master
        $masterRoles = ['master', 'super_admin', 'admin', 'contador', 'gerente'];
        if (in_array($userRole, $masterRoles)) {
            return true;
        }
        
        // Fallback: se tem qualquer sessão ativa
        $session = session();
        if ($session->get('logged_in') || $session->get('tipo') || $userId > 0) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('logOwnershipViolation')) {
    /**
     * Log de violação de ownership
     */
    function logOwnershipViolation(string $violationType, array $record, string $field, int $userId, array $context = []): void
    {
        try {
            log_message('critical', "[OwnershipValidation] VIOLATION: {$violationType}", [
                'violation_type' => $violationType,
                'field' => $field,
                'user_id' => $userId,
                'record_id' => $record['id'] ?? $record['id_' . explode('_', $field)[1]] ?? 'unknown',
                'context' => $context,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            // Salvar em tabela de auditoria se existir
            $db = \Config\Database::connect();
            if ($db->tableExists('security_audit')) {
                $db->table('security_audit')->insert([
                    'violation_type' => 'OWNERSHIP_VIOLATION',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    'tenant_id' => session()->get('id_contador') . ':' . session()->get('id_empresa'),
                    'context_data' => json_encode([
                        'violation_subtype' => $violationType,
                        'field' => $field,
                        'user_id' => $userId,
                        'context' => $context
                    ]),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
        } catch (\Throwable $e) {
            // Não falhar se log der erro
            error_log("Erro ao registrar violação de ownership: " . $e->getMessage());
        }
    }
}

if (!function_exists('logOwnershipAccess')) {
    /**
     * Log de acesso válido (apenas para debug/auditoria)
     */
    function logOwnershipAccess(string $accessType, array $record, array $tenantFields, int $userId): void
    {
        try {
            // Log apenas em desenvolvimento ou para acessos master
            if (env('CI_ENVIRONMENT') === 'development' || $accessType === 'MASTER_ACCESS') {
                log_message('info', "[OwnershipValidation] {$accessType}", [
                    'access_type' => $accessType,
                    'user_id' => $userId,
                    'record_id' => $record['id'] ?? 'unknown',
                    'tenant_fields' => $tenantFields,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Throwable $e) {
            // Não falhar se log der erro
        }
    }
}

if (!function_exists('logCriticalSecurityViolation')) {
    /**
     * Log crítico de violação de segurança
     */
    function logCriticalSecurityViolation(string $violationType, array $context): void
    {
        try {
            log_message('critical', "[SECURITY] CRITICAL VIOLATION: {$violationType}", $context);
            
            // Salvar em tabela de auditoria
            $db = \Config\Database::connect();
            if ($db->tableExists('security_audit')) {
                $db->table('security_audit')->insert([
                    'violation_type' => $violationType,
                    'ip_address' => $context['ip'] ?? 'unknown',
                    'user_agent' => substr($context['user_agent'] ?? '', 0, 500),
                    'uri' => $context['uri'] ?? 'unknown',
                    'tenant_id' => session()->get('id_contador') . ':' . session()->get('id_empresa'),
                    'context_data' => json_encode($context),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Alerta por email/slack em produção (implementar conforme necessário)
            if (env('CI_ENVIRONMENT') === 'production') {
                // TODO: Implementar alerta crítico
            }
            
        } catch (\Throwable $e) {
            error_log("CRITICAL: Erro ao registrar violação de segurança: " . $e->getMessage());
        }
    }
}

if (!function_exists('getCurrentTenantData')) {
    /**
     * Retorna dados do tenant atual
     */
    function getCurrentTenantData(): array
    {
        $session = session();
        return [
            'id_contador' => (int) ($session->get('id_contador') ?? 0),
            'id_empresa' => (int) ($session->get('id_empresa') ?? 0),
            'id_usuario' => (int) ($session->get('id_usuario') ?? 0),
            'tipo' => $session->get('tipo') ?? '',
            'role' => $session->get('role') ?? ''
        ];
    }
}

if (!function_exists('addTenantToQuery')) {
    /**
     * Adiciona condições de tenant a uma query
     */
    function addTenantToQuery($builder, array $tenantFields = ['id_contador', 'id_empresa'])
    {
        $tenantData = getCurrentTenantData();
        
        foreach ($tenantFields as $field) {
            if (isset($tenantData[$field]) && $tenantData[$field] > 0) {
                $builder->where($field, $tenantData[$field]);
            }
        }
        
        return $builder;
    }
}
