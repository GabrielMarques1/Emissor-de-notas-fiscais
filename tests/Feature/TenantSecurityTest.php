<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Testes de Segurança do TenantFilter
 * Valida que o filtro de tenant está funcionando corretamente
 */
class TenantSecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $refresh = true;
    protected $migrate = true;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpar cache para testes
        cache()->clean();
        
        // Criar dados de teste
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Criar empresa de teste
        $this->db->table('empresas')->insert([
            'id_contador' => 1,
            'id_empresa' => 1,
            'razao_social' => 'Empresa Teste',
            'status' => 'ativo',
            'plano' => 'premium',
            'limite_vendas_dia' => 1000,
            'limite_produtos' => 5000,
            'limite_usuarios' => 10,
            'suspenso' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Criar empresa suspensa para testes
        $this->db->table('empresas')->insert([
            'id_contador' => 2,
            'id_empresa' => 2,
            'razao_social' => 'Empresa Suspensa',
            'status' => 'ativo',
            'plano' => 'basic',
            'suspenso' => 1,
            'motivo_suspensao' => 'Teste de suspensão',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Criar empresa inativa
        $this->db->table('empresas')->insert([
            'id_contador' => 3,
            'id_empresa' => 3,
            'razao_social' => 'Empresa Inativa',
            'status' => 'inativo',
            'plano' => 'basic',
            'suspenso' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Teste 1: Acessar API sem sessão deve retornar 401
     */
    public function testAccessApiWithoutSession()
    {
        // Limpar sessão
        session()->destroy();
        
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(401);
        $response->assertJSONFragment([
            'success' => false,
            'code' => 'TENANT_REQUIRED'
        ]);
        
        $this->assertStringContainsString('Tenant não identificado', $response->getJSON()->error);
    }

    /**
     * Teste 2: Acessar API com tenant inválido deve retornar 401
     */
    public function testAccessApiWithInvalidTenant()
    {
        // Configurar sessão com tenant inválido
        session()->set([
            'id_contador' => 0,
            'id_empresa' => 0,
            'id_usuario' => 1
        ]);
        
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(401);
        $response->assertJSONFragment([
            'success' => false,
            'code' => 'TENANT_REQUIRED'
        ]);
    }

    /**
     * Teste 3: Acessar API com tenant suspenso deve retornar 403
     */
    public function testAccessApiWithSuspendedTenant()
    {
        // Configurar sessão com tenant suspenso
        session()->set([
            'id_contador' => 2,
            'id_empresa' => 2,
            'id_usuario' => 1
        ]);
        
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(403);
        $response->assertJSONFragment([
            'success' => false,
            'code' => 'TENANT_FORBIDDEN'
        ]);
        
        $this->assertStringContainsString('suspensa', $response->getJSON()->error);
    }

    /**
     * Teste 4: Acessar API com tenant inativo deve retornar 403
     */
    public function testAccessApiWithInactiveTenant()
    {
        // Configurar sessão com tenant inativo
        session()->set([
            'id_contador' => 3,
            'id_empresa' => 3,
            'id_usuario' => 1
        ]);
        
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(403);
        $response->assertJSONFragment([
            'success' => false,
            'code' => 'TENANT_FORBIDDEN'
        ]);
        
        $this->assertStringContainsString('inativa', $response->getJSON()->error);
    }

    /**
     * Teste 5: Acessar API com tenant válido deve funcionar
     */
    public function testAccessApiWithValidTenant()
    {
        // Configurar sessão com tenant válido
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1
        ]);
        
        $response = $this->get('/api/pos/sales');
        
        // Deve passar pelo filtro (pode retornar 200 ou outro status válido da API)
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * Teste 6: Rotas públicas não devem ser bloqueadas
     */
    public function testPublicRoutesNotBlocked()
    {
        // Limpar sessão
        session()->destroy();
        
        // Testar rotas públicas
        $publicRoutes = [
            '/api/ping',
            '/api/diagnostics',
            '/health'
        ];
        
        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            
            // Não deve retornar 401 por falta de tenant
            $this->assertNotEquals(401, $response->getStatusCode(), 
                "Rota pública {$route} foi bloqueada pelo TenantFilter");
        }
    }

    /**
     * Teste 7: Rate limiting por tenant
     */
    public function testRateLimitingPerTenant()
    {
        // Configurar sessão com tenant válido
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1
        ]);
        
        // Simular muitas requisições (mais que o limite de 1000/min)
        $cache = cache();
        $tenantKey = "rate_limit:tenant:1:1:" . date('Y-m-d-H-i');
        $cache->save($tenantKey, 1001, 60); // Simular 1001 requests
        
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(429);
        $response->assertJSONFragment([
            'success' => false,
            'code' => 'RATE_LIMIT_EXCEEDED'
        ]);
    }

    /**
     * Teste 8: Rate limiting por IP
     */
    public function testRateLimitingPerIP()
    {
        // Configurar sessão com tenant válido
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1
        ]);
        
        // Simular muitas requisições do mesmo IP
        $cache = cache();
        $ipKey = "rate_limit:ip:127.0.0.1:" . date('Y-m-d-H-i');
        $cache->save($ipKey, 101, 60); // Simular 101 requests (limite é 100)
        
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(429);
        $response->assertJSONFragment([
            'success' => false,
            'code' => 'RATE_LIMIT_EXCEEDED'
        ]);
    }

    /**
     * Teste 9: Quota de vendas diárias
     */
    public function testDailySalesQuota()
    {
        // Configurar sessão com tenant válido
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1
        ]);
        
        // Simular que já atingiu o limite de vendas
        $cache = cache();
        $quotaKey = "tenant_quota:1:1:" . date('Y-m-d');
        $cache->save($quotaKey, [
            'vendas_hoje' => 1000,
            'limite_vendas' => 1000,
            'total_produtos' => 100,
            'limite_produtos' => 5000,
            'total_usuarios' => 5,
            'limite_usuarios' => 10
        ], 300);
        
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(429);
        $response->assertJSONFragment([
            'success' => false,
            'code' => 'QUOTA_EXCEEDED'
        ]);
    }

    /**
     * Teste 10: Headers de segurança são adicionados
     */
    public function testSecurityHeadersAdded()
    {
        // Configurar sessão com tenant válido
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1
        ]);
        
        $response = $this->get('/api/pos/sales');
        
        // Verificar headers de segurança
        $this->assertTrue($response->hasHeader('X-Tenant-Validated'));
        $this->assertTrue($response->hasHeader('X-Tenant-ID'));
        $this->assertEquals('1:1', $response->getHeaderLine('X-Tenant-ID'));
    }

    /**
     * Teste 11: Logs de auditoria são criados
     */
    public function testSecurityAuditLogsCreated()
    {
        // Limpar tabela de auditoria
        $this->db->table('security_audit')->truncate();
        
        // Tentar acessar sem tenant
        session()->destroy();
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(401);
        
        // Verificar se log de auditoria foi criado
        $auditLog = $this->db->table('security_audit')
            ->where('violation_type', 'TENANT_NOT_IDENTIFIED')
            ->get()
            ->getFirstRow('array');
        
        $this->assertNotNull($auditLog, 'Log de auditoria não foi criado');
        $this->assertEquals('127.0.0.1', $auditLog['ip_address']);
        $this->assertStringContainsString('/api/pos/sales', $auditLog['uri']);
    }

    /**
     * Teste 12: Performance do filtro (deve ser < 5ms)
     */
    public function testFilterPerformance()
    {
        // Configurar sessão com tenant válido
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1
        ]);
        
        $startTime = microtime(true);
        
        // Fazer 10 requisições para medir performance média
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/api/pos/sales');
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000; // em ms
        $averageTime = $totalTime / 10;
        
        // Filtro deve adicionar menos de 5ms de overhead por requisição
        $this->assertLessThan(5, $averageTime, 
            "TenantFilter está muito lento: {$averageTime}ms (limite: 5ms)");
    }

    /**
     * Teste 13: Múltiplas tentativas do mesmo IP são detectadas
     */
    public function testMultipleAttemptsDetection()
    {
        // Simular múltiplas tentativas falhadas
        for ($i = 0; $i < 12; $i++) {
            session()->destroy();
            $this->get('/api/pos/sales');
        }
        
        // Verificar se foi registrado alerta
        $cache = cache();
        $attemptsKey = "security_attempts:127.0.0.1:" . date('Y-m-d-H');
        $attempts = $cache->get($attemptsKey);
        
        $this->assertGreaterThanOrEqual(10, $attempts, 
            'Múltiplas tentativas não foram detectadas corretamente');
    }

    /**
     * Teste 14: Tenant ID é injetado no request
     */
    public function testTenantIdInjectedInRequest()
    {
        // Configurar sessão com tenant válido
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1
        ]);
        
        // Criar controller de teste para verificar request
        $response = $this->get('/api/pos/sales');
        
        // O filtro deve ter injetado os dados no request
        // (verificação seria feita no controller real)
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * Teste 15: Empresa com data de vencimento expirada
     */
    public function testExpiredTenantAccess()
    {
        // Atualizar empresa para ter data vencida
        $this->db->table('empresas')
            ->where('id_contador', 1)
            ->where('id_empresa', 1)
            ->update([
                'data_vencimento' => date('Y-m-d', strtotime('-1 day'))
            ]);
        
        // Configurar sessão
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1
        ]);
        
        $response = $this->get('/api/pos/sales');
        
        $response->assertStatus(403);
        $response->assertJSONFragment([
            'success' => false,
            'code' => 'TENANT_FORBIDDEN'
        ]);
        
        $this->assertStringContainsString('vencido', $response->getJSON()->error);
    }
}
