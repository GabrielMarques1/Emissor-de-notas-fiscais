<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\WebhookSecurityValidator;

/**
 * Testes para validação de segurança de webhooks
 * 
 * REGRAS:
 * - HMAC SHA256 para validar autenticidade
 * - Replay attack prevention (timestamp)
 * - IP whitelist (opcional)
 * - Tenant validation obrigatória
 */
class WebhookSecurityTest extends CIUnitTestCase
{
    protected WebhookSecurityValidator $validator;
    protected string $webhookSecret = 'test_secret_key_12345';
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new WebhookSecurityValidator();
    }
    
    /**
     * @test
     * REGRA: Webhook com HMAC válido deve passar
     */
    public function valid_hmac_should_pass_validation(): void
    {
        $payload = json_encode([
            'txid' => 'PIX123456789',
            'amount' => 100.00,
            'status' => 'confirmed',
            'timestamp' => time(),
        ]);
        
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        $result = $this->validator->validate($payload, $signature, $this->webhookSecret);
        
        $this->assertTrue($result['valid']);
    }
    
    /**
     * @test
     * REGRA: Webhook com HMAC inválido deve falhar
     */
    public function invalid_hmac_should_fail_validation(): void
    {
        $payload = json_encode([
            'txid' => 'PIX123456789',
            'amount' => 100.00,
        ]);
        
        $wrongSignature = 'invalid_signature_here';
        
        $result = $this->validator->validate($payload, $wrongSignature, $this->webhookSecret);
        
        $this->assertFalse($result['valid']);
        $this->assertEquals('HMAC inválido', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Webhook muito antigo (>5min) deve ser rejeitado (replay attack)
     */
    public function old_webhook_should_be_rejected(): void
    {
        $payload = json_encode([
            'txid' => 'PIX123456789',
            'timestamp' => time() - 400, // 6 minutos atrás
        ]);
        
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        $result = $this->validator->validate($payload, $signature, $this->webhookSecret, [
            'max_age' => 300, // 5 minutos
        ]);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('expirado', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Webhook do futuro deve ser rejeitado
     */
    public function future_webhook_should_be_rejected(): void
    {
        $payload = json_encode([
            'txid' => 'PIX123456789',
            'timestamp' => time() + 120, // 2 minutos no futuro
        ]);
        
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        $result = $this->validator->validate($payload, $signature, $this->webhookSecret);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('futuro', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Payload modificado deve ser detectado
     */
    public function tampered_payload_should_be_detected(): void
    {
        $originalPayload = json_encode([
            'txid' => 'PIX123456789',
            'amount' => 100.00,
        ]);
        
        $signature = hash_hmac('sha256', $originalPayload, $this->webhookSecret);
        
        // Atacante modifica payload mas mantém signature
        $tamperedPayload = json_encode([
            'txid' => 'PIX123456789',
            'amount' => 1000.00, // Alterado!
        ]);
        
        $result = $this->validator->validate($tamperedPayload, $signature, $this->webhookSecret);
        
        $this->assertFalse($result['valid']);
    }
    
    /**
     * @test
     * REGRA: Whitelist de IPs deve ser respeitada
     */
    public function ip_whitelist_should_be_enforced(): void
    {
        $payload = json_encode(['txid' => 'PIX123']);
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        // IP não está na whitelist
        $result = $this->validator->validate($payload, $signature, $this->webhookSecret, [
            'ip_whitelist' => ['192.168.1.100', '10.0.0.50'],
            'client_ip' => '203.0.113.5',
        ]);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('IP não autorizado', $result['error']);
    }
    
    /**
     * @test
     * REGRA: IP na whitelist deve passar
     */
    public function whitelisted_ip_should_pass(): void
    {
        $payload = json_encode(['txid' => 'PIX123', 'timestamp' => time()]);
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        $result = $this->validator->validate($payload, $signature, $this->webhookSecret, [
            'ip_whitelist' => ['192.168.1.100', '203.0.113.5'],
            'client_ip' => '203.0.113.5',
        ]);
        
        $this->assertTrue($result['valid']);
    }
    
    /**
     * @test
     * REGRA: Deve logar todas as validações (audit trail)
     */
    public function should_log_all_validations(): void
    {
        $payload = json_encode(['txid' => 'PIX123', 'timestamp' => time()]);
        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        $result = $this->validator->validate($payload, $signature, $this->webhookSecret, [
            'log' => true,
            'tenant' => '1:100',
        ]);
        
        $this->assertArrayHasKey('validation_log', $result);
    }
}

