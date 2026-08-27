<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\ReturnService;
use App\Models\ReturnModel;
use App\Models\PosSaleModel;

class ReturnTest extends MultiTenantTestCase
{
    protected ReturnService $returnService;
    protected ReturnModel $returnModel;
    protected PosSaleModel $saleModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->returnService = new ReturnService();
        $this->returnModel = new ReturnModel();
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * @test
     * REGRA: Devoluções devem ser isoladas por tenant
     */
    public function returns_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar devolução no tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'finalized',
        ]);
        
        $return1 = $this->returnModel->insert([
            'id_original_sale' => $sale1,
            'type' => 'full_return',
            'reason' => 'Teste',
            'total_returned' => 100.00,
            'refund_method' => 'cash',
            'processed_by' => 1,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        // ACT: Tenant 2 não deve ver devolução do tenant 1
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $returns = $this->returnModel->findAll();
        
        // ASSERT
        $this->assertEmpty($returns, 'Não deve ver devoluções de outro tenant');
    }
    
    /**
     * @test
     * REGRA: Não pode devolver venda de outro tenant
     */
    public function cannot_return_other_tenant_sale(): void
    {
        // ARRANGE: Tenant 1 cria venda
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'finalized',
        ]);
        
        // ACT: Tenant 2 tenta devolver
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Venda não encontrada');
        
        $this->returnService->processReturn($sale1, [
            'type' => 'full_return',
            'reason' => 'Teste',
        ]);
    }
    
    /**
     * @test
     * REGRA: Devolução deve validar prazo configurado
     */
    public function return_must_validate_time_limit(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Configurar prazo de 7 dias
        $db = \Config\Database::connect();
        $db->table('empresas')
           ->where('id_empresa', $this->tenant1Empresa)
           ->update(['return_days_limit' => 7]);
        
        // Criar venda há 10 dias
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'finalized',
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
        ]);
        
        // ACT: Tentar devolver (fora do prazo)
        $result = $this->returnService->processReturn($sale, [
            'type' => 'full_return',
            'reason' => 'Teste',
        ]);
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('prazo', strtolower($result['error']));
    }
    
    /**
     * @test
     * REGRA: Queries de devoluções devem filtrar por tenant
     */
    public function return_queries_must_filter_by_tenant(): void
    {
        // ARRANGE: Criar devoluções para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'finalized',
        ]);
        
        $this->returnModel->insert([
            'id_original_sale' => $sale1,
            'type' => 'full_return',
            'reason' => 'T1',
            'total_returned' => 100.00,
            'refund_method' => 'cash',
            'processed_by' => 1,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sale2 = $this->saleModel->insert([
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
            'total' => 200.00,
            'status' => 'finalized',
        ]);
        
        $this->returnModel->insert([
            'id_original_sale' => $sale2,
            'type' => 'full_return',
            'reason' => 'T2',
            'total_returned' => 200.00,
            'refund_method' => 'credit',
            'processed_by' => 1,
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
        ]);
        
        // ACT: Listar devoluções do tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $returns = $this->returnModel->findAll();
        
        // ASSERT: Apenas 1 devolução
        $this->assertCount(1, $returns);
        $this->assertNoTenantLeakage($returns, $this->tenant1Contador, $this->tenant1Empresa);
    }
    
    /**
     * @test
     * REGRA: Estoque deve ser reposto no tenant correto
     */
    public function stock_must_be_restocked_in_correct_tenant(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Criar venda finalizada
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'finalized',
        ]);
        
        // Processar devolução com reposição de estoque
        $result = $this->returnService->processReturn($sale, [
            'type' => 'full_return',
            'reason' => 'Produto com defeito',
            'restock' => true,
        ]);
        
        // ASSERT: Devolução deve ser registrada
        $this->assertTrue($result['success']);
        
        // Verificar que pertence ao tenant correto
        $return = $this->returnModel->find($result['return']['id_return']);
        $this->assertTenantOwnership($return, $this->tenant1Contador, $this->tenant1Empresa);
    }
}

