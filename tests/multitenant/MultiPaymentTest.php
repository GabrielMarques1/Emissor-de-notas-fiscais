<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\MultiPaymentService;
use App\Models\PosSalePaymentModel;
use App\Models\PosSaleModel;

class MultiPaymentTest extends MultiTenantTestCase
{
    protected MultiPaymentService $multiPaymentService;
    protected PosSalePaymentModel $paymentModel;
    protected PosSaleModel $saleModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->multiPaymentService = new MultiPaymentService();
        $this->paymentModel = new PosSalePaymentModel();
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * @test
     * REGRA: Pagamentos devem ser isolados por tenant
     */
    public function payments_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar venda e pagamento para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        $result1 = $this->multiPaymentService->addPayment($sale1, [
            'type' => 'cash',
            'amount' => 100.00,
        ]);
        
        $this->assertTrue($result1['success']);
        
        // ACT: Tentar acessar com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $payments = $this->paymentModel->getBySale($sale1);
        
        // ASSERT: Não deve retornar pagamentos de outro tenant
        $this->assertEmpty($payments, 'Não deve acessar pagamentos de outro tenant');
    }
    
    /**
     * @test
     * REGRA: Soma dos pagamentos deve igualar total da venda
     */
    public function sum_of_payments_must_equal_sale_total(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 150.00,
            'status' => 'pending',
        ]);
        
        // ACT: Adicionar pagamentos que NÃO somam o total
        $this->multiPaymentService->addPayment($sale, [
            'type' => 'cash',
            'amount' => 50.00,
        ]);
        
        $this->multiPaymentService->addPayment($sale, [
            'type' => 'credit',
            'amount' => 75.00,
        ]);
        
        // Total de pagamentos: 125.00, mas venda é 150.00
        $result = $this->multiPaymentService->validateTotal($sale);
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['valid']);
        $this->assertEquals(25.00, $result['difference']);
    }
    
    /**
     * @test
     * REGRA: Troco só pode ser calculado para dinheiro
     */
    public function change_only_for_cash_payment(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        // ACT: Dinheiro com troco
        $result1 = $this->multiPaymentService->addPayment($sale, [
            'type' => 'cash',
            'amount' => 150.00, // Cliente deu 150
            'calculate_change' => true,
        ]);
        
        $this->assertTrue($result1['success']);
        $this->assertEquals(50.00, $result1['change']); // Troco: 50
        
        // ACT: Cartão não deve ter troco
        $result2 = $this->multiPaymentService->addPayment($sale, [
            'type' => 'credit',
            'amount' => 150.00,
            'calculate_change' => true,
        ]);
        
        $this->assertTrue($result2['success']);
        $this->assertEquals(0.00, $result2['change']); // Sem troco
    }
    
    /**
     * @test
     * REGRA: Múltiplas formas devem ser validadas ao finalizar
     */
    public function multi_payment_must_be_validated_on_finalize(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 200.00,
            'status' => 'pending',
        ]);
        
        // Adicionar 3 formas de pagamento
        $this->multiPaymentService->addPayment($sale, [
            'type' => 'cash',
            'amount' => 50.00,
        ]);
        
        $this->multiPaymentService->addPayment($sale, [
            'type' => 'credit',
            'amount' => 100.00,
            'installments' => 3,
        ]);
        
        $this->multiPaymentService->addPayment($sale, [
            'type' => 'pix',
            'amount' => 50.00,
        ]);
        
        // ACT: Finalizar venda
        $result = $this->multiPaymentService->finalize($sale);
        
        // ASSERT: Deve validar e finalizar
        $this->assertTrue($result['success']);
        
        // Verificar que venda foi atualizada
        $updatedSale = $this->saleModel->find($sale);
        $this->assertEquals('finalized', $updatedSale['status']);
        $this->assertEquals(200.00, $updatedSale['total_paid']);
        $this->assertTrue((bool) $updatedSale['is_multi_payment']);
    }
    
    /**
     * @test
     * REGRA: Queries de pagamentos devem filtrar por tenant
     */
    public function payment_queries_must_filter_by_tenant(): void
    {
        // ARRANGE: Criar vendas e pagamentos para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        $this->multiPaymentService->addPayment($sale1, [
            'type' => 'cash',
            'amount' => 100.00,
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sale2 = $this->saleModel->insert([
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
            'total' => 200.00,
            'status' => 'pending',
        ]);
        
        $this->multiPaymentService->addPayment($sale2, [
            'type' => 'credit',
            'amount' => 200.00,
        ]);
        
        // ACT: Buscar pagamentos do tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $payments = $this->paymentModel->findAll();
        
        // ASSERT: Deve retornar apenas do tenant 1
        $this->assertCount(1, $payments);
        $this->assertNoTenantLeakage($payments, $this->tenant1Contador, $this->tenant1Empresa);
        $this->assertEquals('cash', $payments[0]['payment_type']);
    }
    
    /**
     * @test
     * REGRA: Não pode adicionar pagamento em venda de outro tenant
     */
    public function cannot_add_payment_to_other_tenant_sale(): void
    {
        // ARRANGE: Criar venda no tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        // ACT: Tentar adicionar pagamento como tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Venda não encontrada');
        
        $this->multiPaymentService->addPayment($sale1, [
            'type' => 'cash',
            'amount' => 100.00,
        ]);
    }
}

