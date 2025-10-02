<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\TefService;
use App\Models\TefTransactionModel;

class TefMultiTenantTest extends MultiTenantTestCase
{
    protected TefService $tefService;
    protected TefTransactionModel $tefModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->tefService = new TefService();
        $this->tefModel = new TefTransactionModel();
    }
    
    /**
     * @test
     * REGRA: Transações TEF devem ser isoladas por tenant
     */
    public function tef_transactions_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar transação para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result1 = $this->tefService->authorize([
            'amount' => 100.00,
            'card_type' => 'credit',
            'installments' => 1,
            'card_data' => [
                'number' => '4111111111111111',
                'holder' => 'TESTE TENANT 1',
                'expiry' => '12/2030',
                'cvv' => '123',
            ],
        ]);
        
        $this->assertTrue($result1['success']);
        $idTransaction1 = $result1['transaction']['id_tef_transaction'];
        
        // ACT: Tentar acessar com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $transaction = $this->tefModel->find($idTransaction1);
        
        // ASSERT: Não deve retornar transação de outro tenant
        $this->assertNull($transaction, 'Não deve acessar transação TEF de outro tenant');
    }
    
    /**
     * @test
     * REGRA: Autorização TEF deve validar tenant
     */
    public function tef_authorization_must_require_valid_tenant(): void
    {
        // Remover tenant da sessão
        session()->remove('id_empresa');
        session()->remove('id_contador');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tenant');
        
        $this->tefService->authorize([
            'amount' => 100.00,
            'card_type' => 'credit',
            'installments' => 1,
            'card_data' => [],
        ]);
    }
    
    /**
     * @test
     * REGRA: Queries TEF devem filtrar por tenant
     */
    public function tef_queries_must_filter_by_tenant(): void
    {
        // ARRANGE: Criar transações para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        $result1 = $this->tefService->authorize([
            'amount' => 50.00,
            'card_type' => 'credit',
            'installments' => 1,
            'card_data' => ['number' => '4111111111111111'],
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        $result2 = $this->tefService->authorize([
            'amount' => 75.00,
            'card_type' => 'debit',
            'installments' => 1,
            'card_data' => ['number' => '5111111111111111'],
        ]);
        
        // ACT: Buscar transações do tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $transactions = $this->tefModel->findAll();
        
        // ASSERT: Deve retornar apenas transações do tenant 1
        $this->assertCount(1, $transactions);
        $this->assertNoTenantLeakage($transactions, $this->tenant1Contador, $this->tenant1Empresa);
    }
    
    /**
     * @test
     * REGRA: Confirmar/Cancelar deve validar ownership
     */
    public function tef_confirm_must_validate_tenant_ownership(): void
    {
        // ARRANGE: Criar transação para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result = $this->tefService->authorize([
            'amount' => 100.00,
            'card_type' => 'credit',
            'installments' => 1,
            'card_data' => ['number' => '4111111111111111'],
        ]);
        
        $idTransaction = $result['transaction']['id_tef_transaction'];
        
        // ACT: Tentar confirmar com tenant 2 (não é dono)
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $confirmResult = $this->tefService->confirm($idTransaction);
        
        // ASSERT: Deve falhar
        $this->assertFalse($confirmResult['success']);
        $this->assertStringContainsString('não encontrada', $confirmResult['error']);
    }
    
    /**
     * @test
     * REGRA: Cancelamento deve validar ownership e prazo
     */
    public function tef_cancel_must_validate_ownership(): void
    {
        // ARRANGE
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result = $this->tefService->authorize([
            'amount' => 200.00,
            'card_type' => 'credit',
            'installments' => 2,
            'card_data' => ['number' => '4111111111111111'],
        ]);
        
        $idTransaction = $result['transaction']['id_tef_transaction'];
        
        // Confirmar primeiro
        $this->tefService->confirm($idTransaction);
        
        // ACT: Tentar cancelar com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $cancelResult = $this->tefService->cancel($idTransaction);
        
        // ASSERT: Deve falhar
        $this->assertFalse($cancelResult['success']);
        $this->assertStringContainsString('não encontrada', $cancelResult['error']);
    }
}

