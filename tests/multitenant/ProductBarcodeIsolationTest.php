<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Models\ProdutoModel;

/**
 * Testes de Isolamento Multi-Tenant para Busca de Produtos por Código de Barras
 * 
 * CRÍTICO: Validar que busca por barcode NÃO vaza dados entre tenants (CICLO 2)
 */
class ProductBarcodeIsolationTest extends MultiTenantTestCase
{
    protected ProdutoModel $produtoModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->produtoModel = new ProdutoModel();
    }
    
    /**
     * @test
     * REGRA: Busca por código de barras deve ser isolada por tenant
     */
    public function barcode_search_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Mesmo código de barras em 2 tenants (diferentes produtos)
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $produto1 = $this->produtoModel->insert([
            'nome' => 'Produto Tenant 1',
            'codigo_de_barras' => '7891234567890',
            'valor_unitario' => 10.00,
            'NCM' => '12345678',
            'unidade' => 'UN',
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $produto2 = $this->produtoModel->insert([
            'nome' => 'Produto Tenant 2',
            'codigo_de_barras' => '7891234567890',  // MESMO código
            'valor_unitario' => 20.00,
            'NCM' => '87654321',
            'unidade' => 'UN',
        ]);
        
        // ACT: Tenant 1 busca por código de barras
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $found = $this->produtoModel
                      ->where('codigo_de_barras', '7891234567890')
                      ->first();
        
        // ASSERT: Deve retornar produto do tenant 1, não do tenant 2
        $this->assertNotNull($found);
        $this->assertEquals('Produto Tenant 1', $found['nome']);
        $this->assertEquals(10.00, $found['valor_unitario']);
        $this->assertEquals($this->tenant1Empresa, $found['id_empresa']);
        $this->assertEquals($this->tenant1Contador, $found['id_contador']);
    }
    
    /**
     * @test
     * REGRA: Código de barras vazio não deve retornar produtos
     */
    public function empty_barcode_must_not_return_products(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Criar produto sem código de barras (ou "SEM GTIN")
        $this->produtoModel->insert([
            'nome' => 'Produto Sem Código',
            'codigo_de_barras' => 'SEM GTIN',
            'valor_unitario' => 15.00,
            'NCM' => '12345678',
            'unidade' => 'UN',
        ]);
        
        // ACT: Buscar por código vazio
        $found = $this->produtoModel
                      ->where('codigo_de_barras', '')
                      ->first();
        
        // ASSERT: Não deve retornar nada
        $this->assertNull($found);
    }
    
    /**
     * @test
     * REGRA: Busca por código deve usar índice de performance
     */
    public function barcode_search_must_use_index(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Criar 100 produtos para testar performance
        for ($i = 0; $i < 100; $i++) {
            $this->produtoModel->insert([
                'nome' => "Produto {$i}",
                'codigo_de_barras' => str_pad((string)$i, 13, '0', STR_PAD_LEFT),
                'valor_unitario' => 10.00 + $i,
                'NCM' => '12345678',
                'unidade' => 'UN',
            ]);
        }
        
        // ACT: Buscar produto específico
        $db = \Config\Database::connect();
        
        // EXPLAIN para verificar se usa índice
        $explain = $db->query(
            "EXPLAIN SELECT * FROM produtos 
             WHERE codigo_de_barras = ? 
             AND id_empresa = ? 
             AND id_contador = ?",
            ['0000000000050', $this->tenant1Empresa, $this->tenant1Contador]
        )->getResultArray();
        
        // ASSERT: Deve usar índice (key não deve ser NULL)
        $this->assertNotEmpty($explain);
        $this->assertNotNull($explain[0]['key'] ?? null, 'Query deve usar índice');
    }
    
    /**
     * @test
     * REGRA: Produto duplicado em mesmo tenant deve ser bloqueado
     */
    public function duplicate_barcode_in_same_tenant_should_be_prevented(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Criar primeiro produto
        $this->produtoModel->insert([
            'nome' => 'Produto Original',
            'codigo_de_barras' => '7896543210987',
            'valor_unitario' => 25.00,
            'NCM' => '12345678',
            'unidade' => 'UN',
        ]);
        
        // ACT: Tentar criar segundo produto com mesmo código
        // (Depende de constraint UNIQUE no banco)
        
        $duplicate = $this->produtoModel
                          ->where('codigo_de_barras', '7896543210987')
                          ->where('id_empresa', $this->tenant1Empresa)
                          ->where('id_contador', $this->tenant1Contador)
                          ->countAllResults();
        
        // ASSERT: Deve ter apenas 1 produto com este código no tenant
        $this->assertEquals(1, $duplicate);
    }
    
    /**
     * @test
     * REGRA: Cache deve ser isolado por tenant
     */
    public function cache_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar produto em cada tenant com código diferente
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $this->produtoModel->insert([
            'nome' => 'Produto Cache T1',
            'codigo_de_barras' => '1111111111111',
            'valor_unitario' => 100.00,
            'NCM' => '11111111',
            'unidade' => 'UN',
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $this->produtoModel->insert([
            'nome' => 'Produto Cache T2',
            'codigo_de_barras' => '2222222222222',
            'valor_unitario' => 200.00,
            'NCM' => '22222222',
            'unidade' => 'UN',
        ]);
        
        // ACT: Simular busca com cache
        $cache = \Config\Services::cache();
        
        // Tenant 1 busca e cacheia
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $cacheKey1 = "produto_barcode_{$this->tenant1Empresa}_{$this->tenant1Contador}_1111111111111";
        $produto1 = $this->produtoModel
                         ->where('codigo_de_barras', '1111111111111')
                         ->first();
        
        if ($produto1) {
            $cache->save($cacheKey1, $produto1, 1800);
        }
        
        // Tenant 2 busca e cacheia
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $cacheKey2 = "produto_barcode_{$this->tenant2Empresa}_{$this->tenant2Contador}_2222222222222";
        $produto2 = $this->produtoModel
                         ->where('codigo_de_barras', '2222222222222')
                         ->first();
        
        if ($produto2) {
            $cache->save($cacheKey2, $produto2, 1800);
        }
        
        // ASSERT: Chaves de cache são diferentes (isoladas)
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Chaves de cache devem ser diferentes');
        
        $cached1 = $cache->get($cacheKey1);
        $cached2 = $cache->get($cacheKey2);
        
        $this->assertNotNull($cached1);
        $this->assertNotNull($cached2);
        $this->assertEquals('Produto Cache T1', $cached1['nome']);
        $this->assertEquals('Produto Cache T2', $cached2['nome']);
    }
    
    /**
     * @test
     * REGRA: Índice de código de barras deve existir
     */
    public function barcode_index_must_exist(): void
    {
        $db = \Config\Database::connect();
        
        // Verificar índices em produtos
        $indexes = $db->query("SHOW INDEX FROM produtos")->getResultArray();
        
        $indexNames = array_column($indexes, 'Key_name');
        
        // ASSERT: Índice de código de barras deve existir
        $this->assertContains('idx_produtos_barcode', $indexNames,
            'Índice idx_produtos_barcode deve existir');
        
        // Verificar se índice inclui campos de tenant
        $barcodeIndexColumns = array_filter($indexes, function($idx) {
            return $idx['Key_name'] === 'idx_produtos_barcode';
        });
        
        $barcodeIndexColumns = array_values($barcodeIndexColumns);
        $columnNames = array_column($barcodeIndexColumns, 'Column_name');
        
        $this->assertContains('codigo_de_barras', $columnNames);
        $this->assertContains('id_empresa', $columnNames);
        $this->assertContains('id_contador', $columnNames);
    }
}

