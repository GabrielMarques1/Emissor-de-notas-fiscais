<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\SuspensionService;
use App\Models\PosSaleModel;

class SuspensionTest extends MultiTenantTestCase
{
    protected SuspensionService $suspensionService;
    protected PosSaleModel $saleModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->suspensionService = new SuspensionService();
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * @test
     * REGRA: Vendas suspensas devem ser isoladas por tenant
     */
    public function suspended_sales_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar e suspender venda no tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        $this->suspensionService->suspend($sale1, 'Cliente saiu');
        
        // ACT: Tenant 2 não deve ver vendas suspensas do tenant 1
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $suspended = $this->suspensionService->listSuspended();
        
        // ASSERT: Não deve retornar suspensas de outro tenant
        $this->assertEmpty($suspended, 'Não deve ver vendas suspensas de outro tenant');
    }
    
    /**
     * @test
     * REGRA: Apenas vendas pending podem ser suspensas
     */
    public function only_pending_sales_can_be_suspended(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Criar venda finalizada
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'finalized', // Já finalizada
        ]);
        
        // ACT: Tentar suspender venda finalizada
        $result = $this->suspensionService->suspend($sale, 'Teste');
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('pending', strtolower($result['error']));
    }
    
    /**
     * @test
     * REGRA: Suspender venda deve registrar operador e motivo
     */
    public function suspend_must_register_operator_and_reason(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        // ACT: Suspender venda
        $operatorId = 1;
        $reason = 'Cliente foi ao caixa eletrônico';
        
        $result = $this->suspensionService->suspend($sale, $reason, $operatorId);
        
        // ASSERT: Deve registrar corretamente
        $this->assertTrue($result['success']);
        
        $updatedSale = $this->saleModel->find($sale);
        $this->assertTrue((bool) $updatedSale['is_suspended']);
        $this->assertEquals($operatorId, $updatedSale['suspended_by']);
        $this->assertEquals($reason, $updatedSale['suspended_reason']);
        $this->assertNotNull($updatedSale['suspended_at']);
    }
    
    /**
     * @test
     * REGRA: Retomar venda suspensa deve validar tenant
     */
    public function resume_must_validate_tenant_ownership(): void
    {
        // ARRANGE: Tenant 1 suspende venda
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        $this->suspensionService->suspend($sale, 'Teste');
        
        // ACT: Tenant 2 tenta retomar
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Venda não encontrada');
        
        $this->suspensionService->resume($sale);
    }
    
    /**
     * @test
     * REGRA: Listar suspensas deve filtrar por tenant
     */
    public function list_suspended_must_filter_by_tenant(): void
    {
        // ARRANGE: Criar suspensas para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        $this->suspensionService->suspend($sale1, 'T1');
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sale2 = $this->saleModel->insert([
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
            'total' => 200.00,
            'status' => 'pending',
        ]);
        $this->suspensionService->suspend($sale2, 'T2');
        
        // ACT: Listar suspensas do tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $suspended = $this->suspensionService->listSuspended();
        
        // ASSERT: Deve retornar apenas do tenant 1
        $this->assertCount(1, $suspended);
        $this->assertNoTenantLeakage($suspended, $this->tenant1Contador, $this->tenant1Empresa);
    }
    
    /**
     * @test
     * REGRA: Suspensões expiradas devem ser canceladas automaticamente
     */
    public function expired_suspensions_must_be_auto_cancelled(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Criar venda com suspensão expirada
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
            'is_suspended' => true,
            'suspended_at' => date('Y-m-d H:i:s', strtotime('-25 hours')),
            'suspension_expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ]);
        
        // ACT: Executar expiração
        $expired = $this->suspensionService->expireOld();
        
        // ASSERT: Deve ter expirado 1
        $this->assertEquals(1, $expired);
        
        // Verificar que status mudou
        $updatedSale = $this->saleModel->find($sale);
        $this->assertEquals('cancelled', $updatedSale['status']);
        $this->assertFalse((bool) $updatedSale['is_suspended']);
    }
    
    /**
     * @test
     * REGRA: Não pode exceder limite de vendas suspensas
     */
    public function cannot_exceed_max_suspended_sales(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Configurar limite de 2 suspensas
        $db = \Config\Database::connect();
        $db->table('empresas')
           ->where('id_empresa', $this->tenant1Empresa)
           ->update(['max_suspended_sales' => 2]);
        
        // Criar e suspender 2 vendas (limite)
        for ($i = 0; $i < 2; $i++) {
            $sale = $this->saleModel->insert([
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
                'total' => 100.00,
                'status' => 'pending',
            ]);
            $this->suspensionService->suspend($sale, "Venda $i");
        }
        
        // Tentar suspender mais uma (excede limite)
        $sale3 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        $result = $this->suspensionService->suspend($sale3, 'Venda 3');
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('limite', strtolower($result['error']));
    }
}

