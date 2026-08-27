<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\TenantLogger;

/**
 * AuditFilter - Middleware para Auditoria Automática
 * 
 * Funcionalidades:
 * - Log automático de todas as requisições
 * - Medição de performance
 * - Detecção de anomalias
 * - Auditoria de APIs
 */
class AuditFilter implements FilterInterface
{
    protected $logger;
    protected $startTime;
    protected $excludedPaths = [
        '/favicon.ico',
        '/robots.txt',
        '/.well-known',
        '/assets',
        '/css',
        '/js',
        '/images'
    ];
    
    public function __construct()
    {
        $this->logger = new TenantLogger();
    }
    
    /**
     * Executado antes da requisição
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $this->startTime = microtime(true);
        
        // Pular arquivos estáticos
        $path = $request->getUri()->getPath();
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($path, $excludedPath) === 0) {
                return;
            }
        }
        
        // Log da requisição iniciada
        $this->logRequestStart($request);
        
        // Verificar rate limiting por IP
        $this->checkRateLimit($request);
        
        // Detectar padrões suspeitos
        $this->detectSuspiciousPatterns($request);
        
        return null;
    }
    
    /**
     * Executado após a requisição
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Pular arquivos estáticos
        $path = $request->getUri()->getPath();
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($path, $excludedPath) === 0) {
                return;
            }
        }
        
        $executionTime = microtime(true) - $this->startTime;
        
        // Log da resposta
        $this->logRequestComplete($request, $response, $executionTime);
        
        // Verificar performance
        if ($executionTime > 5.0) {
            $this->logger->log('warning', 'Slow request detected', [
                'execution_time' => $executionTime,
                'uri' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'event_type' => 'performance_issue'
            ]);
        }
        
        return null;
    }
    
    /**
     * Log do início da requisição
     */
    protected function logRequestStart(RequestInterface $request): void
    {
        $context = [
            'event_type' => 'request_start',
            'method' => $request->getMethod(),
            'uri' => $request->getUri()->getPath(),
            'query_string' => $request->getUri()->getQuery(),
            'content_type' => $request->getHeaderLine('Content-Type'),
            'content_length' => $request->getHeaderLine('Content-Length'),
            'referer' => $request->getHeaderLine('Referer'),
            'accept' => $request->getHeaderLine('Accept')
        ];
        
        // Para APIs, logar payload (sem dados sensíveis)
        if (strpos($request->getUri()->getPath(), '/api/') === 0) {
            $payload = $this->sanitizePayload($request);
            if (!empty($payload)) {
                $context['payload_size'] = strlen(json_encode($payload));
                $context['has_payload'] = true;
            }
        }
        
        $this->logger->log('info', 'Request started: ' . $request->getMethod() . ' ' . $request->getUri()->getPath(), $context);
    }
    
    /**
     * Log da conclusão da requisição
     */
    protected function logRequestComplete(RequestInterface $request, ResponseInterface $response, float $executionTime): void
    {
        $context = [
            'event_type' => 'request_complete',
            'method' => $request->getMethod(),
            'uri' => $request->getUri()->getPath(),
            'status_code' => $response->getStatusCode(),
            'execution_time' => round($executionTime, 4),
            'response_size' => strlen($response->getBody()),
            'memory_usage' => memory_get_peak_usage(true),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
        
        // Determinar nível do log baseado no status
        $level = 'info';
        if ($response->getStatusCode() >= 500) {
            $level = 'error';
        } elseif ($response->getStatusCode() >= 400) {
            $level = 'warning';
        }
        
        $message = sprintf(
            'Request completed: %s %s - %d (%s)',
            $request->getMethod(),
            $request->getUri()->getPath(),
            $response->getStatusCode(),
            number_format($executionTime, 3) . 's'
        );
        
        $this->logger->log($level, $message, $context);
        
        // Log específico para APIs
        if (strpos($request->getUri()->getPath(), '/api/') === 0) {
            audit_api_call(
                $request->getUri()->getPath(),
                $request->getMethod(),
                $response->getStatusCode(),
                ['execution_time' => $executionTime]
            );
        }
    }
    
    /**
     * Verificar rate limiting por IP
     */
    protected function checkRateLimit(RequestInterface $request): void
    {
        $ip = $this->getClientIP($request);
        $cacheKey = "rate_limit_ip_{$ip}_" . date('Y-m-d-H-i');
        
        $cache = service('cache');
        $requests = (int) $cache->get($cacheKey, 0);
        $requests++;
        
        $cache->save($cacheKey, $requests, 60); // 1 minuto
        
        // Limite: 300 requisições por minuto por IP
        if ($requests > 300) {
            $this->logger->logSecurity('Rate limit exceeded', [
                'ip' => $ip,
                'requests_per_minute' => $requests,
                'uri' => $request->getUri()->getPath()
            ]);
            
            // Não bloquear, apenas logar por enquanto
            // Em produção, você pode retornar erro 429
        }
    }
    
    /**
     * Detectar padrões suspeitos
     */
    protected function detectSuspiciousPatterns(RequestInterface $request): void
    {
        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();
        $userAgent = $request->getHeaderLine('User-Agent');
        
        $suspiciousPatterns = [
            // Tentativas de SQL injection
            '/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b)/i',
            // Tentativas de XSS
            '/<script|javascript:|onload=|onerror=/i',
            // Tentativas de path traversal
            '/\.\.\/|\.\.\\\\/',
            // Tentativas de command injection
            '/(\bwget\b|\bcurl\b|\bcat\b|\bls\b|\bps\b)/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $uri) || preg_match($pattern, $request->getUri()->getQuery())) {
                $this->logger->logSecurity('Suspicious request pattern detected', [
                    'pattern' => 'URI/Query injection attempt',
                    'uri' => $uri,
                    'query' => $request->getUri()->getQuery(),
                    'method' => $method,
                    'user_agent' => $userAgent
                ]);
                break;
            }
        }
        
        // Detectar user agents suspeitos
        $suspiciousUserAgents = [
            'sqlmap', 'nikto', 'nmap', 'masscan', 'nessus', 'openvas',
            'burpsuite', 'owasp', 'w3af', 'skipfish', 'arachni'
        ];
        
        foreach ($suspiciousUserAgents as $suspiciousUA) {
            if (stripos($userAgent, $suspiciousUA) !== false) {
                $this->logger->logSecurity('Suspicious user agent detected', [
                    'user_agent' => $userAgent,
                    'uri' => $uri,
                    'method' => $method
                ]);
                break;
            }
        }
        
        // Detectar múltiplas requisições para endpoints sensíveis
        $sensitiveEndpoints = ['/login', '/admin', '/api/auth', '/reset-password'];
        
        foreach ($sensitiveEndpoints as $endpoint) {
            if (strpos($uri, $endpoint) === 0) {
                $ip = $this->getClientIP($request);
                // Sanitizar chave removendo caracteres reservados
                $sanitizedEndpoint = $this->sanitizeCacheKey($endpoint);
                $sanitizedIP = $this->sanitizeCacheKey($ip);
                $cacheKey = "sensitive_endpoint_{$sanitizedIP}_{$sanitizedEndpoint}_" . date('Y-m-d-H');
                
                $cache = service('cache');
                $attempts = (int) $cache->get($cacheKey, 0);
                $attempts++;
                
                $cache->save($cacheKey, $attempts, 3600); // 1 hora
                
                if ($attempts > 20) { // 20 tentativas por hora
                    $this->logger->logSecurity('Multiple attempts on sensitive endpoint', [
                        'endpoint' => $endpoint,
                        'attempts_per_hour' => $attempts,
                        'ip' => $ip
                    ]);
                }
                break;
            }
        }
    }
    
    /**
     * Sanitizar payload para log (remover dados sensíveis)
     */
    protected function sanitizePayload(RequestInterface $request): array
    {
        $payload = [];
        
        try {
            if ($request->getMethod() === 'POST' || $request->getMethod() === 'PUT') {
                $contentType = $request->getHeaderLine('Content-Type');
                
                if (strpos($contentType, 'application/json') !== false) {
                    $json = $request->getJSON(true);
                    if (is_array($json)) {
                        $payload = $this->removeSensitiveData($json);
                    }
                } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                    $post = $request->getPost();
                    if (is_array($post)) {
                        $payload = $this->removeSensitiveData($post);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Se falhar ao parsear, apenas ignorar
        }
        
        return $payload;
    }
    
    /**
     * Remover dados sensíveis do payload
     */
    protected function removeSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password', 'senha', 'token', 'secret', 'key', 'api_key',
            'access_token', 'refresh_token', 'authorization', 'auth',
            'cpf', 'cnpj', 'credit_card', 'card_number', 'cvv', 'pin'
        ];
        
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $keyLower = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveFields as $sensitiveField) {
                if (strpos($keyLower, $sensitiveField) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->removeSensitiveData($value);
            } else {
                // Limitar tamanho de strings para evitar logs gigantes
                if (is_string($value) && strlen($value) > 200) {
                    $sanitized[$key] = substr($value, 0, 200) . '... [TRUNCATED]';
                } else {
                    $sanitized[$key] = $value;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Obter IP do cliente
     */
    protected function getClientIP(RequestInterface $request): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
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
     * Sanitizar chave de cache removendo caracteres reservados
     * 
     * @param string $key Chave original
     * @return string Chave sanitizada
     */
    private function sanitizeCacheKey(string $key): string
    {
        // Remover caracteres reservados do CodeIgniter Cache: {}()/\@:
        $sanitized = str_replace(['/', '\\', ':', '@', '{', '}', '(', ')'], '_', $key);
        
        // Remover caracteres especiais adicionais e espaços
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sanitized);
        
        // Remover múltiplos underscores consecutivos
        $sanitized = preg_replace('/_+/', '_', $sanitized);
        
        // Remover underscores do início e fim
        $sanitized = trim($sanitized, '_');
        
        // Garantir que não está vazio
        if (empty($sanitized)) {
            $sanitized = 'unknown';
        }
        
        // Limitar tamanho da chave (máximo 250 caracteres)
        if (strlen($sanitized) > 200) {
            $sanitized = substr($sanitized, 0, 200) . '_' . md5($key);
        }
        
        return $sanitized;
    }
}
