<?php

namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Models\CashMovementModel;
use App\Models\ShiftModel;
use App\Models\CashRegisterModel;

/**
 * Testes de Isolamento Multi-Tenant para Movimentos de Caixa (Sangria/Suprimento)
 * 
 * NOVO RECURSO: Implementado no CICLO 2
 */
class CashMovementIsolationTest extends MultiTenantTestCase
{
    protected CashMovementModel $movementModel;
    protected ShiftModel $shiftModel;
    protected CashRegisterModel $registerModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->movementModel = new CashMovementModel();
        $this->shiftModel = new ShiftModel();
        $this->registerModel = new CashRegisterModel();
    }
    
    /**
     * @test
     * REGRA: Movimentos de caixa devem ser isolados por tenant
     */
    public function cash_movements_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar caixa e turno para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $register1 = $this->registerModel->insert([
            'name' => 'Caixa Tenant 1',
            'location' => 'Loja 1',
            'status' => 'open',
        ]);
        
        $shift1 = $this->shiftModel->insert([
            'id_cash_register' => $register1,
            'opened_by' => 1,
            'opening_amount' => 100.00,
            'status' => 'open',
        ]);
        
        // Sangria de R$ 50
        $movement1 = $this->movementModel->insert([
            'id_shift' => $shift1,
            'id_cash_register' => $register1,
            'type' => 'withdrawal',
            'amount' => 50.00,
            'reason' => 'Pagamento fornecedor',
            'performed_by' => 1,
        ]);
        
        // ACT: Tenant 2 não deve ver movimento do tenant 1
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $movements = $this->movementModel->findAll();
        
        // ASSERT: Vazio para tenant 2
        $this->assertEmpty($movements, 'Tenant 2 não deve ver movimentos do tenant 1');
    }
    
    /**
     * @test
     * REGRA: Sangria deve validar tenant do turno
     */
    public function withdrawal_must_validate_shift_tenant(): void
    {
        // ARRANGE: Tenant 1 cria turno
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $register1 = $this->registerModel->insert([
            'name' => 'Caixa Único',
            'location' => 'Loja A',
            'status' => 'open',
        ]);
        
        $shift1 = $this->shiftModel->insert([
            'id_cash_register' => $register1,
            'opened_by' => 1,
            'opening_amount' => 200.00,
            'status' => 'open',
        ]);
        
        // ACT: Tenant 2 tenta fazer sangria no turno do tenant 1
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        // Deve falhar pois shift não pertence ao tenant 2
        $shiftFound = $this->shiftModel->find($shift1);
        
        // ASSERT: BaseAppModel deve filtrar e não retornar
        $this->assertNull($shiftFound, 'Tenant 2 não deve acessar turno do tenant 1');
    }
    
    /**
     * @test
     * REGRA: Relatório de movimentos deve filtrar por tenant
     */
    public function movement_report_must_filter_by_tenant(): void
    {
        // ARRANGE: Criar movimentos para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $register1 = $this->registerModel->insert([
            'name' => 'Caixa T1',
            'location' => 'Loja T1',
            'status' => 'open',
        ]);
        
        $shift1 = $this->shiftModel->insert([
            'id_cash_register' => $register1,
            'opened_by' => 1,
            'opening_amount' => 500.00,
            'status' => 'open',
        ]);
        
        $this->movementModel->insert([
            'id_shift' => $shift1,
            'id_cash_register' => $register1,
            'type' => 'withdrawal',
            'amount' => 100.00,
            'reason' => 'Sangria T1 - 1',
            'performed_by' => 1,
        ]);
        
        $this->movementModel->insert([
            'id_shift' => $shift1,
            'id_cash_register' => $register1,
            'type' => 'withdrawal',
            'amount' => 50.00,
            'reason' => 'Sangria T1 - 2',
            'performed_by' => 1,
        ]);
        
        // Tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $register2 = $this->registerModel->insert([
            'name' => 'Caixa T2',
            'location' => 'Loja T2',
            'status' => 'open',
        ]);
        
        $shift2 = $this->shiftModel->insert([
            'id_cash_register' => $register2,
            'opened_by' => 1,
            'opening_amount' => 300.00,
            'status' => 'open',
        ]);
        
        $this->movementModel->insert([
            'id_shift' => $shift2,
            'id_cash_register' => $register2,
            'type' => 'supply',
            'amount' => 200.00,
            'reason' => 'Suprimento T2',
            'performed_by' => 1,
        ]);
        
        // ACT: Relatório para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $movements = $this->movementModel->findAll();
        
        // ASSERT: Apenas 2 movimentos (do tenant 1)
        $this->assertCount(2, $movements);
        
        foreach ($movements as $movement) {
            $this->assertEquals($this->tenant1Contador, $movement['id_contador']);
            $this->assertEquals($this->tenant1Empresa, $movement['id_empresa']);
            $this->assertEquals('withdrawal', $movement['type']);
        }
    }
    
    /**
     * @test
     * REGRA: Query agregada de movimentos deve respeitar tenant
     */
    public function aggregate_movement_query_must_respect_tenant(): void
    {
        // ARRANGE: Criar vários movimentos para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $register1 = $this->registerModel->insert([
            'name' => 'Caixa Agregado T1',
            'location' => 'Loja',
            'status' => 'open',
        ]);
        
        $shift1 = $this->shiftModel->insert([
            'id_cash_register' => $register1,
            'opened_by' => 1,
            'opening_amount' => 1000.00,
            'status' => 'open',
        ]);
        
        // Tenant 1: 3 sangrias = R$ 300
        for ($i = 0; $i < 3; $i++) {
            $this->movementModel->insert([
                'id_shift' => $shift1,
                'id_cash_register' => $register1,
                'type' => 'withdrawal',
                'amount' => 100.00,
                'reason' => "Sangria {$i}",
                'performed_by' => 1,
            ]);
        }
        
        // Tenant 2: 5 sangrias = R$ 500
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $register2 = $this->registerModel->insert([
            'name' => 'Caixa Agregado T2',
            'location' => 'Loja',
            'status' => 'open',
        ]);
        
        $shift2 = $this->shiftModel->insert([
            'id_cash_register' => $register2,
            'opened_by' => 1,
            'opening_amount' => 2000.00,
            'status' => 'open',
        ]);
        
        for ($i = 0; $i < 5; $i++) {
            $this->movementModel->insert([
                'id_shift' => $shift2,
                'id_cash_register' => $register2,
                'type' => 'withdrawal',
                'amount' => 100.00,
                'reason' => "Sangria {$i}",
                'performed_by' => 1,
            ]);
        }
        
        // ACT: Query agregada para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $db = \Config\Database::connect();
        $result = $db->table('cash_movements')
                     ->select('SUM(amount) as total_sangria, COUNT(*) as qtd_movimentos')
                     ->where('id_empresa', $this->tenant1Empresa)
                     ->where('id_contador', $this->tenant1Contador)
                     ->where('type', 'withdrawal')
                     ->get()
                     ->getRowArray();
        
        // ASSERT: Apenas R$ 300 (3 sangrias do tenant 1), não R$ 800 (total geral)
        $this->assertEquals(300.00, (float) $result['total_sangria']);
        $this->assertEquals(3, $result['qtd_movimentos']);
    }
    
    /**
     * @test
     * REGRA: Suprimento deve ser isolado por tenant
     */
    public function supply_must_be_isolated_by_tenant(): void
    {
        // ARRANGE
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $register = $this->registerModel->insert([
            'name' => 'Caixa Suprimento',
            'location' => 'Loja',
            'status' => 'open',
        ]);
        
        $shift = $this->shiftModel->insert([
            'id_cash_register' => $register,
            'opened_by' => 1,
            'opening_amount' => 50.00,
            'status' => 'open',
        ]);
        
        // ACT: Registrar suprimento
        $movementId = $this->movementModel->insert([
            'id_shift' => $shift,
            'id_cash_register' => $register,
            'type' => 'supply',
            'amount' => 500.00,
            'reason' => 'Suprimento inicial',
            'performed_by' => 1,
        ]);
        
        $movement = $this->movementModel->find($movementId);
        
        // ASSERT: Campos de tenant preenchidos
        $this->assertNotNull($movement);
        $this->assertEquals($this->tenant1Contador, $movement['id_contador']);
        $this->assertEquals($this->tenant1Empresa, $movement['id_empresa']);
        $this->assertEquals('supply', $movement['type']);
        $this->assertEquals(500.00, $movement['amount']);
    }
    
    /**
     * @test
     * REGRA: Índices de performance devem existir
     */
    public function movement_indexes_must_exist(): void
    {
        $db = \Config\Database::connect();
        
        // Verificar índices em cash_movements
        $indexes = $db->query("SHOW INDEX FROM cash_movements")->getResultArray();
        
        $indexNames = array_column($indexes, 'Key_name');
        
        // ASSERT: Índices devem existir
        $this->assertContains('id_shift', $indexNames);
        $this->assertContains('id_cash_register', $indexNames);
        
        // Verificar se há índice composto para tenant
        $hasCompositeIndex = false;
        foreach ($indexes as $index) {
            if (strpos($index['Column_name'], 'id_contador') !== false || 
                strpos($index['Column_name'], 'id_empresa') !== false) {
                $hasCompositeIndex = true;
                break;
            }
        }
        
        $this->assertTrue($hasCompositeIndex, 'Deve ter índice para campos de tenant');
    }
}

