<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\TenantCache;
use Config\TenantCache as TenantCacheConfig;

/**
 * Testes Unitários para Sistema de Cache Multi-Tenant
 * 
 * Valida isolamento, anti-poisoning e funcionalidades
 */
class TenantCacheTest extends CIUnitTestCase
{
    /**
     * Cache para tenant 1
     */
    protected TenantCache $cache1;
    
    /**
     * Cache para tenant 2
     */
    protected TenantCache $cache2;
    
    /**
     * Diretório de cache
     */
    protected string $cacheDir;
    
    /**
     * Configurar testes
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheDir = WRITEPATH . 'cache/';
        
        // Limpar cache antes dos testes
        $this->cleanupCache();
        
        // Criar instâncias para diferentes tenants
        $this->cache1 = new TenantCache(1, 10);
        $this->cache2 = new TenantCache(2, 20);
    }
    
    /**
     * Limpar após testes
     */
    protected function tearDown(): void
    {
        $this->cleanupCache();
        parent::tearDown();
    }
    
    /**
     * Teste 1: Salvar e recuperar cache corretamente
     */
    public function testCacheSaveAndGet(): void
    {
        $key = 'test_key';
        $value = ['data' => 'test_value', 'number' => 123];
        
        // Salvar no cache
        $saved = $this->cache1->save($key, $value);
        $this->assertTrue($saved, 'Cache deve ser salvo com sucesso');
        
        // Recuperar do cache
        $retrieved = $this->cache1->get($key);
        $this->assertEquals($value, $retrieved, 'Valor recuperado deve ser igual ao salvo');
        
        // Verificar que retorna default quando não existe
        $notFound = $this->cache1->get('non_existent_key', 'default');
        $this->assertEquals('default', $notFound, 'Deve retornar valor padrão para chave inexistente');
    }
    
    /**
     * Teste 2: Método remember executa callback apenas em miss
     */
    public function testCacheRemember(): void
    {
        $key = 'remember_test';
        $callbackExecuted = false;
        
        $callback = function() use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'callback_result';
        };
        
        // Primeira chamada - deve executar callback
        $result1 = $this->cache1->remember($key, $callback);
        $this->assertTrue($callbackExecuted, 'Callback deve ser executado na primeira chamada');
        $this->assertEquals('callback_result', $result1, 'Deve retornar resultado do callback');
        
        // Reset flag
        $callbackExecuted = false;
        
        // Segunda chamada - não deve executar callback
        $result2 = $this->cache1->remember($key, $callback);
        $this->assertFalse($callbackExecuted, 'Callback não deve ser executado na segunda chamada');
        $this->assertEquals('callback_result', $result2, 'Deve retornar valor do cache');
    }
    
    /**
     * Teste 3: Isolamento entre tenants
     */
    public function testCacheIsolation(): void
    {
        $key = 'isolation_test';
        $value1 = 'tenant_1_data';
        $value2 = 'tenant_2_data';
        
        // Salvar dados diferentes para cada tenant
        $this->cache1->save($key, $value1);
        $this->cache2->save($key, $value2);
        
        // Verificar que cada tenant vê apenas seus dados
        $retrieved1 = $this->cache1->get($key);
        $retrieved2 = $this->cache2->get($key);
        
        $this->assertEquals($value1, $retrieved1, 'Tenant 1 deve ver apenas seus dados');
        $this->assertEquals($value2, $retrieved2, 'Tenant 2 deve ver apenas seus dados');
        $this->assertNotEquals($retrieved1, $retrieved2, 'Dados devem ser diferentes entre tenants');
    }
    
    /**
     * Teste 4: Proteção anti-poisoning
     */
    public function testCachePoisoningProtection(): void
    {
        $key = 'poisoning_test';
        $value = 'legitimate_data';
        
        // Salvar dados legítimos
        $this->cache1->save($key, $value);
        
        // Simular poisoning alterando tenant_id no arquivo
        $filename = $this->getCacheFilename('1:10', $key);
        $this->assertTrue(file_exists($filename), 'Arquivo de cache deve existir');
        
        $cacheData = unserialize(file_get_contents($filename));
        $cacheData['_tenant_id'] = '2:20'; // Alterar tenant_id
        file_put_contents($filename, serialize($cacheData));
        
        // Tentar recuperar - deve detectar poisoning e retornar null
        $retrieved = $this->cache1->get($key, 'default');
        $this->assertEquals('default', $retrieved, 'Deve retornar default ao detectar poisoning');
        
        // Arquivo corrompido deve ter sido deletado
        $this->assertFalse(file_exists($filename), 'Arquivo corrompido deve ser deletado');
    }
    
    /**
     * Teste 5: Expiração de cache
     */
    public function testCacheExpiration(): void
    {
        $key = 'expiration_test';
        $value = 'expiring_data';
        $ttl = 1; // 1 segundo
        
        // Salvar com TTL curto
        $this->cache1->save($key, $value, $ttl);
        
        // Verificar que está disponível imediatamente
        $retrieved = $this->cache1->get($key);
        $this->assertEquals($value, $retrieved, 'Cache deve estar disponível imediatamente');
        
        // Aguardar expiração
        sleep(2);
        
        // Verificar que expirou
        $expired = $this->cache1->get($key, 'expired');
        $this->assertEquals('expired', $expired, 'Cache deve ter expirado');
    }
    
    /**
     * Teste 6: Deletar cache
     */
    public function testCacheInvalidation(): void
    {
        $key = 'delete_test';
        $value = 'data_to_delete';
        
        // Salvar e verificar
        $this->cache1->save($key, $value);
        $retrieved = $this->cache1->get($key);
        $this->assertEquals($value, $retrieved, 'Cache deve estar salvo');
        
        // Deletar
        $deleted = $this->cache1->delete($key);
        $this->assertTrue($deleted, 'Delete deve retornar true');
        
        // Verificar que foi deletado
        $notFound = $this->cache1->get($key, 'deleted');
        $this->assertEquals('deleted', $notFound, 'Cache deve ter sido deletado');
    }
    
    /**
     * Teste 7: Invalidação por grupo
     */
    public function testCacheInvalidateGroup(): void
    {
        $keys = [
            'products_list',
            'products_active', 
            'products_search',
            'customer_list' // Não deve ser afetado
        ];
        
        // Salvar múltiplos caches
        foreach ($keys as $key) {
            $this->cache1->save($key, "data_for_{$key}");
        }
        
        // Verificar que todos estão salvos
        foreach ($keys as $key) {
            $this->assertNotNull($this->cache1->get($key), "Cache {$key} deve existir");
        }
        
        // Invalidar grupo 'products'
        $deleted = $this->cache1->invalidateGroup('products');
        $this->assertGreaterThan(0, $deleted, 'Deve ter deletado pelo menos um arquivo');
        
        // Verificar que caches de produtos foram deletados
        $this->assertNull($this->cache1->get('products_list'), 'products_list deve ter sido deletado');
        $this->assertNull($this->cache1->get('products_active'), 'products_active deve ter sido deletado');
        $this->assertNull($this->cache1->get('products_search'), 'products_search deve ter sido deletado');
        
        // Verificar que outros caches não foram afetados
        $this->assertNotNull($this->cache1->get('customer_list'), 'customer_list não deve ter sido afetado');
    }
    
    /**
     * Teste 8: Estatísticas de cache
     */
    public function testCacheStats(): void
    {
        // Gerar algumas operações
        $this->cache1->save('stats_test_1', 'data1');
        $this->cache1->save('stats_test_2', 'data2');
        $this->cache1->get('stats_test_1'); // Hit
        $this->cache1->get('nonexistent'); // Miss
        
        $stats = $this->cache1->getStats();
        
        $this->assertIsArray($stats, 'Stats deve retornar array');
        $this->assertArrayHasKey('tenant_id', $stats, 'Stats deve conter tenant_id');
        $this->assertArrayHasKey('cache_files', $stats, 'Stats deve conter cache_files');
        $this->assertArrayHasKey('hit_rate_percent', $stats, 'Stats deve conter hit_rate_percent');
        $this->assertEquals('1:10', $stats['tenant_id'], 'Tenant ID deve estar correto');
        $this->assertGreaterThanOrEqual(2, $stats['cache_files'], 'Deve ter pelo menos 2 arquivos');
    }
    
    /**
     * Teste 9: Contadores increment/decrement
     */
    public function testCacheIncrement(): void
    {
        $key = 'counter_test';
        
        // Incrementar a partir de 0
        $result1 = $this->cache1->increment($key);
        $this->assertEquals(1, $result1, 'Primeiro increment deve retornar 1');
        
        // Incrementar novamente
        $result2 = $this->cache1->increment($key, 5);
        $this->assertEquals(6, $result2, 'Increment de 5 deve retornar 6');
        
        // Decrementar
        $result3 = $this->cache1->decrement($key, 2);
        $this->assertEquals(4, $result3, 'Decrement de 2 deve retornar 4');
        
        // Decrementar abaixo de 0
        $result4 = $this->cache1->decrement($key, 10);
        $this->assertEquals(0, $result4, 'Decrement não deve ir abaixo de 0');
    }
    
    /**
     * Teste 10: Flush de cache do tenant
     */
    public function testCacheFlush(): void
    {
        // Salvar dados em ambos os tenants
        $this->cache1->save('flush_test_1', 'data1');
        $this->cache1->save('flush_test_2', 'data2');
        $this->cache2->save('flush_test_3', 'data3');
        
        // Verificar que dados existem
        $this->assertNotNull($this->cache1->get('flush_test_1'));
        $this->assertNotNull($this->cache1->get('flush_test_2'));
        $this->assertNotNull($this->cache2->get('flush_test_3'));
        
        // Flush apenas do tenant 1
        $flushed = $this->cache1->flush();
        $this->assertTrue($flushed, 'Flush deve retornar true');
        
        // Verificar que cache do tenant 1 foi limpo
        $this->assertNull($this->cache1->get('flush_test_1'), 'Cache do tenant 1 deve ter sido limpo');
        $this->assertNull($this->cache1->get('flush_test_2'), 'Cache do tenant 1 deve ter sido limpo');
        
        // Verificar que cache do tenant 2 não foi afetado
        $this->assertNotNull($this->cache2->get('flush_test_3'), 'Cache do tenant 2 não deve ser afetado');
    }
    
    /**
     * Teste 11: Operações múltiplas
     */
    public function testMultipleOperations(): void
    {
        $data = [
            'multi_1' => 'value1',
            'multi_2' => 'value2',
            'multi_3' => 'value3'
        ];
        
        // Salvar múltiplos valores
        $saved = $this->cache1->saveMultiple($data);
        $this->assertTrue($saved, 'SaveMultiple deve retornar true');
        
        // Recuperar múltiplos valores
        $keys = array_keys($data);
        $retrieved = $this->cache1->getMultiple($keys);
        
        $this->assertIsArray($retrieved, 'GetMultiple deve retornar array');
        $this->assertEquals($data, $retrieved, 'Dados recuperados devem ser iguais aos salvos');
    }
    
    /**
     * Teste 12: TTL personalizado por tipo
     */
    public function testCustomTTLByType(): void
    {
        // Usar chaves que correspondem a padrões específicos
        $this->cache1->save('dashboard_stats', 'dashboard_data'); // TTL: 300s
        $this->cache1->save('config_setting', 'config_data');    // TTL: 86400s
        $this->cache1->save('custom_key', 'custom_data', 10);    // TTL: 10s
        
        // Verificar que arquivos foram criados
        $dashboardFile = $this->getCacheFilename('1:10', 'dashboard_stats');
        $configFile = $this->getCacheFilename('1:10', 'config_setting');
        $customFile = $this->getCacheFilename('1:10', 'custom_key');
        
        $this->assertTrue(file_exists($dashboardFile), 'Arquivo dashboard deve existir');
        $this->assertTrue(file_exists($configFile), 'Arquivo config deve existir');
        $this->assertTrue(file_exists($customFile), 'Arquivo custom deve existir');
        
        // Verificar TTLs nos metadados
        $dashboardData = unserialize(file_get_contents($dashboardFile));
        $configData = unserialize(file_get_contents($configFile));
        $customData = unserialize(file_get_contents($customFile));
        
        $this->assertEquals(300, $dashboardData['_ttl'], 'TTL dashboard deve ser 300s');
        $this->assertEquals(86400, $configData['_ttl'], 'TTL config deve ser 86400s');
        $this->assertEquals(10, $customData['_ttl'], 'TTL custom deve ser 10s');
    }
    
    /**
     * Métodos auxiliares
     */
    
    /**
     * Limpar todos os arquivos de cache
     */
    protected function cleanupCache(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }
        
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Obter nome do arquivo de cache
     */
    protected function getCacheFilename(string $tenantId, string $key): string
    {
        $sanitizedTenant = str_replace([':', '/', '\\', ' '], '_', $tenantId);
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . "{$sanitizedTenant}_{$sanitizedKey}.cache";
    }
    
    /**
     * Simular sessão para tenant
     */
    protected function mockSession(int $idContador, int $idEmpresa): void
    {
        $_SESSION['id_contador'] = $idContador;
        $_SESSION['id_empresa'] = $idEmpresa;
    }
}
