<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\PixService;
use App\Models\PixTransactionModel;

class PixMultiTenantTest extends MultiTenantTestCase
{
    protected PixService $pixService;
    protected PixTransactionModel $pixModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->pixService = new PixService();
        $this->pixModel = new PixTransactionModel();
    }
    
    /**
     * @test
     * REGRA: Transações PIX devem ser isoladas por tenant
     */
    public function pix_transactions_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar transação PIX para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result1 = $this->pixService->generate([
            'amount' => 100.00,
            'description' => 'Teste Tenant 1',
        ]);
        
        $this->assertTrue($result1['success']);
        $idTransaction1 = $result1['transaction']['id_pix_transaction'];
        
        // ACT: Tentar acessar com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $transaction = $this->pixModel->find($idTransaction1);
        
        // ASSERT: Não deve retornar transação de outro tenant
        $this->assertNull($transaction, 'Não deve acessar transação PIX de outro tenant');
    }
    
    /**
     * @test
     * REGRA: Gerar QR Code deve validar tenant
     */
    public function pix_generate_must_require_valid_tenant(): void
    {
        // Remover tenant da sessão
        session()->remove('id_empresa');
        session()->remove('id_contador');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tenant');
        
        $this->pixService->generate([
            'amount' => 100.00,
            'description' => 'Teste',
        ]);
    }
    
    /**
     * @test
     * REGRA: Queries PIX devem filtrar por tenant
     */
    public function pix_queries_must_filter_by_tenant(): void
    {
        // ARRANGE: Criar transações para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        $result1 = $this->pixService->generate(['amount' => 50.00, 'description' => 'T1']);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        $result2 = $this->pixService->generate(['amount' => 75.00, 'description' => 'T2']);
        
        // ACT: Buscar transações do tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $transactions = $this->pixModel->findAll();
        
        // ASSERT: Deve retornar apenas transações do tenant 1
        $this->assertCount(1, $transactions);
        $this->assertNoTenantLeakage($transactions, $this->tenant1Contador, $this->tenant1Empresa);
    }
    
    /**
     * @test
     * REGRA: Confirmar PIX deve validar ownership
     */
    public function pix_confirm_must_validate_tenant_ownership(): void
    {
        // ARRANGE: Criar transação para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result = $this->pixService->generate([
            'amount' => 100.00,
            'description' => 'Teste Ownership',
        ]);
        
        $txid = $result['transaction']['txid'];
        
        // ACT: Tentar confirmar com tenant 2 (não é dono)
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $confirmResult = $this->pixService->confirm($txid, 'E2E123456789');
        
        // ASSERT: Deve falhar
        $this->assertFalse($confirmResult['success']);
        $this->assertStringContainsString('não encontrada', $confirmResult['error']);
    }
    
    /**
     * @test
     * REGRA: PIX expirado deve ser marcado automaticamente
     */
    public function expired_pix_must_be_auto_cancelled(): void
    {
        // ARRANGE: Criar transação PIX expirada
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $db = \Config\Database::connect();
        $db->table('pix_transactions')->insert([
            'txid' => 'EXPIRED-TEST-001',
            'provider' => 'mercadopago',
            'amount' => 50.00,
            'qr_code' => 'mock_qr_code',
            'status' => 'pending',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
            'created_at' => date('Y-m-d H:i:s', strtotime('-20 minutes')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-20 minutes')),
        ]);
        
        // ACT: Executar expiração
        $expired = $this->pixService->expireOld();
        
        // ASSERT: Deve ter expirado 1
        $this->assertEquals(1, $expired);
        
        // Verificar que status mudou
        $transaction = $this->pixModel->where('txid', 'EXPIRED-TEST-001')->first();
        $this->assertEquals('expired', $transaction['status']);
    }
    
    /**
     * @test
     * REGRA: Webhook deve validar tenant antes de confirmar
     */
    public function webhook_must_validate_tenant_before_confirming(): void
    {
        // ARRANGE: Criar transação para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result = $this->pixService->generate([
            'amount' => 200.00,
            'description' => 'Teste Webhook',
        ]);
        
        $txid = $result['transaction']['txid'];
        
        // ACT: Confirmar via webhook (simula webhook externo)
        $confirmResult = $this->pixService->confirm($txid, 'E2E789012345');
        
        // ASSERT: Deve funcionar
        $this->assertTrue($confirmResult['success']);
        
        // Verificar que pertence ao tenant correto
        $transaction = $this->pixModel->where('txid', $txid)->first();
        $this->assertTenantOwnership($transaction, $this->tenant1Contador, $this->tenant1Empresa);
        $this->assertEquals('confirmed', $transaction['status']);
    }
}

