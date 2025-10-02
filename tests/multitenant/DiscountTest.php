<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\DiscountService;
use App\Models\CouponModel;
use App\Models\DiscountModel;
use App\Models\PosSaleModel;

class DiscountTest extends MultiTenantTestCase
{
    protected DiscountService $discountService;
    protected CouponModel $couponModel;
    protected DiscountModel $discountModel;
    protected PosSaleModel $saleModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->discountService = new DiscountService();
        $this->couponModel = new CouponModel();
        $this->discountModel = new DiscountModel();
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * @test
     * REGRA: Cupons devem ser isolados por tenant
     */
    public function coupons_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar cupom para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $coupon1 = $this->couponModel->insert([
            'code' => 'PROMO10',
            'type' => 'percentage',
            'value' => 10.00,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        // ACT: Tenant 2 não deve ver cupom do tenant 1
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $found = $this->couponModel->where('code', 'PROMO10')->first();
        
        // ASSERT
        $this->assertNull($found, 'Não deve ver cupom de outro tenant');
    }
    
    /**
     * @test
     * REGRA: Aplicar cupom deve validar tenant
     */
    public function apply_coupon_must_validate_tenant(): void
    {
        // ARRANGE: Tenant 1 cria cupom
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $this->couponModel->insert([
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20.00,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        // ACT: Tenant 2 tenta usar cupom do tenant 1
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sale2 = $this->saleModel->insert([
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
            'total' => 150.00,
            'status' => 'pending',
        ]);
        
        $result = $this->discountService->applyCoupon($sale2, 'SAVE20');
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('inválido', strtolower($result['error']));
    }
    
    /**
     * @test
     * REGRA: Desconto não pode exceder limite do tenant
     */
    public function discount_cannot_exceed_tenant_limit(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Configurar limite de 30%
        $db = \Config\Database::connect();
        $db->table('empresas')
           ->where('id_empresa', $this->tenant1Empresa)
           ->update(['max_discount_percentage' => 30.00]);
        
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 200.00,
            'status' => 'pending',
        ]);
        
        // ACT: Tentar aplicar desconto de 40% (excede limite)
        $result = $this->discountService->applyDiscount($sale, [
            'type' => 'percentage',
            'value' => 40.00,
            'reason' => 'Cliente VIP',
        ]);
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('limite', strtolower($result['error']));
    }
    
    /**
     * @test
     * REGRA: Descontos devem ser auditados por tenant
     */
    public function discounts_must_be_audited_by_tenant(): void
    {
        // ARRANGE: Aplicar desconto no tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 100.00,
            'status' => 'pending',
        ]);
        
        $this->discountService->applyDiscount($sale1, [
            'type' => 'fixed',
            'value' => 10.00,
            'reason' => 'Desconto promocional',
        ]);
        
        // ACT: Tenant 2 não deve ver auditoria do tenant 1
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $audits = $this->discountModel->findAll();
        
        // ASSERT: Vazio para tenant 2
        $this->assertEmpty($audits);
    }
    
    /**
     * @test
     * REGRA: Queries de cupons devem filtrar por tenant
     */
    public function coupon_queries_must_filter_by_tenant(): void
    {
        // ARRANGE: Criar cupons para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $this->couponModel->insert([
            'code' => 'T1PROMO',
            'type' => 'percentage',
            'value' => 15.00,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $this->couponModel->insert([
            'code' => 'T2PROMO',
            'type' => 'fixed',
            'value' => 20.00,
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
        ]);
        
        // ACT: Listar cupons do tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $coupons = $this->couponModel->findAll();
        
        // ASSERT: Apenas 1 cupom (do tenant 1)
        $this->assertCount(1, $coupons);
        $this->assertEquals('T1PROMO', $coupons[0]['code']);
    }
    
    /**
     * @test
     * REGRA: Cupom com limite de uso deve controlar por tenant
     */
    public function coupon_usage_limit_must_be_per_tenant(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Criar cupom com limite de 2 usos
        $couponId = $this->couponModel->insert([
            'code' => 'LIMITED2',
            'type' => 'fixed',
            'value' => 5.00,
            'usage_limit' => 2,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        // Usar 2 vezes (limite)
        for ($i = 0; $i < 2; $i++) {
            $sale = $this->saleModel->insert([
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
                'total' => 50.00,
                'status' => 'pending',
            ]);
            
            $result = $this->discountService->applyCoupon($sale, 'LIMITED2');
            $this->assertTrue($result['success']);
        }
        
        // Tentar usar 3ª vez (excede limite)
        $sale3 = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 50.00,
            'status' => 'pending',
        ]);
        
        $result = $this->discountService->applyCoupon($sale3, 'LIMITED2');
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('limite', strtolower($result['error']));
    }
    
    /**
     * @test
     * REGRA: Descontos devem calcular corretamente
     */
    public function discounts_must_calculate_correctly(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale = $this->saleModel->insert([
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'total' => 200.00,
            'status' => 'pending',
        ]);
        
        // ACT: Aplicar 10% de desconto
        $result = $this->discountService->applyDiscount($sale, [
            'type' => 'percentage',
            'value' => 10.00,
        ]);
        
        // ASSERT: Desconto deve ser R$ 20,00
        $this->assertTrue($result['success']);
        $this->assertEquals(20.00, $result['discount_amount']);
        $this->assertEquals(180.00, $result['new_total']);
    }
}

