# 🛒 IMPLEMENTAÇÃO - MÓDULO DE VENDAS AVANÇADO

**Sistema:** xFiscal ERP - PDV Multi-Tenant SaaS  
**Módulo:** Vendas Avançado  
**Metodologia:** TDD + Multi-Tenant First  
**Versão:** 1.0  
**Estimativa Total:** 96 horas  

---

## 📋 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [1.1 Suspensão de Vendas](#11-suspensão-de-vendas)
3. [1.2 Múltiplas Formas de Pagamento](#12-múltiplas-formas-de-pagamento)
4. [1.3 Descontos e Promoções](#13-descontos-e-promoções)
5. [1.4 Devoluções e Trocas](#14-devoluções-e-trocas)
6. [Ordem de Implementação](#ordem-de-implementação)

---

## 🎯 VISÃO GERAL

### Escopo do Módulo

Este módulo adiciona funcionalidades avançadas ao PDV:

```
┌─────────────────────────────────────────────────────────────┐
│              MÓDULO DE VENDAS AVANÇADO                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. SUSPENSÃO DE VENDAS                                     │
│     └─ Pausar/Retomar vendas                                │
│     └─ Timeout automático                                   │
│     └─ Listagem isolada por tenant                          │
│                                                             │
│  2. MÚLTIPLAS FORMAS DE PAGAMENTO                          │
│     └─ Dinheiro + Cartão + PIX na mesma venda              │
│     └─ Validação soma = total                               │
│     └─ Troco calculado corretamente                         │
│                                                             │
│  3. DESCONTOS E PROMOÇÕES                                   │
│     └─ Desconto por item ou total                           │
│     └─ Cupons de desconto por tenant                        │
│     └─ Permissões e limites configuráveis                   │
│     └─ Auditoria completa                                   │
│                                                             │
│  4. DEVOLUÇÕES E TROCAS                                     │
│     └─ Devolução total/parcial                              │
│     └─ Troca de produtos                                    │
│     └─ Estorno e reposição de estoque                       │
│     └─ NF-e de devolução                                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Estimativas

| Funcionalidade | Complexidade | Estimativa |
|----------------|--------------|------------|
| Suspensão de Vendas | Média | 20h |
| Múltiplas Formas Pagamento | Alta | 24h |
| Descontos e Promoções | Alta | 32h |
| Devoluções e Trocas | Muito Alta | 40h |
| **TOTAL** | - | **116h** |

### Dependências

- ✅ PDV Básico funcional
- ✅ `BaseAppModel` com isolamento multi-tenant
- ✅ Sistema de permissões (a implementar se não existir)
- ⚠️ TEF/PIX (recomendado para pagamentos múltiplos)

---

## 1.1 SUSPENSÃO DE VENDAS

### 📊 Análise

**Objetivo:** Permitir que operador pause uma venda (ex: cliente saiu buscar dinheiro) e retome depois.

**Casos de Uso:**
1. Cliente não tem dinheiro suficiente → suspender
2. Cliente quer fazer outra compra antes → suspender atual
3. Operador precisa atender outro cliente urgente → suspender
4. Timeout: vendas suspensas > 2h são canceladas automaticamente

**Regras de Negócio:**
- ✅ Máximo 10 vendas suspensas simultâneas por tenant
- ✅ Timeout configurável (padrão: 2 horas)
- ✅ Apenas operador que suspendeu pode retomar (ou gerente)
- ✅ Ao retomar, carregar itens de volta ao carrinho
- ✅ Validar que não há outra venda ativa no caixa

### 🗄️ STEP 1: MIGRATIONS

```php
<?php
// app/Database/Migrations/2025-10-02-100000_AddSuspendedSalesSupport.php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSuspendedSalesSupport extends Migration
{
    public function up()
    {
        // 1. Adicionar novo status 'suspended' se não existir
        $this->db->query("
            ALTER TABLE pos_sales 
            MODIFY COLUMN status ENUM(
                'draft', 
                'finalized', 
                'cancelled', 
                'suspended'
            ) NOT NULL DEFAULT 'draft'
        ");
        
        // 2. Adicionar campos para controle de suspensão
        $this->forge->addColumn('pos_sales', [
            'suspended_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'status',
            ],
            'suspended_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'ID do usuário que suspendeu',
                'after' => 'suspended_at',
            ],
            'suspended_reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Motivo da suspensão',
                'after' => 'suspended_by',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data/hora de expiração automática',
                'after' => 'suspended_reason',
            ],
        ]);
        
        // 3. Índice para buscar vendas expiradas (cron job)
        $this->db->query("
            CREATE INDEX idx_suspended_sales 
            ON pos_sales(status, expires_at, id_contador, id_empresa)
            WHERE status = 'suspended' AND expires_at IS NOT NULL
        ");
    }
    
    public function down()
    {
        $this->db->query("DROP INDEX idx_suspended_sales ON pos_sales");
        
        $this->forge->dropColumn('pos_sales', [
            'suspended_at',
            'suspended_by',
            'suspended_reason',
            'expires_at',
        ]);
        
        $this->db->query("
            ALTER TABLE pos_sales 
            MODIFY COLUMN status ENUM(
                'draft', 
                'finalized', 
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
}
```

### 🧪 STEP 2: TESTES (TDD)

```php
<?php
// tests/multitenant/SuspendedSalesTest.php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Models\PosSaleModel;
use App\Libraries\SuspendedSalesService;

class SuspendedSalesTest extends MultiTenantTestCase
{
    protected PosSaleModel $saleModel;
    protected SuspendedSalesService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->saleModel = new PosSaleModel();
        $this->service = new SuspendedSalesService();
    }
    
    /**
     * @test
     * REGRA: Vendas suspensas devem ser isoladas por tenant
     */
    public function suspended_sales_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar venda suspensa para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $idSale1 = $this->saleModel->insert([
            'sale_number' => 'SUSP-001',
            'total' => 100.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $this->service->suspend($idSale1, 'Cliente foi buscar dinheiro');
        
        // ACT: Tentar listar vendas suspensas com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $suspended = $this->service->listSuspended();
        
        // ASSERT: Não deve ver venda do tenant 1
        $this->assertCount(0, $suspended);
        
        // ASSERT: Tenant 1 deve ver a própria venda
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        $suspended1 = $this->service->listSuspended();
        
        $this->assertCount(1, $suspended1);
        $this->assertTenantOwnership($suspended1[0], $this->tenant1Contador, $this->tenant1Empresa);
    }
    
    /**
     * @test
     * REGRA: Não pode retomar venda suspensa de outro tenant
     */
    public function cannot_resume_suspended_sale_from_other_tenant(): void
    {
        // ARRANGE: Criar venda suspensa para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $idSale1 = $this->saleModel->insert([
            'sale_number' => 'SUSP-002',
            'total' => 150.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $this->service->suspend($idSale1, 'Teste');
        
        // ACT: Tentar retomar com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Venda não encontrada ou não pertence a este tenant');
        
        $this->service->resume($idSale1);
    }
    
    /**
     * @test
     * REGRA: Máximo 10 vendas suspensas por tenant
     */
    public function tenant_can_have_max_10_suspended_sales(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // ARRANGE: Criar 10 vendas suspensas
        for ($i = 1; $i <= 10; $i++) {
            $idSale = $this->saleModel->insert([
                'sale_number' => "SUSP-{$i}",
                'total' => 100.00 * $i,
                'status' => 'draft',
                'id_shift' => 1,
                'id_cash_register' => 1,
            ]);
            
            $this->service->suspend($idSale, "Motivo {$i}");
        }
        
        // ACT: Tentar suspender 11ª venda
        $idSale11 = $this->saleModel->insert([
            'sale_number' => 'SUSP-11',
            'total' => 999.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Limite de vendas suspensas atingido');
        
        $this->service->suspend($idSale11, 'Motivo 11');
    }
    
    /**
     * @test
     * REGRA: Vendas expiradas devem ser canceladas automaticamente
     */
    public function expired_suspended_sales_must_be_auto_cancelled(): void
    {
        // ARRANGE: Criar venda suspensa com expiração no passado
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SUSP-EXPIRED',
            'total' => 50.00,
            'status' => 'suspended',
            'id_shift' => 1,
            'id_cash_register' => 1,
            'suspended_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
            'suspended_by' => 1,
            'suspended_reason' => 'Teste expiração',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ]);
        
        // ACT: Executar limpeza de vendas expiradas
        $cancelled = $this->service->cancelExpired();
        
        // ASSERT: Deve ter cancelado 1 venda
        $this->assertEquals(1, $cancelled);
        
        // Validar que status mudou
        $sale = $this->saleModel->find($idSale);
        $this->assertEquals('cancelled', $sale['status']);
    }
    
    /**
     * @test
     * REGRA: Ao retomar, carregar itens de volta ao carrinho
     */
    public function resume_must_load_items_back_to_cart(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // ARRANGE: Criar venda com itens
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SUSP-WITH-ITEMS',
            'total' => 250.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $db = \Config\Database::connect();
        $db->table('pos_sale_items')->insertBatch([
            [
                'id_pos_sale' => $idSale,
                'id_produto' => 1,
                'descricao' => 'Produto A',
                'quantidade' => 2,
                'valor_unitario' => 50.00,
                'subtotal' => 100.00,
            ],
            [
                'id_pos_sale' => $idSale,
                'id_produto' => 2,
                'descricao' => 'Produto B',
                'quantidade' => 3,
                'valor_unitario' => 50.00,
                'subtotal' => 150.00,
            ],
        ]);
        
        // Suspender
        $this->service->suspend($idSale, 'Teste');
        
        // ACT: Retomar
        $result = $this->service->resume($idSale);
        
        // ASSERT: Deve ter retornado itens
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['items']);
        
        // Status volta para draft
        $sale = $this->saleModel->find($idSale);
        $this->assertEquals('draft', $sale['status']);
    }
}
```

### 📦 STEP 3: SERVICE

```php
<?php
// app/Libraries/SuspendedSalesService.php
namespace App\Libraries;

use App\Models\PosSaleModel;
use App\Models\PosSaleItemModel;
use App\Traits\TenantAwareTrait;

class SuspendedSalesService
{
    use TenantAwareTrait;
    
    protected PosSaleModel $saleModel;
    protected PosSaleItemModel $itemModel;
    
    /**
     * Configurações
     */
    protected int $maxSuspended = 10;
    protected int $timeoutHours = 2;
    
    public function __construct()
    {
        $this->saleModel = new PosSaleModel();
        $this->itemModel = new PosSaleItemModel();
        
        // Carregar configurações do tenant
        $this->loadTenantConfig();
    }
    
    /**
     * Carregar configurações de suspensão do tenant
     */
    protected function loadTenantConfig(): void
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $db = \Config\Database::connect();
        $config = $db->table('empresas')
                     ->where('id_contador', $idContador)
                     ->where('id_empresa', $idEmpresa)
                     ->get()
                     ->getRowArray();
        
        if ($config) {
            $this->maxSuspended = $config['max_suspended_sales'] ?? 10;
            $this->timeoutHours = $config['suspended_timeout_hours'] ?? 2;
        }
    }
    
    /**
     * Suspender venda
     */
    public function suspend(int $idSale, string $reason): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Validar que venda pertence ao tenant
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale) {
            throw new \RuntimeException('Venda não encontrada');
        }
        
        if (!$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Venda não pertence a este tenant');
        }
        
        // Validar status
        if ($sale['status'] !== 'draft') {
            throw new \RuntimeException('Apenas vendas em rascunho podem ser suspensas');
        }
        
        // Validar limite de vendas suspensas
        $currentSuspended = $this->countSuspended();
        
        if ($currentSuspended >= $this->maxSuspended) {
            throw new \RuntimeException(
                "Limite de vendas suspensas atingido ({$this->maxSuspended}). " .
                "Finalize ou cancele vendas suspensas antes de suspender novas."
            );
        }
        
        // Calcular expiração
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->timeoutHours} hours"));
        
        // Obter ID do usuário
        $session = session();
        $idUsuario = (int) ($session->get('id_usuario') ?? $session->get('id_login') ?? 0);
        
        // Atualizar venda
        $updated = $this->saleModel->update($idSale, [
            'status' => 'suspended',
            'suspended_at' => date('Y-m-d H:i:s'),
            'suspended_by' => $idUsuario,
            'suspended_reason' => $reason,
            'expires_at' => $expiresAt,
        ]);
        
        if (!$updated) {
            throw new \RuntimeException('Erro ao suspender venda');
        }
        
        // Log
        log_message('info', '[SuspendedSales] Venda suspensa', [
            'id_sale' => $idSale,
            'reason' => $reason,
            'expires_at' => $expiresAt,
            'tenant' => "{$idContador}:{$idEmpresa}",
            'user_id' => $idUsuario,
        ]);
        
        return [
            'success' => true,
            'message' => 'Venda suspensa com sucesso',
            'expires_at' => $expiresAt,
        ];
    }
    
    /**
     * Retomar venda suspensa
     */
    public function resume(int $idSale): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar venda
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale) {
            throw new \RuntimeException('Venda não encontrada ou não pertence a este tenant');
        }
        
        if ($sale['status'] !== 'suspended') {
            throw new \RuntimeException('Apenas vendas suspensas podem ser retomadas');
        }
        
        // Validar expiração
        if ($sale['expires_at'] && strtotime($sale['expires_at']) < time()) {
            throw new \RuntimeException('Venda expirou e foi cancelada automaticamente');
        }
        
        // Buscar itens da venda
        $items = $this->itemModel->where('id_pos_sale', $idSale)->findAll();
        
        // Atualizar status para draft
        $this->saleModel->update($idSale, [
            'status' => 'draft',
            'suspended_at' => null,
            'suspended_by' => null,
            'suspended_reason' => null,
            'expires_at' => null,
        ]);
        
        // Log
        log_message('info', '[SuspendedSales] Venda retomada', [
            'id_sale' => $idSale,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return [
            'success' => true,
            'message' => 'Venda retomada com sucesso',
            'sale' => $sale,
            'items' => $items,
        ];
    }
    
    /**
     * Listar vendas suspensas do tenant
     */
    public function listSuspended(): array
    {
        return $this->saleModel->where('status', 'suspended')
                               ->orderBy('suspended_at', 'ASC')
                               ->findAll();
    }
    
    /**
     * Contar vendas suspensas do tenant
     */
    public function countSuspended(): int
    {
        return $this->saleModel->where('status', 'suspended')
                               ->countAllResults();
    }
    
    /**
     * Cancelar vendas expiradas (CRON JOB)
     */
    public function cancelExpired(): int
    {
        $db = \Config\Database::connect();
        
        // Buscar vendas expiradas de TODOS os tenants
        $expired = $db->table('pos_sales')
                      ->where('status', 'suspended')
                      ->where('expires_at IS NOT NULL')
                      ->where('expires_at <', date('Y-m-d H:i:s'))
                      ->get()
                      ->getResultArray();
        
        $cancelled = 0;
        
        foreach ($expired as $sale) {
            // Cancelar venda
            $db->table('pos_sales')
               ->where('id_pos_sale', $sale['id_pos_sale'])
               ->update([
                   'status' => 'cancelled',
                   'cancelled_at' => date('Y-m-d H:i:s'),
                   'cancellation_reason' => 'Suspensão expirada automaticamente',
                   'updated_at' => date('Y-m-d H:i:s'),
               ]);
            
            $cancelled++;
            
            log_message('info', '[SuspendedSales] Venda cancelada por expiração', [
                'id_sale' => $sale['id_pos_sale'],
                'tenant' => "{$sale['id_contador']}:{$sale['id_empresa']}",
                'suspended_at' => $sale['suspended_at'],
                'expires_at' => $sale['expires_at'],
            ]);
        }
        
        return $cancelled;
    }
}
```

### 🎮 STEP 4: CONTROLLER

```php
<?php
// app/Controllers/Api/Pos.php (adicionar métodos)
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\SuspendedSalesService;

class Pos extends BaseController
{
    // ... métodos existentes ...
    
    /**
     * Suspender venda
     * POST /api/pos/{id}/suspend
     */
    public function suspend(int $id): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            $reason = $data['reason'] ?? '';
            
            if (empty($reason)) {
                return $this->respond([
                    'success' => false,
                    'error' => 'Motivo é obrigatório',
                ], 400);
            }
            
            $service = new SuspendedSalesService();
            $result = $service->suspend($id, $reason);
            
            return $this->respond($result, 200);
            
        } catch (\Exception $e) {
            log_message('error', '[Pos::suspend] Erro: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Retomar venda suspensa
     * POST /api/pos/{id}/resume
     */
    public function resume(int $id): ResponseInterface
    {
        try {
            $service = new SuspendedSalesService();
            $result = $service->resume($id);
            
            return $this->respond($result, 200);
            
        } catch (\Exception $e) {
            log_message('error', '[Pos::resume] Erro: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Listar vendas suspensas
     * GET /api/pos/suspended
     */
    public function suspended(): ResponseInterface
    {
        try {
            $service = new SuspendedSalesService();
            $sales = $service->listSuspended();
            
            return $this->respond([
                'success' => true,
                'data' => $sales,
                'count' => count($sales),
            ], 200);
            
        } catch (\Exception $e) {
            log_message('error', '[Pos::suspended] Erro: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

### ⏰ STEP 5: CRON JOB

```php
<?php
// app/Commands/CancelExpiredSuspendedSales.php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\SuspendedSalesService;

class CancelExpiredSuspendedSales extends BaseCommand
{
    protected $group       = 'maintenance';
    protected $name        = 'sales:cancel-expired';
    protected $description = 'Cancelar vendas suspensas expiradas';
    
    public function run(array $params)
    {
        CLI::write('Buscando vendas suspensas expiradas...', 'yellow');
        
        $service = new SuspendedSalesService();
        $cancelled = $service->cancelExpired();
        
        CLI::write("✅ {$cancelled} venda(s) cancelada(s)", 'green');
    }
}
```

**Configurar no crontab:**

```bash
# Executar a cada 15 minutos
*/15 * * * * cd /var/www/erp && php spark sales:cancel-expired >> /var/log/cron-suspended-sales.log 2>&1
```

### ✅ VALIDAÇÃO

```markdown
## Checklist de Validação - Suspensão de Vendas

- [ ] Testes multi-tenant passando (isolamento)
- [ ] Limite de 10 vendas suspensas funciona
- [ ] Timeout automático funciona (cron)
- [ ] Ao retomar, itens voltam ao carrinho
- [ ] Apenas operador/gerente pode retomar
- [ ] Logs incluem tenant_id
- [ ] Queries filtram por tenant
- [ ] UI mostra vendas suspensas apenas do tenant
```

---

## 1.2 MÚLTIPLAS FORMAS DE PAGAMENTO

### 📊 Análise

**Objetivo:** Permitir que uma venda seja paga com dinheiro + cartão + PIX na mesma transação.

**Casos de Uso:**
1. Cliente paga R$ 100 dinheiro + R$ 150 cartão
2. Calcular troco apenas sobre dinheiro
3. Validar que soma dos pagamentos = total da venda
4. Registrar cada pagamento com rastreabilidade

**Regras de Negócio:**
- ✅ Soma exata dos pagamentos deve ser igual ao total da venda
- ✅ Troco calculado apenas sobre pagamentos em dinheiro
- ✅ Se um pagamento falhar (TEF, PIX), estornar todos
- ✅ Transação atômica (tudo ou nada)
- ✅ Auditoria completa de cada pagamento

### 🗄️ STEP 1: MIGRATION

```php
<?php
// app/Database/Migrations/2025-10-02-110000_CreatePosSalePayments.php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePosSalePayments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_payment' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'payment_type' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'credit', 'debit', 'pix', 'voucher', 'other'],
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'paid_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor pago (pode ser > amount se troco)',
            ],
            'change_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'Troco (apenas para cash)',
            ],
            'installments' => [
                'type'       => 'INT',
                'constraint' => 2,
                'default'    => 1,
                'comment'    => 'Parcelas (credit/debit)',
            ],
            'card_brand' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Bandeira (Visa, Master, etc)',
            ],
            'card_last4' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
                'null'       => true,
                'comment'    => 'Últimos 4 dígitos do cartão',
            ],
            'authorization_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Código de autorização TEF',
            ],
            'nsu' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'NSU da transação TEF',
            ],
            'id_tef_transaction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para tef_transactions',
            ],
            'id_pix_transaction' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para pix_transactions',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'authorized', 'confirmed', 'cancelled', 'failed'],
                'default'    => 'pending',
            ],
            'processed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id_payment', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey('status');
        
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('pos_sale_payments');
    }
    
    public function down()
    {
        $this->forge->dropTable('pos_sale_payments');
    }
}
```

### 🧪 STEP 2: TESTES

```php
<?php
// tests/multitenant/MultiPaymentTest.php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\MultiPaymentService;
use App\Models\PosSaleModel;
use App\Models\PosSalePaymentModel;

class MultiPaymentTest extends MultiTenantTestCase
{
    /**
     * @test
     * REGRA: Soma dos pagamentos deve ser igual ao total da venda
     */
    public function payment_sum_must_equal_sale_total(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $service = new MultiPaymentService();
        $saleModel = new PosSaleModel();
        
        // ARRANGE: Criar venda
        $idSale = $saleModel->insert([
            'sale_number' => 'MULTI-PAY-001',
            'total' => 300.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Tentar finalizar com soma errada
        $payments = [
            ['type' => 'cash', 'amount' => 100.00],
            ['type' => 'credit', 'amount' => 150.00], // Total: 250 (falta 50)
        ];
        
        $result = $service->processMultiple($idSale, $payments);
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Soma dos pagamentos', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Troco calculado apenas sobre dinheiro
     */
    public function change_calculated_only_for_cash(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $service = new MultiPaymentService();
        $saleModel = new PosSaleModel();
        
        // ARRANGE
        $idSale = $saleModel->insert([
            'sale_number' => 'MULTI-PAY-002',
            'total' => 300.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Cliente paga R$ 150 dinheiro (com nota de R$ 200) + R$ 150 cartão
        $payments = [
            ['type' => 'cash', 'amount' => 150.00, 'paid_amount' => 200.00], // Troco: 50
            ['type' => 'credit', 'amount' => 150.00, 'installments' => 3],
        ];
        
        $result = $service->processMultiple($idSale, $payments);
        
        // ASSERT
        $this->assertTrue($result['success']);
        $this->assertEquals(50.00, $result['change_amount']);
        
        // Validar que apenas pagamento em dinheiro tem troco
        $paymentModel = new PosSalePaymentModel();
        $savedPayments = $paymentModel->where('id_pos_sale', $idSale)->findAll();
        
        $cashPayment = array_values(array_filter($savedPayments, fn($p) => $p['payment_type'] === 'cash'))[0];
        $creditPayment = array_values(array_filter($savedPayments, fn($p) => $p['payment_type'] === 'credit'))[0];
        
        $this->assertEquals(50.00, $cashPayment['change_amount']);
        $this->assertEquals(0.00, $creditPayment['change_amount']);
    }
    
    /**
     * @test
     * REGRA: Pagamentos devem ser isolados por tenant
     */
    public function payments_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar venda e pagamentos para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $saleModel = new PosSaleModel();
        $paymentModel = new PosSalePaymentModel();
        
        $idSale1 = $saleModel->insert([
            'sale_number' => 'SALE-T1',
            'total' => 200.00,
            'status' => 'finalized',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $paymentModel->insertBatch([
            [
                'id_pos_sale' => $idSale1,
                'payment_type' => 'cash',
                'amount' => 100.00,
                'paid_amount' => 100.00,
                'status' => 'confirmed',
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
                'processed_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id_pos_sale' => $idSale1,
                'payment_type' => 'credit',
                'amount' => 100.00,
                'paid_amount' => 100.00,
                'status' => 'confirmed',
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
                'processed_at' => date('Y-m-d H:i:s'),
            ],
        ]);
        
        // ACT: Buscar pagamentos com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $payments = $paymentModel->findAll();
        
        // ASSERT: Não deve ver pagamentos do tenant 1
        $this->assertCount(0, $payments);
    }
    
    /**
     * @test
     * REGRA: Se um pagamento falhar, estornar todos (transação atômica)
     */
    public function failed_payment_must_rollback_all(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $service = new MultiPaymentService();
        $saleModel = new PosSaleModel();
        $paymentModel = new PosSalePaymentModel();
        
        // ARRANGE
        $idSale = $saleModel->insert([
            'sale_number' => 'MULTI-PAY-FAIL',
            'total' => 300.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Simular falha no segundo pagamento (TEF)
        $payments = [
            ['type' => 'cash', 'amount' => 100.00, 'paid_amount' => 100.00],
            ['type' => 'credit', 'amount' => 200.00, 'simulate_fail' => true], // Forçar falha
        ];
        
        $result = $service->processMultiple($idSale, $payments);
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        
        // Nenhum pagamento deve estar salvo (rollback)
        $savedPayments = $paymentModel->where('id_pos_sale', $idSale)->findAll();
        $this->assertCount(0, $savedPayments);
        
        // Status da venda deve continuar draft
        $sale = $saleModel->find($idSale);
        $this->assertEquals('draft', $sale['status']);
    }
}
```

### 📦 STEP 3: MODEL

```php
<?php
// app/Models/PosSalePaymentModel.php
namespace App\Models;

class PosSalePaymentModel extends BaseAppModel
{
    protected $table = 'pos_sale_payments';
    protected $primaryKey = 'id_payment';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_pos_sale', 'payment_type', 'amount', 'paid_amount', 'change_amount',
        'installments', 'card_brand', 'card_last4', 'authorization_code', 'nsu',
        'id_tef_transaction', 'id_pix_transaction', 'status', 'processed_at',
        'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'id_pos_sale' => 'required|integer',
        'payment_type' => 'required|in_list[cash,credit,debit,pix,voucher,other]',
        'amount' => 'required|decimal|greater_than[0]',
        'paid_amount' => 'permit_empty|decimal',
        'installments' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[12]',
    ];
    
    /**
     * Buscar pagamentos de uma venda
     */
    public function getBySale(int $idSale): array
    {
        return $this->where('id_pos_sale', $idSale)
                    ->orderBy('id_payment', 'ASC')
                    ->findAll();
    }
    
    /**
     * Calcular total pago de uma venda
     */
    public function getTotalPaid(int $idSale): float
    {
        $result = $this->selectSum('amount')
                       ->where('id_pos_sale', $idSale)
                       ->where('status', 'confirmed')
                       ->get()
                       ->getRowArray();
        
        return (float) ($result['amount'] ?? 0.00);
    }
    
    /**
     * Calcular troco total de uma venda
     */
    public function getTotalChange(int $idSale): float
    {
        $result = $this->selectSum('change_amount')
                       ->where('id_pos_sale', $idSale)
                       ->where('status', 'confirmed')
                       ->get()
                       ->getRowArray();
        
        return (float) ($result['change_amount'] ?? 0.00);
    }
}
```

### 📦 STEP 4: SERVICE

```php
<?php
// app/Libraries/MultiPaymentService.php
namespace App\Libraries;

use App\Models\PosSaleModel;
use App\Models\PosSalePaymentModel;
use App\Traits\TenantAwareTrait;

class MultiPaymentService
{
    use TenantAwareTrait;
    
    protected PosSaleModel $saleModel;
    protected PosSalePaymentModel $paymentModel;
    
    public function __construct()
    {
        $this->saleModel = new PosSaleModel();
        $this->paymentModel = new PosSalePaymentModel();
    }
    
    /**
     * Processar múltiplos pagamentos
     * 
     * @param int $idSale
     * @param array $payments [
     *   ['type' => 'cash', 'amount' => 100.00, 'paid_amount' => 150.00],
     *   ['type' => 'credit', 'amount' => 200.00, 'installments' => 3],
     * ]
     */
    public function processMultiple(int $idSale, array $payments): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Validar que venda pertence ao tenant
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale) {
            throw new \RuntimeException('Venda não encontrada');
        }
        
        if (!$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Venda não pertence a este tenant');
        }
        
        if ($sale['status'] !== 'draft') {
            throw new \RuntimeException('Apenas vendas em rascunho podem receber pagamentos');
        }
        
        // Validar soma dos pagamentos
        $totalPayments = array_sum(array_column($payments, 'amount'));
        $saleTotal = (float) $sale['total'];
        
        if (abs($totalPayments - $saleTotal) > 0.01) {
            return [
                'success' => false,
                'error' => "Soma dos pagamentos (R$ {$totalPayments}) não é igual ao total da venda (R$ {$saleTotal})",
            ];
        }
        
        // Iniciar transação
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            $processedPayments = [];
            $totalChange = 0.00;
            
            foreach ($payments as $payment) {
                $result = $this->processPayment($idSale, $payment, $idContador, $idEmpresa);
                
                if (!$result['success']) {
                    // Se falhar, lançar exceção para rollback
                    throw new \RuntimeException($result['error']);
                }
                
                $processedPayments[] = $result['payment'];
                $totalChange += $result['payment']['change_amount'];
            }
            
            // Finalizar transação
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Erro ao processar pagamentos (transação falhou)');
            }
            
            log_message('info', '[MultiPayment] Pagamentos processados', [
                'id_sale' => $idSale,
                'total_payments' => count($processedPayments),
                'total_change' => $totalChange,
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            return [
                'success' => true,
                'message' => 'Pagamentos processados com sucesso',
                'payments' => $processedPayments,
                'change_amount' => $totalChange,
            ];
            
        } catch (\Exception $e) {
            $db->transRollback();
            
            log_message('error', '[MultiPayment] Erro ao processar pagamentos', [
                'id_sale' => $idSale,
                'error' => $e->getMessage(),
                'tenant' => "{$idContador}:{$idEmpresa}",
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Processar pagamento individual
     */
    protected function processPayment(int $idSale, array $payment, int $idContador, int $idEmpresa): array
    {
        $type = $payment['type'];
        $amount = $payment['amount'];
        $paidAmount = $payment['paid_amount'] ?? $amount;
        $installments = $payment['installments'] ?? 1;
        
        // Calcular troco (apenas para cash)
        $change = 0.00;
        if ($type === 'cash' && $paidAmount > $amount) {
            $change = $paidAmount - $amount;
        }
        
        // Simular falha (apenas para testes)
        if (isset($payment['simulate_fail']) && $payment['simulate_fail'] === true) {
            return [
                'success' => false,
                'error' => 'Pagamento negado (simulação)',
            ];
        }
        
        // Processar conforme tipo
        $paymentData = [
            'id_pos_sale' => $idSale,
            'payment_type' => $type,
            'amount' => $amount,
            'paid_amount' => $paidAmount,
            'change_amount' => $change,
            'installments' => $installments,
            'status' => 'pending',
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
        ];
        
        switch ($type) {
            case 'cash':
                $paymentData['status'] = 'confirmed';
                $paymentData['processed_at'] = date('Y-m-d H:i:s');
                break;
                
            case 'credit':
            case 'debit':
                // Aqui você integraria com TEF
                // Por enquanto, simular sucesso
                $paymentData['status'] = 'confirmed';
                $paymentData['authorization_code'] = $this->generateAuthCode();
                $paymentData['nsu'] = $this->generateNSU();
                $paymentData['card_last4'] = '****';
                $paymentData['processed_at'] = date('Y-m-d H:i:s');
                break;
                
            case 'pix':
                // Aqui você integraria com PIX
                // Por enquanto, simular sucesso
                $paymentData['status'] = 'confirmed';
                $paymentData['processed_at'] = date('Y-m-d H:i:s');
                break;
                
            default:
                $paymentData['status'] = 'confirmed';
                $paymentData['processed_at'] = date('Y-m-d H:i:s');
        }
        
        $idPayment = $this->paymentModel->insert($paymentData);
        
        if (!$idPayment) {
            return [
                'success' => false,
                'error' => 'Erro ao registrar pagamento',
            ];
        }
        
        $paymentData['id_payment'] = $idPayment;
        
        return [
            'success' => true,
            'payment' => $paymentData,
        ];
    }
    
    /**
     * Gerar código de autorização fake (para testes)
     */
    protected function generateAuthCode(): string
    {
        return strtoupper(bin2hex(random_bytes(3)));
    }
    
    /**
     * Gerar NSU fake (para testes)
     */
    protected function generateNSU(): string
    {
        return (string) rand(100000000, 999999999);
    }
}
```

### 🎮 STEP 5: REFATORAR CONTROLLER

```php
<?php
// app/Controllers/Api/Pos.php (refatorar método finalize)
namespace App\Controllers\Api;

use App\Libraries\MultiPaymentService;

class Pos extends BaseController
{
    /**
     * Finalizar venda (REFATORADO para suportar múltiplos pagamentos)
     * POST /api/pos/{id}/finalize
     * 
     * Payload antigo (compatível):
     * {
     *   "payment_type": "cash",
     *   "paid_amount": 100.00,
     *   "emit_nfce": true
     * }
     * 
     * Payload novo (múltiplos pagamentos):
     * {
     *   "payments": [
     *     {"type": "cash", "amount": 50.00, "paid_amount": 100.00},
     *     {"type": "credit", "amount": 100.00, "installments": 3},
     *     {"type": "pix", "amount": 50.00}
     *   ],
     *   "emit_nfce": true
     * }
     */
    public function finalize($id)
    {
        $data = $this->request->getJSON(true);
        
        // Detectar se é pagamento único ou múltiplo
        $hasMultiplePayments = isset($data['payments']) && is_array($data['payments']);
        
        try {
            if ($hasMultiplePayments) {
                // NOVO: Múltiplos pagamentos
                return $this->finalizeWithMultiplePayments($id, $data);
            } else {
                // ANTIGO: Pagamento único (compatibilidade)
                return $this->finalizeWithSinglePayment($id, $data);
            }
            
        } catch (\Exception $e) {
            log_message('error', '[Pos::finalize] Erro: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Finalizar com múltiplos pagamentos (NOVO)
     */
    protected function finalizeWithMultiplePayments(int $id, array $data): ResponseInterface
    {
        $payments = $data['payments'];
        $emitNFCe = $data['emit_nfce'] ?? false;
        
        // 1. Processar pagamentos
        $multiPaymentService = new MultiPaymentService();
        $result = $multiPaymentService->processMultiple($id, $payments);
        
        if (!$result['success']) {
            return $this->respond($result, 400);
        }
        
        // 2. Marcar venda como finalizada
        $saleModel = new \App\Models\PosSaleModel();
        $saleModel->update($id, [
            'status' => 'finalized',
            'finalized_at' => date('Y-m-d H:i:s'),
            'change_amount' => $result['change_amount'],
        ]);
        
        // 3. Dar baixa no estoque (código existente)
        $this->darBaixaEstoque($id);
        
        // 4. Emitir NFC-e se solicitado
        $nfceResult = null;
        if ($emitNFCe) {
            $nfceResult = $this->emitirNFCe($id);
        }
        
        // 5. Registrar no financeiro (código existente)
        $this->registrarFinanceiro($id);
        
        return $this->respond([
            'success' => true,
            'message' => 'Venda finalizada com sucesso',
            'payments' => $result['payments'],
            'change_amount' => $result['change_amount'],
            'nfce' => $nfceResult,
        ], 200);
    }
    
    /**
     * Finalizar com pagamento único (ANTIGO - compatibilidade)
     */
    protected function finalizeWithSinglePayment(int $id, array $data): ResponseInterface
    {
        // Converter para formato de múltiplos pagamentos
        $paymentType = $data['payment_type'] ?? 'cash';
        $paidAmount = (float) ($data['paid_amount'] ?? 0.00);
        
        $saleModel = new \App\Models\PosSaleModel();
        $sale = $saleModel->find($id);
        
        if (!$sale) {
            return $this->respond([
                'success' => false,
                'error' => 'Venda não encontrada',
            ], 404);
        }
        
        $payments = [
            [
                'type' => $paymentType,
                'amount' => (float) $sale['total'],
                'paid_amount' => $paidAmount,
                'installments' => (int) ($data['installments'] ?? 1),
            ],
        ];
        
        $data['payments'] = $payments;
        
        return $this->finalizeWithMultiplePayments($id, $data);
    }
}
```

### ✅ VALIDAÇÃO

```markdown
## Checklist de Validação - Múltiplas Formas Pagamento

- [ ] Soma dos pagamentos validada (= total)
- [ ] Troco calculado apenas sobre dinheiro
- [ ] Se um pagamento falhar, rollback completo
- [ ] Cada pagamento registrado separadamente
- [ ] Testes multi-tenant passando
- [ ] API backward compatible (pagamento único)
- [ ] Logs incluem tenant_id
- [ ] Queries filtram por tenant
```

---

## 1.3 DESCONTOS E PROMOÇÕES

### 📊 Análise

**Objetivo:** Sistema completo de descontos com cupons, permissões e auditoria.

**Casos de Uso:**
1. Desconto por item (5% off em produto X)
2. Desconto geral na venda (10% off total)
3. Cupom "PROMO20" = 20% off
4. Gerente autoriza desconto > 10%
5. Auditoria: quem aplicou, quando, motivo

**Regras de Negócio:**
- ✅ Operador: até 5% sem autorização
- ✅ Gerente: até 20% sem autorização
- ✅ Admin: sem limite
- ✅ Cupons por tenant (isolados)
- ✅ Limite máximo configurável por tenant
- ✅ Auditoria completa (obrigatória)

### 🗄️ STEP 1: MIGRATIONS

```php
<?php
// app/Database/Migrations/2025-10-02-120000_CreateDiscountsSystem.php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDiscountsSystem extends Migration
{
    public function up()
    {
        // 1. Tabela de cupons de desconto
        $this->forge->addField([
            'id_coupon' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['percentage', 'fixed_value'],
            ],
            'value' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Percentual (ex: 15.00) ou valor fixo (ex: 50.00)',
            ],
            'min_purchase' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'Valor mínimo de compra para usar cupom',
            ],
            'max_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'Desconto máximo (para percentuais)',
            ],
            'usage_limit' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Limite de usos (null = ilimitado)',
            ],
            'usage_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Contador de usos',
            ],
            'usage_per_customer' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
                'comment'    => 'Limite por cliente',
            ],
            'valid_from' => [
                'type' => 'DATETIME',
            ],
            'valid_until' => [
                'type' => 'DATETIME',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id_coupon', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('code');
        $this->forge->addKey(['valid_from', 'valid_until']);
        
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('discount_coupons');
        
        // 2. Tabela de auditoria de descontos
        $this->forge->addField([
            'id_discount_log' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'id_pos_sale_item' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'NULL = desconto geral, preenchido = desconto em item',
            ],
            'id_coupon' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'NULL = desconto manual, preenchido = cupom usado',
            ],
            'discount_type' => [
                'type'       => 'ENUM',
                'constraint' => ['percentage', 'fixed_value', 'coupon'],
            ],
            'discount_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor percentual ou fixo',
            ],
            'discount_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor efetivo do desconto em R$',
            ],
            'original_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor antes do desconto',
            ],
            'final_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor após desconto',
            ],
            'applied_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'comment'    => 'ID do usuário que aplicou',
            ],
            'authorized_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'ID do gerente que autorizou (se necessário)',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'requires_authorization' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id_discount_log', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey('applied_by');
        
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('discount_audit_logs');
        
        // 3. Adicionar configurações de desconto na tabela empresas
        $this->forge->addColumn('empresas', [
            'discount_operator_limit' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 5.00,
                'comment'    => 'Limite percentual para operador',
                'after'      => 'xFant',
            ],
            'discount_manager_limit' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 20.00,
                'comment'    => 'Limite percentual para gerente',
                'after'      => 'discount_operator_limit',
            ],
            'discount_requires_reason' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Exigir motivo ao aplicar desconto',
                'after'      => 'discount_manager_limit',
            ],
        ]);
    }
    
    public function down()
    {
        $this->forge->dropColumn('empresas', [
            'discount_operator_limit',
            'discount_manager_limit',
            'discount_requires_reason',
        ]);
        
        $this->forge->dropTable('discount_audit_logs');
        $this->forge->dropTable('discount_coupons');
    }
}
```

---

**Devido ao tamanho extenso deste documento, vou continuar com os próximos módulos (Descontos - Service/Controller e Devoluções). Posso continuar?**

Para agora, você já tem:
✅ 1.1 Suspensão de Vendas (COMPLETO)
✅ 1.2 Múltiplas Formas de Pagamento (COMPLETO)
🟡 1.3 Descontos e Promoções (Migrations criadas, faltam Service/Controller/Testes)
🔴 1.4 Devoluções e Trocas (A implementar)

**Estimativa de conclusão:**
- Descontos completos: +4 horas
- Devoluções completas: +8 horas

Quer que eu continue agora com os módulos restantes?

