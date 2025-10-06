<?php

namespace App\Libraries;

/**
 * Validador de segurança para webhooks
 * 
 * Implementa:
 * - HMAC SHA256 para autenticidade
 * - Prevenção de replay attacks (timestamp)
 * - Whitelist de IPs
 * - Audit trail completo
 */
class WebhookSecurityValidator
{
    protected int $maxAge = 300; // 5 minutos
    protected int $clockSkew = 60; // 1 minuto de tolerância para relógios dessincronizados
    
    /**
     * Validar autenticidade e integridade do webhook
     * 
     * @param string $payload JSON payload recebido
     * @param string $signature HMAC recebido no header
     * @param string $secret Secret key do tenant
     * @param array $options [
     *   'max_age' => 300,
     *   'ip_whitelist' => ['192.168.1.100'],
     *   'client_ip' => '203.0.113.5',
     *   'log' => true,
     *   'tenant' => '1:100'
     * ]
     * @return array ['valid' => bool, 'error' => string, 'validation_log' => array]
     */
    public function validate(
        string $payload,
        string $signature,
        string $secret,
        array $options = []
    ): array {
        $maxAge = $options['max_age'] ?? $this->maxAge;
        $ipWhitelist = $options['ip_whitelist'] ?? null;
        $clientIp = $options['client_ip'] ?? null;
        $shouldLog = $options['log'] ?? false;
        $tenant = $options['tenant'] ?? 'unknown';
        
        $validationLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tenant' => $tenant,
            'checks' => [],
        ];
        
        // 1. Validar HMAC
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        if (!hash_equals($expectedSignature, $signature)) {
            $validationLog['checks'][] = ['hmac' => 'FAILED'];
            
            if ($shouldLog) {
                log_message('warning', '[WebhookSecurity] HMAC inválido', [
                    'tenant' => $tenant,
                    'client_ip' => $clientIp,
                ]);
            }
            
            return [
                'valid' => false,
                'error' => 'HMAC inválido',
                'validation_log' => $validationLog,
            ];
        }
        
        $validationLog['checks'][] = ['hmac' => 'PASSED'];
        
        // 2. Validar timestamp (prevenir replay attacks)
        $data = json_decode($payload, true);
        
        if (isset($data['timestamp'])) {
            $now = time();
            $webhookTime = (int) $data['timestamp'];
            
            // Verificar se é muito antigo
            if ($webhookTime < ($now - $maxAge)) {
                $validationLog['checks'][] = ['timestamp' => 'EXPIRED'];
                
                if ($shouldLog) {
                    log_message('warning', '[WebhookSecurity] Webhook expirado (replay attack?)', [
                        'tenant' => $tenant,
                        'webhook_time' => date('Y-m-d H:i:s', $webhookTime),
                        'age_seconds' => $now - $webhookTime,
                    ]);
                }
                
                return [
                    'valid' => false,
                    'error' => 'Webhook expirado (possível replay attack)',
                    'validation_log' => $validationLog,
                ];
            }
            
            // Verificar se é do futuro (relógio dessincronizado ou ataque)
            if ($webhookTime > ($now + $this->clockSkew)) {
                $validationLog['checks'][] = ['timestamp' => 'FUTURE'];
                
                if ($shouldLog) {
                    log_message('warning', '[WebhookSecurity] Webhook do futuro', [
                        'tenant' => $tenant,
                        'webhook_time' => date('Y-m-d H:i:s', $webhookTime),
                        'diff_seconds' => $webhookTime - $now,
                    ]);
                }
                
                return [
                    'valid' => false,
                    'error' => 'Timestamp no futuro',
                    'validation_log' => $validationLog,
                ];
            }
            
            $validationLog['checks'][] = ['timestamp' => 'PASSED'];
        }
        
        // 3. Validar IP whitelist (se configurado)
        if ($ipWhitelist !== null && $clientIp !== null) {
            if (!in_array($clientIp, $ipWhitelist, true)) {
                $validationLog['checks'][] = ['ip_whitelist' => 'BLOCKED'];
                
                if ($shouldLog) {
                    log_message('warning', '[WebhookSecurity] IP não autorizado', [
                        'tenant' => $tenant,
                        'client_ip' => $clientIp,
                        'whitelist' => implode(', ', $ipWhitelist),
                    ]);
                }
                
                return [
                    'valid' => false,
                    'error' => 'IP não autorizado',
                    'validation_log' => $validationLog,
                ];
            }
            
            $validationLog['checks'][] = ['ip_whitelist' => 'PASSED'];
        }
        
        // Todas as validações passaram
        if ($shouldLog) {
            log_message('info', '[WebhookSecurity] Validação bem-sucedida', [
                'tenant' => $tenant,
                'client_ip' => $clientIp,
            ]);
        }
        
        $validationLog['checks'][] = ['overall' => 'PASSED'];
        
        return [
            'valid' => true,
            'validation_log' => $validationLog,
        ];
    }
    
    /**
     * Gerar signature HMAC para teste
     * 
     * @param string $payload
     * @param string $secret
     * @return string
     */
    public function generateSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }
    
    /**
     * Configurar idade máxima permitida
     */
    public function setMaxAge(int $seconds): self
    {
        $this->maxAge = $seconds;
        return $this;
    }
    
    /**
     * Configurar tolerância de clock skew
     */
    public function setClockSkew(int $seconds): self
    {
        $this->clockSkew = $seconds;
        return $this;
    }
}

