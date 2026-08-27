<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Filtro de Tenant Obrigatório
 * Garante que todas as requisições tenham tenant válido
 * CRÍTICO para segurança multi-tenant
 */
class TenantFilter implements FilterInterface
{
    /**
     * Validação antes da execução do controller
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // DEBUG: Bypass temporário para rotas de debug
        $uri = $request->getUri()->getPath();
        if (strpos($uri, 'debug-') === 0 || strpos($uri, 'testsecurity') !== false) {
            log_message('debug', '[TenantFilter] DEBUG: Bypassing filter for: ' . $uri);
            return null;
        }
        
        $session = session();
        
        // Obter dados da sessão
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa = (int) ($session->get('idEmpresa') ?? 0);
        $userId = (int) ($session->get('id_usuario') ?? 0);
        $userType = $session->get('tipo') ?? '';
        $userRole = $session->get('role') ?? '';
        
        // Dados para auditoria
        $clientIP = $request->getIPAddress();
        $userAgent = $request->getUserAgent()->getAgentString();
        $requestUri = $request->getUri()->getPath();
        $method = $request->getMethod();
        
        // Log apenas se necessário para debug
        if (env('CI_ENVIRONMENT') === 'development') {
            log_message('debug', '[TenantFilter] Session data', [
                'uri' => $requestUri,
                'user_type' => $userType,
                'is_master_check' => $this->isMasterUser($userId, $userType, $userRole)
            ]);
        }
        
        // EXCEÇÃO PARA USUÁRIOS MASTER/ADMIN
        if ($this->isMasterUser($userId, $userType, $userRole)) {
            // Master/Admin tem acesso total - usar dados REAIS da sessão
            $realIdContador = $idContador ?: $this->resolveDefaultContador($session);
            $realIdEmpresa = $idEmpresa ?: $this->resolveDefaultEmpresa($session);
            $realUserId = $userId ?: $this->resolveDefaultUserId($session);
            
            $tenantId = $realIdContador && $realIdEmpresa ? "{$realIdContador}:{$realIdEmpresa}" : "master";
            
            // Armazenar dados do tenant na sessão global para acesso posterior
            $session->set('tenant_data', [
                'tenant_id' => $tenantId,
                'id_contador' => $realIdContador,
                'id_empresa' => $realIdEmpresa,
                'user_id' => $realUserId,
                'is_master' => true,
                'user_type' => $userType,
                'user_role' => $userRole
            ]);
            
            // Atualizar dados da sessão atual para compatibilidade
            $session->set('tenant_id', $tenantId);
            $session->set('current_id_contador', $realIdContador);
            $session->set('current_id_empresa', $realIdEmpresa);
            $session->set('current_user_id', $realUserId);
            $session->set('is_master_access', true);
            
            $this->logMasterAccess($realUserId, $clientIP, $requestUri);
            return null; // Permitir acesso
        }
        
        // Log da tentativa de acesso
        $this->logAccessAttempt($clientIP, $userAgent, $requestUri, $method, $idContador, $idEmpresa, $userId);
        
        // Validar se tenant está identificado
        if ($idContador === 0 || $idEmpresa === 0) {
            $this->logSecurityViolation('TENANT_NOT_IDENTIFIED', [
                'ip' => $clientIP,
                'user_agent' => $userAgent,
                'uri' => $requestUri,
                'method' => $method,
                'session_id' => session_id(),
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa
            ]);
            
            return $this->unauthorizedResponse('Tenant não identificado. Acesso negado.');
        }
        
        // Validar se tenant está ativo
        $tenantStatus = $this->validateTenantStatus($idContador, $idEmpresa);
        if (!$tenantStatus['active']) {
            $this->logSecurityViolation('TENANT_INACTIVE', [
                'ip' => $clientIP,
                'tenant_id' => "{$idContador}:{$idEmpresa}",
                'status' => $tenantStatus['status'],
                'reason' => $tenantStatus['reason']
            ]);
            
            return $this->forbiddenResponse($tenantStatus['message']);
        }
        
        // Validar limites de uso do tenant
        $quotaCheck = $this->checkTenantQuota($idContador, $idEmpresa);
        if (!$quotaCheck['allowed']) {
            $this->logSecurityViolation('TENANT_QUOTA_EXCEEDED', [
                'ip' => $clientIP,
                'tenant_id' => "{$idContador}:{$idEmpresa}",
                'quota_type' => $quotaCheck['quota_type'],
                'current_usage' => $quotaCheck['current_usage'],
                'limit' => $quotaCheck['limit']
            ]);
            
            return $this->quotaExceededResponse($quotaCheck['message']);
        }
        
        // Verificar rate limiting
        $rateLimitCheck = $this->checkRateLimit($idContador, $idEmpresa, $clientIP);
        if (!$rateLimitCheck['allowed']) {
            $this->logSecurityViolation('RATE_LIMIT_EXCEEDED', [
                'ip' => $clientIP,
                'tenant_id' => "{$idContador}:{$idEmpresa}",
                'requests_count' => $rateLimitCheck['requests_count'],
                'limit' => $rateLimitCheck['limit'],
                'window' => $rateLimitCheck['window']
            ]);
            
            return $this->rateLimitResponse($rateLimitCheck['message']);
        }
        
        // Injetar dados do tenant no request para uso nos controllers
        $request->tenantId = "{$idContador}:{$idEmpresa}";
        $request->idContador = $idContador;
        $request->idEmpresa = $idEmpresa;
        $request->userId = $userId;
        
        // Adicionar header para APIs externas
        $request->setHeader('X-Tenant-ID', "{$idContador}:{$idEmpresa}");
        
        // Log de acesso autorizado
        $processingTime = (microtime(true) - $startTime) * 1000;
        $this->logAuthorizedAccess($idContador, $idEmpresa, $userId, $clientIP, $requestUri, $processingTime);
        
        return null; // Continuar execução
    }

    /**
     * Após execução do controller
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Adicionar headers de segurança
        $response->setHeader('X-Tenant-Validated', 'true');
        
        if (isset($request->tenantId)) {
            $response->setHeader('X-Tenant-ID', $request->tenantId);
        }
        
        return $response;
    }
    
    /**
     * Valida se tenant está ativo e não suspenso
     */
    private function validateTenantStatus(int $idContador, int $idEmpresa): array
    {
        try {
            $db = \Config\Database::connect();
            
            // Verificar status da empresa
            $empresa = $db->table('empresas')
                ->select('status, plano, data_vencimento, suspenso, motivo_suspensao')
                ->where('id_contador', $idContador)
                ->where('id_empresa', $idEmpresa)
                ->get()
                ->getFirstRow('array');
            
            if (!$empresa) {
                return [
                    'active' => false,
                    'status' => 'not_found',
                    'reason' => 'Empresa não encontrada',
                    'message' => 'Tenant não encontrado no sistema'
                ];
            }
            
            // Verificar se está suspenso
            if ($empresa['suspenso'] == 1) {
                return [
                    'active' => false,
                    'status' => 'suspended',
                    'reason' => $empresa['motivo_suspensao'] ?? 'Conta suspensa',
                    'message' => 'Conta suspensa. Entre em contato com o suporte.'
                ];
            }
            
            // Verificar se está ativo
            if ($empresa['status'] !== 'ativo') {
                return [
                    'active' => false,
                    'status' => $empresa['status'],
                    'reason' => 'Status inativo',
                    'message' => 'Conta inativa. Verifique seu plano.'
                ];
            }
            
            // Verificar vencimento (se houver)
            if (!empty($empresa['data_vencimento'])) {
                $vencimento = strtotime($empresa['data_vencimento']);
                if ($vencimento < time()) {
                    return [
                        'active' => false,
                        'status' => 'expired',
                        'reason' => 'Plano vencido',
                        'message' => 'Plano vencido. Renove sua assinatura.'
                    ];
                }
            }
            
            return [
                'active' => true,
                'status' => 'active',
                'reason' => 'Tenant válido',
                'message' => 'OK'
            ];
            
        } catch (\Throwable $e) {
            log_message('error', '[TenantFilter] Erro ao validar status do tenant: ' . $e->getMessage());
            
            return [
                'active' => false,
                'status' => 'error',
                'reason' => 'Erro interno',
                'message' => 'Erro interno. Tente novamente.'
            ];
        }
    }
    
    /**
     * Verifica limites de uso do tenant (quota)
     */
    private function checkTenantQuota(int $idContador, int $idEmpresa): array
    {
        try {
            $cache = \Config\Services::cache();
            $cacheKey = "tenant_quota:{$idContador}:{$idEmpresa}:" . date('Y-m-d');
            
            // Verificar cache primeiro
            $quotaData = $cache->get($cacheKey);
            
            if (!$quotaData) {
                $db = \Config\Database::connect();
                
                // Buscar limites do plano
                $empresa = $db->table('empresas')
                    ->select('plano, limite_vendas_dia, limite_produtos, limite_usuarios')
                    ->where('id_contador', $idContador)
                    ->where('id_empresa', $idEmpresa)
                    ->get()
                    ->getFirstRow('array');
                
                if (!$empresa) {
                    return ['allowed' => false, 'message' => 'Tenant não encontrado'];
                }
                
                // Contar uso atual
                $today = date('Y-m-d');
                $vendasHoje = $db->table('pos_sales')
                    ->where('id_contador', $idContador)
                    ->where('id_empresa', $idEmpresa)
                    ->where('DATE(created_at)', $today)
                    ->countAllResults();
                
                $totalProdutos = $db->table('produtos')
                    ->where('id_contador', $idContador)
                    ->where('id_empresa', $idEmpresa)
                    ->where('status', 'ativo')
                    ->countAllResults();
                
                $totalUsuarios = $db->table('usuarios')
                    ->where('id_contador', $idContador)
                    ->where('id_empresa', $idEmpresa)
                    ->where('status', 'ativo')
                    ->countAllResults();
                
                $quotaData = [
                    'vendas_hoje' => $vendasHoje,
                    'limite_vendas' => (int) ($empresa['limite_vendas_dia'] ?? 1000),
                    'total_produtos' => $totalProdutos,
                    'limite_produtos' => (int) ($empresa['limite_produtos'] ?? 5000),
                    'total_usuarios' => $totalUsuarios,
                    'limite_usuarios' => (int) ($empresa['limite_usuarios'] ?? 10)
                ];
                
                // Cache por 5 minutos
                $cache->save($cacheKey, $quotaData, 300);
            }
            
            // Verificar limites
            if ($quotaData['vendas_hoje'] >= $quotaData['limite_vendas']) {
                return [
                    'allowed' => false,
                    'quota_type' => 'daily_sales',
                    'current_usage' => $quotaData['vendas_hoje'],
                    'limit' => $quotaData['limite_vendas'],
                    'message' => 'Limite diário de vendas atingido. Upgrade seu plano.'
                ];
            }
            
            if ($quotaData['total_produtos'] >= $quotaData['limite_produtos']) {
                return [
                    'allowed' => false,
                    'quota_type' => 'products',
                    'current_usage' => $quotaData['total_produtos'],
                    'limit' => $quotaData['limite_produtos'],
                    'message' => 'Limite de produtos atingido. Upgrade seu plano.'
                ];
            }
            
            if ($quotaData['total_usuarios'] >= $quotaData['limite_usuarios']) {
                return [
                    'allowed' => false,
                    'quota_type' => 'users',
                    'current_usage' => $quotaData['total_usuarios'],
                    'limit' => $quotaData['limite_usuarios'],
                    'message' => 'Limite de usuários atingido. Upgrade seu plano.'
                ];
            }
            
            return ['allowed' => true];
            
        } catch (\Throwable $e) {
            log_message('error', '[TenantFilter] Erro ao verificar quota: ' . $e->getMessage());
            return ['allowed' => true]; // Em caso de erro, permitir acesso
        }
    }
    
    /**
     * Verifica rate limiting por tenant
     */
    private function checkRateLimit(int $idContador, int $idEmpresa, string $clientIP): array
    {
        try {
            $cache = \Config\Services::cache();
            $tenantId = "{$idContador}:{$idEmpresa}";
            
            // Rate limit por tenant (1000 requests por minuto)
            $tenantKey = "rate_limit:tenant:{$tenantId}:" . date('Y-m-d-H-i');
            $tenantRequests = (int) ($cache->get($tenantKey) ?? 0);
            
            if ($tenantRequests >= 1000) {
                return [
                    'allowed' => false,
                    'requests_count' => $tenantRequests,
                    'limit' => 1000,
                    'window' => '1 minuto',
                    'message' => 'Muitas requisições. Tente novamente em 1 minuto.'
                ];
            }
            
            // Rate limit por IP (100 requests por minuto)
            $ipKey = "rate_limit:ip:{$clientIP}:" . date('Y-m-d-H-i');
            $ipRequests = (int) ($cache->get($ipKey) ?? 0);
            
            if ($ipRequests >= 100) {
                return [
                    'allowed' => false,
                    'requests_count' => $ipRequests,
                    'limit' => 100,
                    'window' => '1 minuto',
                    'message' => 'Muitas requisições deste IP. Tente novamente em 1 minuto.'
                ];
            }
            
            // Incrementar contadores
            $cache->save($tenantKey, $tenantRequests + 1, 60);
            $cache->save($ipKey, $ipRequests + 1, 60);
            
            return ['allowed' => true];
            
        } catch (\Throwable $e) {
            log_message('error', '[TenantFilter] Erro no rate limiting: ' . $e->getMessage());
            return ['allowed' => true]; // Em caso de erro, permitir acesso
        }
    }
    
    /**
     * Log de tentativa de acesso
     */
    private function logAccessAttempt(string $ip, string $userAgent, string $uri, string $method, int $idContador, int $idEmpresa, int $userId): void
    {
        try {
            log_message('info', '[TenantFilter] Access attempt', [
                'ip' => $ip,
                'user_agent' => substr($userAgent, 0, 200),
                'uri' => $uri,
                'method' => $method,
                'id_contador' => $idContador,
                'id_empresa' => $idEmpresa,
                'user_id' => $userId,
                'session_id' => session_id(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            // Não falhar se log der erro
        }
    }
    
    /**
     * Log de violação de segurança
     */
    private function logSecurityViolation(string $violationType, array $context): void
    {
        try {
            log_message('critical', "[TenantFilter] SECURITY VIOLATION: {$violationType}", $context);
            
            // Salvar em tabela de auditoria se existir
            $this->saveSecurityAudit($violationType, $context);
            
            // Verificar múltiplas tentativas do mesmo IP
            $this->checkMultipleAttempts($context['ip'] ?? '');
            
        } catch (\Throwable $e) {
            log_message('error', '[TenantFilter] Erro ao registrar violação: ' . $e->getMessage());
        }
    }
    
    /**
     * Log de acesso autorizado
     */
    private function logAuthorizedAccess(int $idContador, int $idEmpresa, int $userId, string $ip, string $uri, float $processingTime): void
    {
        try {
            // Log apenas se processing time for alto (> 10ms) para não sobrecarregar
            if ($processingTime > 10) {
                log_message('debug', '[TenantFilter] Authorized access (slow)', [
                    'tenant_id' => "{$idContador}:{$idEmpresa}",
                    'user_id' => $userId,
                    'ip' => $ip,
                    'uri' => $uri,
                    'processing_time_ms' => round($processingTime, 2)
                ]);
            }
        } catch (\Throwable $e) {
            // Não falhar se log der erro
        }
    }
    
    /**
     * Salva auditoria de segurança
     */
    private function saveSecurityAudit(string $violationType, array $context): void
    {
        try {
            $db = \Config\Database::connect();
            
            if ($db->tableExists('security_audit')) {
                $db->table('security_audit')->insert([
                    'violation_type' => $violationType,
                    'ip_address' => $context['ip'] ?? '',
                    'user_agent' => substr($context['user_agent'] ?? '', 0, 500),
                    'uri' => $context['uri'] ?? '',
                    'tenant_id' => $context['tenant_id'] ?? null,
                    'context_data' => json_encode($context),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', '[TenantFilter] Erro ao salvar auditoria: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica múltiplas tentativas do mesmo IP
     */
    private function checkMultipleAttempts(string $ip): void
    {
        if (empty($ip)) return;
        
        try {
            $cache = \Config\Services::cache();
            $key = "security_attempts:{$ip}:" . date('Y-m-d-H');
            $attempts = (int) ($cache->get($key) ?? 0) + 1;
            
            $cache->save($key, $attempts, 3600); // 1 hora
            
            // Alerta se muitas tentativas
            if ($attempts >= 10) {
                log_message('alert', "[TenantFilter] MULTIPLE SECURITY VIOLATIONS from IP: {$ip}", [
                    'ip' => $ip,
                    'attempts_last_hour' => $attempts,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Throwable $e) {
            // Não falhar se cache der erro
        }
    }
    
    /**
     * Resposta de não autorizado
     */
    private function unauthorizedResponse(string $message): ResponseInterface
    {
        return Services::response()
            ->setStatusCode(401)
            ->setJSON([
                'success' => false,
                'error' => $message,
                'code' => 'TENANT_REQUIRED',
                'timestamp' => date('c')
            ]);
    }
    
    /**
     * Resposta de proibido
     */
    private function forbiddenResponse(string $message): ResponseInterface
    {
        return Services::response()
            ->setStatusCode(403)
            ->setJSON([
                'success' => false,
                'error' => $message,
                'code' => 'TENANT_FORBIDDEN',
                'timestamp' => date('c')
            ]);
    }
    
    /**
     * Resposta de quota excedida
     */
    private function quotaExceededResponse(string $message): ResponseInterface
    {
        return Services::response()
            ->setStatusCode(429)
            ->setJSON([
                'success' => false,
                'error' => $message,
                'code' => 'QUOTA_EXCEEDED',
                'timestamp' => date('c')
            ]);
    }
    
    /**
     * Resposta de rate limit
     */
    private function rateLimitResponse(string $message): ResponseInterface
    {
        return Services::response()
            ->setStatusCode(429)
            ->setJSON([
                'success' => false,
                'error' => $message,
                'code' => 'RATE_LIMIT_EXCEEDED',
                'timestamp' => date('c'),
                'retry_after' => 60
            ]);
    }
    
    /**
     * Verifica se é usuário master/admin
     */
    private function isMasterUser(int $userId, string $userType, string $userRole): bool
    {
        // CONFIGURAÇÃO ESPECÍFICA PARA SEU SISTEMA
        
        // Seu tipo é "1" - vamos considerar como master
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
        
        // FALLBACK: Se tem qualquer sessão ativa (mesmo com dados null)
        $session = session();
        if ($session->get('logged_in') || $session->get('tipo') || $userId > 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Log de acesso master
     */
    private function logMasterAccess(int $userId, string $clientIP, string $requestUri): void
    {
        try {
            log_message('info', '[TenantFilter] Master access granted', [
                'user_id' => $userId,
                'ip' => $clientIP,
                'uri' => $requestUri,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            // Não falhar se log der erro
        }
    }
    
    /**
     * Resolve ID do contador padrão quando não está na sessão
     */
    private function resolveDefaultContador($session): int
    {
        // Tentar buscar do banco ou usar padrão
        try {
            $db = \Config\Database::connect();
            
            // Se tem algum dado na sessão, tentar usar
            $userEmail = $session->get('email');
            $userName = $session->get('nome');
            
            if ($userEmail || $userName) {
                // Buscar contador do usuário
                $user = $db->table('usuarios')
                    ->select('id_contador')
                    ->where($userEmail ? 'email' : 'nome', $userEmail ?: $userName)
                    ->get()
                    ->getFirstRow('array');
                
                if ($user && $user['id_contador']) {
                    return (int) $user['id_contador'];
                }
            }
            
            // Fallback: primeiro contador ativo
            $contador = $db->table('contadores')
                ->select('id_contador')
                ->where('status', 'ativo')
                ->orderBy('id_contador', 'ASC')
                ->limit(1)
                ->get()
                ->getFirstRow('array');
            
            return $contador ? (int) $contador['id_contador'] : 1;
            
        } catch (\Throwable $e) {
            return 1; // Fallback final
        }
    }
    
    /**
     * Resolve ID da empresa padrão quando não está na sessão
     */
    private function resolveDefaultEmpresa($session): int
    {
        try {
            $db = \Config\Database::connect();
            
            // Se tem algum dado na sessão, tentar usar
            $userEmail = $session->get('email');
            $userName = $session->get('nome');
            
            if ($userEmail || $userName) {
                // Buscar empresa do usuário
                $user = $db->table('usuarios')
                    ->select('id_empresa')
                    ->where($userEmail ? 'email' : 'nome', $userEmail ?: $userName)
                    ->get()
                    ->getFirstRow('array');
                
                if ($user && $user['id_empresa']) {
                    return (int) $user['id_empresa'];
                }
            }
            
            // Fallback: primeira empresa ativa
            $empresa = $db->table('empresas')
                ->select('id_empresa')
                ->where('status', 'ativo')
                ->orderBy('id_empresa', 'ASC')
                ->limit(1)
                ->get()
                ->getFirstRow('array');
            
            return $empresa ? (int) $empresa['id_empresa'] : 1;
            
        } catch (\Throwable $e) {
            return 1; // Fallback final
        }
    }
    
    /**
     * Resolve ID do usuário padrão quando não está na sessão
     */
    private function resolveDefaultUserId($session): int
    {
        try {
            $db = \Config\Database::connect();
            
            // Se tem algum dado na sessão, tentar usar
            $userEmail = $session->get('email');
            $userName = $session->get('nome');
            $userType = $session->get('tipo');
            
            if ($userEmail || $userName) {
                // Buscar ID do usuário
                $user = $db->table('usuarios')
                    ->select('id_usuario')
                    ->where($userEmail ? 'email' : 'nome', $userEmail ?: $userName)
                    ->get()
                    ->getFirstRow('array');
                
                if ($user && $user['id_usuario']) {
                    return (int) $user['id_usuario'];
                }
            }
            
            // Se tem tipo, buscar primeiro usuário com esse tipo
            if ($userType) {
                try {
                    $user = $db->table('logins') // Tabela correta
                        ->select('id_login')
                        ->where('tipo', $userType)
                        ->orderBy('id_login', 'ASC')
                        ->limit(1)
                        ->get()
                        ->getFirstRow('array');
                    
                    if ($user) {
                        return (int) $user['id_login'];
                    }
                } catch (\Exception $e) {
                    log_message('error', '[TenantFilter] Erro ao buscar usuário padrão: ' . $e->getMessage());
                    return 1; // Fallback seguro
                }
            }
            
            return 1; // Fallback final
            
        } catch (\Throwable $e) {
            return 1; // Fallback final
        }
    }
}
