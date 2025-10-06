<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Testes E2E para Modo Offline e Sincronização Multi-Tenant
 * 
 * @group offline
 * @group e2e
 */
class OfflineSyncTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $refresh = true;
    protected $migrate = true;
    
    protected $idContador = 1;
    protected $idEmpresa = 1;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Simular sessão do usuário
        $_SESSION = [
            'id_contador' => $this->idContador,
            'id_empresa' => $this->idEmpresa,
            'id' => 1,
            'nome' => 'Teste User'
        ];
    }

    /**
     * Testa registro no outbox com validação de tenant
     */
    public function testOutboxRecordWithTenant()
    {
        $payload = [
            'id_pos_sale' => 123,
            'id_contador' => $this->idContador,
            'id_empresa' => $this->idEmpresa,
            'total' => 100.50,
            'status' => 'completed'
        ];
        
        \App\Libraries\Outbox::record('pos_sales', ['id_pos_sale' => 123], 'insert', $payload);
        
        $result = $this->db->table('outbox_events')
            ->where('table_name', 'pos_sales')
            ->where('id_contador', $this->idContador)
            ->where('id_empresa', $this->idEmpresa)
            ->get()
            ->getRowArray();
        
        $this->assertNotNull($result);
        $this->assertEquals('insert', $result['operation']);
        $this->assertEquals('pending', $result['status']);
        $this->assertEquals($this->idContador, $result['id_contador']);
    }

    /**
     * Testa isolamento de tenant no outbox
     */
    public function testOutboxTenantIsolation()
    {
        // Tenant 1
        \App\Libraries\Outbox::record('products', ['id' => 1], 'insert', [
            'id_contador' => 1,
            'id_empresa' => 1,
            'name' => 'Produto Tenant 1'
        ]);
        
        // Tenant 2
        \App\Libraries\Outbox::record('products', ['id' => 2], 'insert', [
            'id_contador' => 2,
            'id_empresa' => 2,
            'name' => 'Produto Tenant 2'
        ]);
        
        // Buscar eventos do tenant 1
        $tenant1Events = $this->db->table('outbox_events')
            ->where('id_contador', 1)
            ->where('id_empresa', 1)
            ->get()
            ->getResultArray();
        
        $this->assertCount(1, $tenant1Events);
        $this->assertStringContainsString('Tenant 1', $tenant1Events[0]['payload']);
    }

    /**
     * Testa sincronização com resolução de conflitos
     */
    public function testSyncConflictResolution()
    {
        // Criar registro no local
        $localData = [
            'id_produto' => 100,
            'id_contador' => $this->idContador,
            'id_empresa' => $this->idEmpresa,
            'nome' => 'Produto Local',
            'preco' => 50.00,
            'updated_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ];
        
        $this->db->table('produtos')->insert($localData);
        
        // Registrar no outbox
        \App\Libraries\Outbox::record('produtos', ['id_produto' => 100], 'insert', $localData);
        
        // Simular sincronização
        $sync = new \App\Commands\SyncCloud();
        $sync->runWithOptions([
            'tables' => [],
            'limit' => 10,
            'dry' => true,
            'useOutbox' => true
        ]);
        
        // Verificar que evento foi processado (em dry-run não marca como processado)
        $event = $this->db->table('outbox_events')
            ->where('table_name', 'produtos')
            ->where('id_contador', $this->idContador)
            ->get()
            ->getRowArray();
        
        $this->assertNotNull($event);
    }

    /**
     * Testa auditoria de operações offline
     */
    public function testOfflineAuditLog()
    {
        $saleData = [
            'id_pos_sale' => 456,
            'id_contador' => $this->idContador,
            'id_empresa' => $this->idEmpresa,
            'total' => 250.00
        ];
        
        \App\Libraries\OfflineAudit::log(
            'create_sale',
            'pos_sale',
            456,
            $saleData,
            'pending'
        );
        
        $audit = $this->db->table('offline_audit_log')
            ->where('entity_type', 'pos_sale')
            ->where('entity_id', '456')
            ->where('id_contador', $this->idContador)
            ->get()
            ->getRowArray();
        
        $this->assertNotNull($audit);
        $this->assertEquals('create_sale', $audit['action']);
        $this->assertEquals('pending', $audit['status']);
    }

    /**
     * Testa estatísticas de sincronização
     */
    public function testSyncStats()
    {
        // Criar eventos pendentes
        for ($i = 1; $i <= 5; $i++) {
            \App\Libraries\Outbox::record("test_table_{$i}", ['id' => $i], 'insert', [
                'id_contador' => $this->idContador,
                'id_empresa' => $this->idEmpresa
            ]);
        }
        
        $stats = \App\Libraries\OfflineAudit::getSyncStats($this->idContador, $this->idEmpresa);
        
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('synced_last_24h', $stats);
        $this->assertArrayHasKey('failed', $stats);
    }

    /**
     * Testa retry de eventos falhados
     */
    public function testEventRetryMechanism()
    {
        // Criar evento
        \App\Libraries\Outbox::record('test_retry', ['id' => 1], 'insert', [
            'id_contador' => $this->idContador,
            'id_empresa' => $this->idEmpresa
        ]);
        
        $event = $this->db->table('outbox_events')
            ->where('table_name', 'test_retry')
            ->get()
            ->getRowArray();
        
        $eventId = $event['id'];
        
        // Marcar como falho 3 vezes
        for ($i = 0; $i < 3; $i++) {
            \App\Libraries\Outbox::markFailed($eventId, 'Erro de teste');
        }
        
        $updated = $this->db->table('outbox_events')->find($eventId);
        
        $this->assertEquals(3, $updated['retry_count']);
        $this->assertEquals('pending', $updated['status']); // Ainda pode tentar
    }

    /**
     * Testa limite máximo de retries
     */
    public function testMaxRetryLimit()
    {
        \App\Libraries\Outbox::record('test_max_retry', ['id' => 1], 'insert', [
            'id_contador' => $this->idContador,
            'id_empresa' => $this->idEmpresa
        ]);
        
        $event = $this->db->table('outbox_events')
            ->where('table_name', 'test_max_retry')
            ->get()
            ->getRowArray();
        
        // Falhar 5 vezes (limite)
        for ($i = 0; $i < 5; $i++) {
            \App\Libraries\Outbox::markFailed($event['id'], 'Erro permanente');
        }
        
        $final = $this->db->table('outbox_events')->find($event['id']);
        
        $this->assertEquals(5, $final['retry_count']);
        $this->assertEquals('failed', $final['status']); // Status final
    }

    /**
     * Testa API de health check
     */
    public function testHealthCheckEndpoint()
    {
        $result = $this->get('/api/health-check');
        
        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'online']);
    }

    /**
     * Testa API de estatísticas com autenticação
     */
    public function testStatsEndpointRequiresAuth()
    {
        // Sem sessão
        unset($_SESSION);
        
        $result = $this->get('/api/sync/stats');
        
        $result->assertStatus(401);
    }

    /**
     * Testa API de estatísticas com sessão válida
     */
    public function testStatsEndpointWithValidSession()
    {
        $result = $this->get('/api/sync/stats');
        
        $result->assertStatus(200);
        $result->assertJSONFragment(['is_offline']);
    }
}
