<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Testes de Segurança de Ownership Multi-Tenant
 * Valida que tenants não podem acessar dados de outros tenants
 */
class OwnershipSecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $refresh = true;
    protected $migrate = true;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpar cache e sessões
        cache()->clean();
        
        // Criar dados de teste multi-tenant
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Criar empresas para diferentes tenants
        $this->db->table('empresas')->insertBatch([
            [
                'id_contador' => 1,
                'id_empresa' => 1,
                'razao_social' => 'Empresa Tenant A',
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_contador' => 2,
                'id_empresa' => 2,
                'razao_social' => 'Empresa Tenant B',
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);

        // Criar usuários para cada tenant
        $this->db->table('usuarios')->insertBatch([
            [
                'id_contador' => 1,
                'id_empresa' => 1,
                'nome' => 'User Tenant A',
                'email' => 'usera@test.com',
                'tipo' => '1',
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_contador' => 2,
                'id_empresa' => 2,
                'nome' => 'User Tenant B',
                'email' => 'userb@test.com',
                'tipo' => '1',
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);

        // Criar vendas para cada tenant
        $this->db->table('pos_sales')->insertBatch([
            [
                'id_contador' => 1,
                'id_empresa' => 1,
                'sale_number' => 'SALE-A-001',
                'total' => 100.00,
                'status' => 'completed',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_contador' => 2,
                'id_empresa' => 2,
                'sale_number' => 'SALE-B-001',
                'total' => 200.00,
                'status' => 'completed',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);

        // Criar produtos para cada tenant
        $this->db->table('produtos')->insertBatch([
            [
                'id_contador' => 1,
                'id_empresa' => 1,
                'nome' => 'Produto Tenant A',
                'codigo' => 'PROD-A-001',
                'preco' => 50.00,
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_contador' => 2,
                'id_empresa' => 2,
                'nome' => 'Produto Tenant B',
                'codigo' => 'PROD-B-001',
                'preco' => 75.00,
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);

        // Criar clientes para cada tenant
        $this->db->table('clientes')->insertBatch([
            [
                'id_contador' => 1,
                'id_empresa' => 1,
                'nome' => 'Cliente Tenant A',
                'cpf_cnpj' => '11111111111',
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_contador' => 2,
                'id_empresa' => 2,
                'nome' => 'Cliente Tenant B',
                'cpf_cnpj' => '22222222222',
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Teste 1: Tenant A não pode visualizar venda de Tenant B
     */
    public function testTenantCannotViewOtherTenantSale()
    {
        // Login como Tenant A
        $this->setTenantSession(1, 1, 1);
        
        // Buscar ID da venda do Tenant B
        $saleB = $this->db->table('pos_sales')
            ->where('id_contador', 2)
            ->where('id_empresa', 2)
            ->get()
            ->getFirstRow('array');
        
        $this->assertNotNull($saleB, 'Venda do Tenant B deve existir');
        
        // Tentar acessar venda do Tenant B
        $response = $this->get("/api/pos/sales/{$saleB['id_pos_sale']}");
        
        // Deve retornar 404 (não 403 para não revelar existência)
        $response->assertStatus(404);
        $response->assertJSONFragment([
            'success' => false,
            'error' => 'Resource not found'
        ]);
    }

    /**
     * Teste 2: Tenant A não pode atualizar venda de Tenant B
     */
    public function testTenantCannotUpdateOtherTenantSale()
    {
        // Login como Tenant A
        $this->setTenantSession(1, 1, 1);
        
        // Buscar ID da venda do Tenant B
        $saleB = $this->db->table('pos_sales')
            ->where('id_contador', 2)
            ->where('id_empresa', 2)
            ->get()
            ->getFirstRow('array');
        
        // Tentar atualizar venda do Tenant B
        $response = $this->put("/api/pos/sales/{$saleB['id_pos_sale']}", [
            'total' => 999.99,
            'notes' => 'HACKED BY TENANT A'
        ]);
        
        // Deve retornar 404
        $response->assertStatus(404);
        
        // Verificar que venda não foi alterada
        $saleAfter = $this->db->table('pos_sales')
            ->where('id_pos_sale', $saleB['id_pos_sale'])
            ->get()
            ->getFirstRow('array');
        
        $this->assertEquals(200.00, $saleAfter['total'], 'Venda não deve ter sido alterada');
        $this->assertNotEquals('HACKED BY TENANT A', $saleAfter['notes'] ?? '');
    }

    /**
     * Teste 3: Tenant A não pode deletar venda de Tenant B
     */
    public function testTenantCannotDeleteOtherTenantSale()
    {
        // Login como Tenant A
        $this->setTenantSession(1, 1, 1);
        
        // Buscar ID da venda do Tenant B
        $saleB = $this->db->table('pos_sales')
            ->where('id_contador', 2)
            ->where('id_empresa', 2)
            ->get()
            ->getFirstRow('array');
        
        // Tentar deletar venda do Tenant B
        $response = $this->delete("/api/pos/sales/{$saleB['id_pos_sale']}");
        
        // Deve retornar 404
        $response->assertStatus(404);
        
        // Verificar que venda ainda existe
        $saleAfter = $this->db->table('pos_sales')
            ->where('id_pos_sale', $saleB['id_pos_sale'])
            ->get()
            ->getFirstRow('array');
        
        $this->assertNotNull($saleAfter, 'Venda não deve ter sido deletada');
    }

    /**
     * Teste 4: Tenant A pode operar normalmente seus próprios dados
     */
    public function testTenantCanAccessOwnData()
    {
        // Login como Tenant A
        $this->setTenantSession(1, 1, 1);
        
        // Buscar ID da venda do Tenant A
        $saleA = $this->db->table('pos_sales')
            ->where('id_contador', 1)
            ->where('id_empresa', 1)
            ->get()
            ->getFirstRow('array');
        
        // Deve conseguir visualizar
        $response = $this->get("/api/pos/sales/{$saleA['id_pos_sale']}");
        $response->assertStatus(200);
        $response->assertJSONFragment([
            'sale_number' => 'SALE-A-001'
        ]);
        
        // Deve conseguir atualizar
        $response = $this->put("/api/pos/sales/{$saleA['id_pos_sale']}", [
            'notes' => 'Updated by owner'
        ]);
        $response->assertStatus(200);
        
        // Verificar que foi atualizada
        $saleAfter = $this->db->table('pos_sales')
            ->where('id_pos_sale', $saleA['id_pos_sale'])
            ->get()
            ->getFirstRow('array');
        
        $this->assertEquals('Updated by owner', $saleAfter['notes']);
    }

    /**
     * Teste 5: Múltiplas tentativas cross-tenant simultâneas
     */
    public function testMultipleCrossTenantAttempts()
    {
        $violations = 0;
        
        // Simular 50 tentativas de acesso cross-tenant
        for ($i = 0; $i < 50; $i++) {
            // Alternar entre tenants
            $tenantA = ($i % 2 === 0);
            
            if ($tenantA) {
                $this->setTenantSession(1, 1, 1);
                // Tentar acessar dados do Tenant B
                $targetSale = $this->db->table('pos_sales')
                    ->where('id_contador', 2)
                    ->where('id_empresa', 2)
                    ->get()
                    ->getFirstRow('array');
            } else {
                $this->setTenantSession(2, 2, 2);
                // Tentar acessar dados do Tenant A
                $targetSale = $this->db->table('pos_sales')
                    ->where('id_contador', 1)
                    ->where('id_empresa', 1)
                    ->get()
                    ->getFirstRow('array');
            }
            
            if ($targetSale) {
                $response = $this->get("/api/pos/sales/{$targetSale['id_pos_sale']}");
                
                if ($response->getStatusCode() === 404) {
                    $violations++;
                }
            }
        }
        
        // Todas as 50 tentativas devem ter sido bloqueadas
        $this->assertEquals(50, $violations, 'Todas tentativas cross-tenant devem ser bloqueadas');
    }

    /**
     * Teste 6: Verificar logs de auditoria
     */
    public function testSecurityAuditLogs()
    {
        // Limpar logs de auditoria
        $this->db->table('security_audit')->truncate();
        
        // Login como Tenant A
        $this->setTenantSession(1, 1, 1);
        
        // Tentar acessar dados do Tenant B
        $saleB = $this->db->table('pos_sales')
            ->where('id_contador', 2)
            ->where('id_empresa', 2)
            ->get()
            ->getFirstRow('array');
        
        $response = $this->get("/api/pos/sales/{$saleB['id_pos_sale']}");
        $response->assertStatus(404);
        
        // Verificar se log de auditoria foi criado
        $auditLog = $this->db->table('security_audit')
            ->where('violation_type', 'UNAUTHORIZED_RESOURCE_ACCESS')
            ->get()
            ->getFirstRow('array');
        
        $this->assertNotNull($auditLog, 'Log de auditoria deve ser criado');
        $this->assertEquals('127.0.0.1', $auditLog['ip_address']);
        $this->assertStringContainsString('/api/pos/sales/', $auditLog['uri']);
    }

    /**
     * Teste 7: Validação com helper validateOwnership
     */
    public function testValidateOwnershipHelper()
    {
        helper('tenant');
        
        // Login como Tenant A
        $this->setTenantSession(1, 1, 1);
        
        // Registro do Tenant A (deve passar)
        $recordA = ['id_contador' => 1, 'id_empresa' => 1, 'id' => 123];
        $this->assertTrue(validateOwnership($recordA), 'Deve validar registro próprio');
        
        // Registro do Tenant B (deve falhar)
        $recordB = ['id_contador' => 2, 'id_empresa' => 2, 'id' => 456];
        $this->assertFalse(validateOwnership($recordB), 'Deve rejeitar registro de outro tenant');
        
        // Registro sem campos de tenant (deve falhar)
        $recordInvalid = ['id' => 789];
        $this->assertFalse(validateOwnership($recordInvalid), 'Deve rejeitar registro sem tenant');
    }

    /**
     * Teste 8: Master user pode acessar qualquer tenant
     */
    public function testMasterUserCanAccessAnyTenant()
    {
        helper('tenant');
        
        // Login como master user
        $this->setMasterSession();
        
        // Deve conseguir acessar dados de qualquer tenant
        $recordA = ['id_contador' => 1, 'id_empresa' => 1, 'id' => 123];
        $recordB = ['id_contador' => 2, 'id_empresa' => 2, 'id' => 456];
        
        $this->assertTrue(validateOwnership($recordA), 'Master deve acessar Tenant A');
        $this->assertTrue(validateOwnership($recordB), 'Master deve acessar Tenant B');
    }

    /**
     * Helper: Configurar sessão de tenant
     */
    private function setTenantSession(int $idContador, int $idEmpresa, int $idUsuario): void
    {
        session()->set([
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
            'id_usuario' => $idUsuario,
            'tipo' => '2', // Usuário normal (não master)
            'logged_in' => true
        ]);
    }

    /**
     * Helper: Configurar sessão de master
     */
    private function setMasterSession(): void
    {
        session()->set([
            'id_contador' => 1,
            'id_empresa' => 1,
            'id_usuario' => 1,
            'tipo' => '1', // Master user
            'logged_in' => true
        ]);
    }
}
