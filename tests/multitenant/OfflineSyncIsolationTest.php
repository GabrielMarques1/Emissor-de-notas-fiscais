<?php

namespace Tests\Multitenant;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestHelper;

/**
 * Testes de Isolamento Multi-Tenant - Sincronização Offline
 * 
 * Valida que operações de sincronização offline respeitam isolamento de tenants
 */
class OfflineSyncIsolationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    
    protected $tenantA;
    protected $tenantB;
    protected $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->db = \Config\Database::connect();
        
        // Criar tenants de teste
        $this->tenantA = [
            'id_contador' => 999,
            'id_empresa' => 777
        ];
        
        $this->tenantB = [
            'id_contador' => 888,
            'id_empresa' => 666
        ];
    }

    /**
     * @test
     * SYNC-ISOLATION-001: Endpoint /api/sync/outbox valida tenant da sessão
     */
    public function testSyncOutboxValidatesTenant()
    {
        // Simular sessão do Tenant A
        $session = session();
        $session->set([
            'id_contador' => $this->tenantA['id_contador'],
            'id_empresa' => $this->tenantA['id_empresa']
        ]);
        
        // Tentar sincronizar operação com dados do Tenant B
        $payload = [
            'operation' => 'create_sale',
            'data' => [
                'id_contador' => $this->tenantB['id_contador'], // Tenant diferente!
                'id_empresa' => $this->tenantB['id_empresa'],
                'valor_total' => 100.00
            ]
        ];
        
        $controller = new \App\Controllers\Api\Sync();
        $result = $this->withBodyFormat('json')
                       ->withBody($payload)
                       ->post('api/sync/outbox');
        
        // Deve falhar (tenant inválido)
        $result->assertStatus(400);
        
        $this->assertStringContainsString('Tenant inválido', $result->getJSON());
    }

    /**
     * @test
     * SYNC-ISOLATION-002: Sincronização cria registros apenas no tenant da sessão
     */
    public function testSyncCreatesRecordsOnlyInSessionTenant()
    {
        // Simular sessão do Tenant A
        $session = session();
        $session->set([
            'id_contador' => $this->tenantA['id_contador'],
            'id_empresa' => $this->tenantA['id_empresa'],
            'id_login' => 1
        ]);
        
        // Sincronizar venda do Tenant A
        $payload = [
            'operation' => 'create_sale',
            'data' => [
                'id_contador' => $this->tenantA['id_contador'],
                'id_empresa' => $this->tenantA['id_empresa'],
                'valor_total' => 150.00,
                'status' => 'pending'
            ]
        ];
        
        $result = $this->withBodyFormat('json')
                       ->withBody($payload)
                       ->post('api/sync/outbox');
        
        $result->assertStatus(201);
        
        // Validar que venda foi criada
        $json = $result->getJSON();
        $saleId = $json->data->id ?? null;
        
        $this->assertNotNull($saleId);
        
        // Buscar venda criada
        $sale = $this->db->table('pos_sales')
                         ->where('id_pos_sale', $saleId)
                         ->get()
                         ->getRowArray();
        
        // Deve ter os campos de tenant corretos
        $this->assertEquals($this->tenantA['id_contador'], $sale['id_contador']);
        $this->assertEquals($this->tenantA['id_empresa'], $sale['id_empresa']);
    }

    /**
     * @test
     * SYNC-ISOLATION-003: Ping endpoint responde sem vazar informações de tenant
     */
    public function testPingDoesNotLeakTenantInfo()
    {
        $result = $this->get('api/ping');
        
        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'online']);
        
        $json = $result->getJSON();
        
        // Não deve conter informações de tenant
        $this->assertObjectNotHasAttribute('id_contador', $json);
        $this->assertObjectNotHasAttribute('id_empresa', $json);
        $this->assertObjectNotHasAttribute('tenant', $json);
    }

    /**
     * @test
     * SYNC-ISOLATION-004: Múltiplos tenants podem sincronizar simultaneamente
     */
    public function testMultipleTenantsCanSyncConcurrently()
    {
        // Sincronização do Tenant A
        $sessionA = session();
        $sessionA->set([
            'id_contador' => $this->tenantA['id_contador'],
            'id_empresa' => $this->tenantA['id_empresa']
        ]);
        
        $payloadA = [
            'operation' => 'create_customer',
            'data' => [
                'id_contador' => $this->tenantA['id_contador'],
                'id_empresa' => $this->tenantA['id_empresa'],
                'nome' => 'Cliente Tenant A',
                'tipo' => 1
            ]
        ];
        
        $resultA = $this->withBodyFormat('json')
                        ->withBody($payloadA)
                        ->post('api/sync/outbox');
        
        $resultA->assertStatus(201);
        
        // Trocar para Tenant B
        $sessionA->set([
            'id_contador' => $this->tenantB['id_contador'],
            'id_empresa' => $this->tenantB['id_empresa']
        ]);
        
        $payloadB = [
            'operation' => 'create_customer',
            'data' => [
                'id_contador' => $this->tenantB['id_contador'],
                'id_empresa' => $this->tenantB['id_empresa'],
                'nome' => 'Cliente Tenant B',
                'tipo' => 1
            ]
        ];
        
        $resultB = $this->withBodyFormat('json')
                        ->withBody($payloadB)
                        ->post('api/sync/outbox');
        
        $resultB->assertStatus(201);
        
        // Validar que clientes foram criados com tenants corretos
        $clienteA = $this->db->table('clientes')
                             ->where('nome', 'Cliente Tenant A')
                             ->get()
                             ->getRowArray();
        
        $clienteB = $this->db->table('clientes')
                             ->where('nome', 'Cliente Tenant B')
                             ->get()
                             ->getRowArray();
        
        $this->assertEquals($this->tenantA['id_contador'], $clienteA['id_contador']);
        $this->assertEquals($this->tenantB['id_contador'], $clienteB['id_contador']);
    }

    /**
     * @test
     * SYNC-ISOLATION-005: Sincronização rejeita operação sem tenant na sessão
     */
    public function testSyncRejectsOperationWithoutSession()
    {
        // Limpar sessão
        $session = session();
        $session->remove(['id_contador', 'id_empresa']);
        
        $payload = [
            'operation' => 'create_sale',
            'data' => [
                'valor_total' => 100.00
            ]
        ];
        
        $result = $this->withBodyFormat('json')
                       ->withBody($payload)
                       ->post('api/sync/outbox');
        
        // Deve falhar (não autenticado)
        $result->assertStatus(401);
    }

    /**
     * @test
     * SYNC-ISOLATION-006: Cache de Service Worker é isolado por URL (tenant implícito)
     */
    public function testServiceWorkerCacheIsIsolatedByUrl()
    {
        // Service Worker cacheia por URL completa
        // Ex: /api/products?tenant=777 vs /api/products?tenant=666
        
        // Simular requisição do Tenant A
        $sessionA = session();
        $sessionA->set([
            'id_contador' => $this->tenantA['id_contador'],
            'id_empresa' => $this->tenantA['id_empresa']
        ]);
        
        $resultA = $this->get('api/products');
        $resultA->assertStatus(200);
        
        $productsA = $resultA->getJSON();
        
        // Trocar para Tenant B
        $sessionA->set([
            'id_contador' => $this->tenantB['id_contador'],
            'id_empresa' => $this->tenantB['id_empresa']
        ]);
        
        $resultB = $this->get('api/products');
        $resultB->assertStatus(200);
        
        $productsB = $resultB->getJSON();
        
        // Produtos devem ser diferentes (cada tenant vê apenas seus produtos)
        // Nota: Este teste assume que há produtos diferentes em cada tenant
        $this->assertNotEquals($productsA, $productsB);
    }
    
    protected function tearDown(): void
    {
        // Limpar dados de teste
        $this->db->table('pos_sales')
                 ->whereIn('id_contador', [$this->tenantA['id_contador'], $this->tenantB['id_contador']])
                 ->delete();
        
        $this->db->table('clientes')
                 ->whereIn('id_contador', [$this->tenantA['id_contador'], $this->tenantB['id_contador']])
                 ->delete();
        
        parent::tearDown();
    }
}

