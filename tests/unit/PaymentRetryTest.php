<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\PaymentRetryHandler;

/**
 * Testes para lógica de retry de pagamentos
 * 
 * REGRAS:
 * - 3 tentativas automáticas
 * - Backoff exponencial (1s, 2s, 4s)
 * - Timeout por tentativa: 30s
 * - Falha definitiva após 3 tentativas
 */
class PaymentRetryTest extends CIUnitTestCase
{
    protected PaymentRetryHandler $retryHandler;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->retryHandler = new PaymentRetryHandler();
    }
    
    /**
     * @test
     * REGRA: Deve retornar sucesso se primeira tentativa funcionar
     */
    public function should_succeed_on_first_attempt(): void
    {
        $attempts = 0;
        
        $callback = function () use (&$attempts) {
            $attempts++;
            return ['success' => true, 'message' => 'OK'];
        };
        
        $result = $this->retryHandler->execute($callback);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $attempts);
    }
    
    /**
     * @test
     * REGRA: Deve fazer 3 tentativas antes de falhar definitivamente
     */
    public function should_retry_3_times_on_failure(): void
    {
        $attempts = 0;
        
        $callback = function () use (&$attempts) {
            $attempts++;
            return ['success' => false, 'error' => 'Network error'];
        };
        
        $result = $this->retryHandler->execute($callback);
        
        $this->assertFalse($result['success']);
        $this->assertEquals(3, $attempts);
        $this->assertStringContainsString('após 3 tentativas', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Deve ter sucesso se retry funcionar
     */
    public function should_succeed_on_second_attempt(): void
    {
        $attempts = 0;
        
        $callback = function () use (&$attempts) {
            $attempts++;
            
            if ($attempts === 1) {
                return ['success' => false, 'error' => 'Timeout'];
            }
            
            return ['success' => true, 'message' => 'Succeeded on retry'];
        };
        
        $result = $this->retryHandler->execute($callback);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $attempts);
    }
    
    /**
     * @test
     * REGRA: Deve aplicar backoff exponencial (1s, 2s, 4s)
     */
    public function should_apply_exponential_backoff(): void
    {
        $attempts = [];
        
        $callback = function () use (&$attempts) {
            $attempts[] = microtime(true);
            return ['success' => false, 'error' => 'Fail'];
        };
        
        $startTime = microtime(true);
        $this->retryHandler->execute($callback);
        $totalTime = microtime(true) - $startTime;
        
        // 3 tentativas com delays de 1s e 2s = ~3 segundos total
        $this->assertGreaterThan(2.9, $totalTime);
        $this->assertLessThan(4, $totalTime);
    }
    
    /**
     * @test
     * REGRA: Não deve fazer retry em erros não-recuperáveis
     */
    public function should_not_retry_on_non_recoverable_errors(): void
    {
        $attempts = 0;
        
        $callback = function () use (&$attempts) {
            $attempts++;
            return [
                'success' => false,
                'error' => 'Cartão inválido',
                'recoverable' => false, // Erro definitivo
            ];
        };
        
        $result = $this->retryHandler->execute($callback);
        
        $this->assertFalse($result['success']);
        $this->assertEquals(1, $attempts); // Apenas 1 tentativa
    }
    
    /**
     * @test
     * REGRA: Deve logar todas as tentativas
     */
    public function should_log_all_attempts(): void
    {
        $callback = function () {
            return ['success' => false, 'error' => 'Error'];
        };
        
        $result = $this->retryHandler->execute($callback, [
            'context' => 'TEF',
            'tenant' => '1:100',
        ]);
        
        $this->assertArrayHasKey('attempts_log', $result);
        $this->assertCount(3, $result['attempts_log']);
    }
    
    /**
     * @test
     * REGRA: Timeout por tentativa deve ser respeitado
     */
    public function should_respect_per_attempt_timeout(): void
    {
        $callback = function () {
            sleep(35); // Excede timeout de 30s
            return ['success' => true];
        };
        
        $startTime = microtime(true);
        $result = $this->retryHandler->execute($callback, ['timeout' => 30]);
        $duration = microtime(true) - $startTime;
        
        $this->assertFalse($result['success']);
        $this->assertLessThan(32, $duration); // Deve abortar antes de 35s
    }
}

