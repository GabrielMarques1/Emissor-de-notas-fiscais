<?php

namespace App\Libraries;

/**
 * Handler de retry para operações de pagamento
 * 
 * Implementa:
 * - Retry automático (3 tentativas)
 * - Backoff exponencial (1s, 2s, 4s)
 * - Timeout por tentativa
 * - Detecção de erros não-recuperáveis
 * - Logging completo
 */
class PaymentRetryHandler
{
    protected int $maxAttempts = 3;
    protected int $baseDelay = 1; // segundos
    protected int $timeout = 30; // segundos por tentativa
    
    /**
     * Executar callback com retry automático
     * 
     * @param callable $callback Função a executar
     * @param array $options ['timeout' => 30, 'context' => 'TEF', 'tenant' => '1:100']
     * @return array ['success' => bool, 'error' => string, 'attempts_log' => array]
     */
    public function execute(callable $callback, array $options = []): array
    {
        $timeout = $options['timeout'] ?? $this->timeout;
        $context = $options['context'] ?? 'Payment';
        $tenant = $options['tenant'] ?? 'unknown';
        
        $attemptsLog = [];
        $lastResult = null;
        
        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            $startTime = microtime(true);
            
            try {
                // Executar com timeout
                $result = $this->executeWithTimeout($callback, $timeout);
                
                $duration = microtime(true) - $startTime;
                
                // Log da tentativa
                $attemptLog = [
                    'attempt' => $attempt,
                    'duration' => round($duration, 2),
                    'success' => $result['success'] ?? false,
                    'timestamp' => date('Y-m-d H:i:s'),
                ];
                
                $attemptsLog[] = $attemptLog;
                
                // Log detalhado
                log_message('info', "[PaymentRetry] Tentativa {$attempt}/{$this->maxAttempts}", [
                    'context' => $context,
                    'tenant' => $tenant,
                    'success' => $result['success'],
                    'duration' => $attemptLog['duration'],
                ]);
                
                // Sucesso - retornar imediatamente
                if ($result['success'] ?? false) {
                    $result['attempts_log'] = $attemptsLog;
                    $result['total_attempts'] = $attempt;
                    return $result;
                }
                
                $lastResult = $result;
                
                // Verificar se é erro não-recuperável
                if (isset($result['recoverable']) && $result['recoverable'] === false) {
                    log_message('warning', "[PaymentRetry] Erro não-recuperável - abortando", [
                        'context' => $context,
                        'error' => $result['error'] ?? 'Unknown',
                    ]);
                    
                    $result['attempts_log'] = $attemptsLog;
                    $result['total_attempts'] = $attempt;
                    return $result;
                }
                
                // Se não é a última tentativa, aguardar antes de retry
                if ($attempt < $this->maxAttempts) {
                    $delay = $this->calculateBackoff($attempt);
                    
                    log_message('info', "[PaymentRetry] Aguardando {$delay}s antes de retry", [
                        'context' => $context,
                        'next_attempt' => $attempt + 1,
                    ]);
                    
                    sleep($delay);
                }
                
            } catch (\Throwable $e) {
                $duration = microtime(true) - $startTime;
                
                $attemptLog = [
                    'attempt' => $attempt,
                    'duration' => round($duration, 2),
                    'success' => false,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s'),
                ];
                
                $attemptsLog[] = $attemptLog;
                
                log_message('error', "[PaymentRetry] Exceção na tentativa {$attempt}", [
                    'context' => $context,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
                
                $lastResult = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                
                // Aguardar antes de retry (se não for última tentativa)
                if ($attempt < $this->maxAttempts) {
                    $delay = $this->calculateBackoff($attempt);
                    sleep($delay);
                }
            }
        }
        
        // Todas as tentativas falharam
        log_message('error', "[PaymentRetry] Falhou após {$this->maxAttempts} tentativas", [
            'context' => $context,
            'tenant' => $tenant,
            'last_error' => $lastResult['error'] ?? 'Unknown',
        ]);
        
        return [
            'success' => false,
            'error' => ($lastResult['error'] ?? 'Erro desconhecido') . " (falhou após {$this->maxAttempts} tentativas)",
            'attempts_log' => $attemptsLog,
            'total_attempts' => $this->maxAttempts,
        ];
    }
    
    /**
     * Calcular delay de backoff exponencial
     * 
     * @param int $attempt Número da tentativa atual
     * @return int Segundos para aguardar
     */
    protected function calculateBackoff(int $attempt): int
    {
        // Exponencial: 2^(attempt-1) * baseDelay
        // Tentativa 1 => 2^0 * 1 = 1s
        // Tentativa 2 => 2^1 * 1 = 2s
        // Tentativa 3 => 2^2 * 1 = 4s
        return (int) (pow(2, $attempt - 1) * $this->baseDelay);
    }
    
    /**
     * Executar callback com timeout
     * 
     * @param callable $callback
     * @param int $timeout Segundos
     * @return array
     */
    protected function executeWithTimeout(callable $callback, int $timeout): array
    {
        // Configurar timeout (signal alarm em Unix/Linux)
        if (function_exists('pcntl_alarm') && function_exists('pcntl_signal')) {
            $timedOut = false;
            
            // Handler de timeout
            pcntl_signal(SIGALRM, function () use (&$timedOut) {
                $timedOut = true;
            });
            
            pcntl_alarm($timeout);
            
            $result = $callback();
            
            pcntl_alarm(0); // Cancelar alarm
            
            if ($timedOut) {
                return [
                    'success' => false,
                    'error' => "Timeout após {$timeout} segundos",
                    'recoverable' => true,
                ];
            }
            
            return $result;
        }
        
        // Fallback: executar sem timeout
        return $callback();
    }
    
    /**
     * Configurar número máximo de tentativas
     */
    public function setMaxAttempts(int $max): self
    {
        $this->maxAttempts = $max;
        return $this;
    }
    
    /**
     * Configurar delay base para backoff
     */
    public function setBaseDelay(int $seconds): self
    {
        $this->baseDelay = $seconds;
        return $this;
    }
    
    /**
     * Configurar timeout por tentativa
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }
}

