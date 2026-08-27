<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Models\PosSaleModel;
use App\Models\PosSaleItemModel;

/**
 * Testes de Isolamento Multi-Tenant para Itens de Venda
 * 
 * CRÍTICO: Validar que pos_sale_items está isolado por tenant após correção do CICLO 2
 */
class PosSaleItemsIsolationTest extends MultiTenantTestCase
{
    protected PosSaleModel $saleModel;
    protected PosSaleItemModel $itemModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->saleModel = new PosSaleModel();
        $this->itemModel = new PosSaleItemModel();
    }
    
    /**
     * @test
     * REGRA: Itens de venda devem ter campos de tenant preenchidos automaticamente
     */
    public function sale_items_must_have_tenant_fields(): void
    {
        // ARRANGE
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale = $this->saleModel->insert([
            'sale_number' => 'TEST-001',
            'total' => 100.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Criar item
        $itemId = $this->itemModel->insert([
            'id_pos_sale' => $sale,
            'nome' => 'Produto Teste',
            'quantidade' => 2,
            'valor_unitario' => 50.00,
            'CFOP_NFCe' => '5102',
            'NCM' => '12345678',
            'CSOSN' => '102',
            'unidade' => 'UN',
        ]);
        
        // ASSERT: Campos de tenant devem estar preenchidos
        $item = $this->itemModel->find($itemId);
        
        $this->assertNotNull($item);
        $this->assertEquals($this->tenant1Contador, $item['id_contador']);
        $this->assertEquals($this->tenant1Empresa, $item['id_empresa']);
    }
    
    /**
     * @test
     * REGRA: Itens não devem vazar entre tenants em queries diretas
     */
    public function sale_items_must_not_leak_between_tenants(): void
    {
        // ARRANGE: Tenant 1 cria venda com itens
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'sale_number' => 'T1-SALE-001',
            'total' => 150.00,
            'status' => 'finalized',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $item1 = $this->itemModel->insert([
            'id_pos_sale' => $sale1,
            'nome' => 'Produto Tenant 1',
            'quantidade' => 3,
            'valor_unitario' => 50.00,
            'CFOP_NFCe' => '5102',
            'NCM' => '12345678',
            'CSOSN' => '102',
            'unidade' => 'UN',
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        // ARRANGE: Tenant 2 cria venda com itens
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sale2 = $this->saleModel->insert([
            'sale_number' => 'T2-SALE-001',
            'total' => 200.00,
            'status' => 'finalized',
            'id_shift' => 2,
            'id_cash_register' => 2,
        ]);
        
        $item2 = $this->itemModel->insert([
            'id_pos_sale' => $sale2,
            'nome' => 'Produto Tenant 2',
            'quantidade' => 4,
            'valor_unitario' => 50.00,
            'CFOP_NFCe' => '6102',
            'NCM' => '87654321',
            'CSOSN' => '102',
            'unidade' => 'UN',
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
        ]);
        
        // ACT: Buscar itens com query manual (simulando relatório)
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $db = \Config\Database::connect();
        $items = $db->table('pos_sale_items')
                    ->where('id_empresa', $this->tenant1Empresa)
                    ->where('id_contador', $this->tenant1Contador)
                    ->get()
                    ->getResultArray();
        
        // ASSERT: Apenas itens do tenant 1
        $this->assertCount(1, $items);
        $this->assertEquals('Produto Tenant 1', $items[0]['nome']);
        $this->assertEquals($this->tenant1Empresa, $items[0]['id_empresa']);
        $this->assertEquals($this->tenant1Contador, $items[0]['id_contador']);
    }
    
    /**
     * @test
     * REGRA: JOIN entre pos_sales e pos_sale_items deve filtrar ambas as tabelas
     */
    public function join_sales_items_must_filter_both_tables(): void
    {
        // ARRANGE: Criar vendas e itens para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'sale_number' => 'JOIN-T1-001',
            'total' => 100.00,
            'status' => 'finalized',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $this->itemModel->insert([
            'id_pos_sale' => $sale1,
            'nome' => 'Item T1',
            'quantidade' => 2,
            'valor_unitario' => 50.00,
            'CFOP_NFCe' => '5102',
            'NCM' => '12345678',
            'CSOSN' => '102',
            'unidade' => 'UN',
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sale2 = $this->saleModel->insert([
            'sale_number' => 'JOIN-T2-001',
            'total' => 200.00,
            'status' => 'finalized',
            'id_shift' => 2,
            'id_cash_register' => 2,
        ]);
        
        $this->itemModel->insert([
            'id_pos_sale' => $sale2,
            'nome' => 'Item T2',
            'quantidade' => 4,
            'valor_unitario' => 50.00,
            'CFOP_NFCe' => '6102',
            'NCM' => '87654321',
            'CSOSN' => '102',
            'unidade' => 'UN',
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
        ]);
        
        // ACT: Query com JOIN para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $db = \Config\Database::connect();
        $results = $db->table('pos_sales as s')
                      ->select('s.sale_number, i.nome, i.quantidade')
                      ->join('pos_sale_items as i', 'i.id_pos_sale = s.id_pos_sale')
                      ->where('s.id_empresa', $this->tenant1Empresa)
                      ->where('s.id_contador', $this->tenant1Contador)
                      ->where('i.id_empresa', $this->tenant1Empresa)  // CRÍTICO: Filtrar ambas
                      ->where('i.id_contador', $this->tenant1Contador)
                      ->get()
                      ->getResultArray();
        
        // ASSERT: Apenas dados do tenant 1
        $this->assertCount(1, $results);
        $this->assertEquals('JOIN-T1-001', $results[0]['sale_number']);
        $this->assertEquals('Item T1', $results[0]['nome']);
    }
    
    /**
     * @test
     * REGRA: Relatório de produtos mais vendidos deve respeitar isolamento
     */
    public function product_sales_report_must_respect_isolation(): void
    {
        // ARRANGE: Criar vendas com itens para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $sale1 = $this->saleModel->insert([
            'sale_number' => 'REP-T1-001',
            'total' => 300.00,
            'status' => 'finalized',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // Produto A vendido 10 unidades no tenant 1
        $this->itemModel->insert([
            'id_pos_sale' => $sale1,
            'nome' => 'Produto A',
            'quantidade' => 10,
            'valor_unitario' => 30.00,
            'CFOP_NFCe' => '5102',
            'NCM' => '12345678',
            'CSOSN' => '102',
            'unidade' => 'UN',
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sale2 = $this->saleModel->insert([
            'sale_number' => 'REP-T2-001',
            'total' => 500.00,
            'status' => 'finalized',
            'id_shift' => 2,
            'id_cash_register' => 2,
        ]);
        
        // Produto A vendido 50 unidades no tenant 2 (muito mais!)
        $this->itemModel->insert([
            'id_pos_sale' => $sale2,
            'nome' => 'Produto A',
            'quantidade' => 50,
            'valor_unitario' => 10.00,
            'CFOP_NFCe' => '6102',
            'NCM' => '87654321',
            'CSOSN' => '102',
            'unidade' => 'UN',
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
        ]);
        
        // ACT: Relatório de produtos mais vendidos para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $db = \Config\Database::connect();
        $report = $db->table('pos_sale_items as i')
                     ->select('i.nome, SUM(i.quantidade) as total_vendido')
                     ->join('pos_sales as s', 's.id_pos_sale = i.id_pos_sale')
                     ->where('s.id_empresa', $this->tenant1Empresa)
                     ->where('s.id_contador', $this->tenant1Contador)
                     ->where('i.id_empresa', $this->tenant1Empresa)
                     ->where('i.id_contador', $this->tenant1Contador)
                     ->where('s.status', 'finalized')
                     ->groupBy('i.nome')
                     ->get()
                     ->getResultArray();
        
        // ASSERT: Apenas 10 unidades (do tenant 1), não 60 (total geral)
        $this->assertCount(1, $report);
        $this->assertEquals('Produto A', $report[0]['nome']);
        $this->assertEquals(10, $report[0]['total_vendido']);
    }
    
    /**
     * @test
     * REGRA: Índices de performance devem existir
     */
    public function performance_indexes_must_exist(): void
    {
        $db = \Config\Database::connect();
        
        // Verificar índices em pos_sale_items
        $indexes = $db->query("SHOW INDEX FROM pos_sale_items")->getResultArray();
        
        $indexNames = array_column($indexes, 'Key_name');
        
        // ASSERT: Índices de tenant devem existir
        $this->assertContains('idx_pos_sale_items_tenant', $indexNames, 
            'Índice de tenant (id_empresa, id_contador) deve existir');
        
        $this->assertContains('idx_pos_sale_items_sale_tenant', $indexNames,
            'Índice composto (id_pos_sale, id_empresa, id_contador) deve existir');
    }
}

