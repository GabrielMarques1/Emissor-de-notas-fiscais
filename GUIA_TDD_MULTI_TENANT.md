# 🧪 GUIA TDD - IMPLEMENTAÇÃO MULTI-TENANT

**Sistema:** xFiscal ERP - PDV Multi-Tenant SaaS  
**Metodologia:** Test-Driven Development + Multi-Tenant First  
**Versão:** 1.0  

---

## 📋 ÍNDICE

1. [Processo TDD Multi-Tenant](#processo-tdd-multi-tenant)
2. [Configuração de Ambiente de Testes](#configuração-de-ambiente-de-testes)
3. [Templates de Implementação](#templates-de-implementação)
4. [Exemplos Práticos](#exemplos-práticos)
5. [Checklist de Validação](#checklist-de-validação)

---

## 🔄 PROCESSO TDD MULTI-TENANT

### Ciclo Red-Green-Refactor Adaptado

```
┌─────────────────────────────────────────────────────────┐
│                   CICLO TDD MULTI-TENANT                │
└─────────────────────────────────────────────────────────┘

1. 🔴 RED - Escrever Teste de Isolamento
   ├─ Teste de acesso cross-tenant (deve falhar)
   ├─ Teste de validação de tenant ID
   └─ Teste de query filtering

2. 🟢 GREEN - Implementar Código Mínimo
   ├─ Adicionar tenant_id nas queries
   ├─ Validar tenant no início das operações
   └─ Fazer testes passarem

3. 🔵 REFACTOR - Melhorar Sem Quebrar
   ├─ Extrair duplicações
   ├─ Melhorar nomenclatura
   └─ Manter testes verdes

4. 🔒 SECURE - Validar Isolamento
   ├─ Code review focado em multi-tenancy
   ├─ Teste de carga com múltiplos tenants
   └─ Auditoria de queries

5. 📝 DOCUMENT - Documentar
   ├─ Atualizar README
   ├─ Comentar edge cases
   └─ Exemplos de uso

6. ✅ COMMIT - Versionar
   ├─ Commit atômico
   ├─ Mensagem descritiva
   └─ Tag se necessário
```

---

## ⚙️ CONFIGURAÇÃO DE AMBIENTE DE TESTES

### Instalar PHPUnit

```bash
composer require --dev phpunit/phpunit
composer require --dev codeigniter4/devkit
```

### Configurar phpunit.xml

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
    bootstrap="vendor/autoload.php"
    colors="true"
    beStrictAboutOutputDuringTests="true"
    beStrictAboutTodoAnnotatedTests="true"
    failOnRisky="true"
    failOnWarning="true"
    verbose="true">
    
    <testsuites>
        <testsuite name="unit">
            <directory>tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/integration</directory>
        </testsuite>
        <testsuite name="multitenant">
            <directory>tests/multitenant</directory>
        </testsuite>
    </testsuites>

    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">app/Controllers</directory>
            <directory suffix=".php">app/Models</directory>
            <directory suffix=".php">app/Libraries</directory>
        </include>
        <exclude>
            <directory>app/Views</directory>
            <directory>app/ThirdParty</directory>
        </exclude>
    </coverage>

    <php>
        <env name="CI_ENVIRONMENT" value="testing"/>
        <server name="app.baseURL" value="http://localhost:8080/"/>
    </php>
</phpunit>
```

### Criar Base Test Case

```php
// tests/Support/MultiTenantTestCase.php
<?php
namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

abstract class MultiTenantTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    
    protected $refresh = true;
    protected $namespace = 'App';
    
    /**
     * IDs de tenants para testes
     */
    protected int $tenant1Contador = 1;
    protected int $tenant1Empresa = 100;
    
    protected int $tenant2Contador = 1;
    protected int $tenant2Empresa = 200;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar tenants de teste
        $this->createTestTenants();
    }
    
    protected function createTestTenants(): void
    {
        $db = \Config\Database::connect();
        
        // Criar contador
        $db->table('contadores')->insertBatch([
            ['id_contador' => $this->tenant1Contador, 'nome' => 'Contador Teste'],
        ]);
        
        // Criar empresas
        $db->table('empresas')->insertBatch([
            [
                'id_empresa' => $this->tenant1Empresa,
                'id_contador' => $this->tenant1Contador,
                'xFant' => 'Empresa Tenant 1',
                'CNPJ' => '11111111000111',
            ],
            [
                'id_empresa' => $this->tenant2Empresa,
                'id_contador' => $this->tenant2Contador,
                'xFant' => 'Empresa Tenant 2',
                'CNPJ' => '22222222000122',
            ],
        ]);
    }
    
    /**
     * Simula sessão de tenant
     */
    protected function actingAsTenant(int $idContador, int $idEmpresa): void
    {
        $_SESSION['id_contador'] = $idContador;
        $_SESSION['id_empresa'] = $idEmpresa;
        $_SESSION['tipo'] = 1; // Admin
        $_SESSION['usuario'] = 'teste';
    }
    
    /**
     * Valida que recurso pertence ao tenant
     */
    protected function assertTenantOwnership(array $resource, int $idContador, int $idEmpresa): void
    {
        $this->assertEquals($idContador, $resource['id_contador'], 'Recurso não pertence ao contador correto');
        $this->assertEquals($idEmpresa, $resource['id_empresa'], 'Recurso não pertence à empresa correta');
    }
    
    /**
     * Valida que query não retorna dados de outro tenant
     */
    protected function assertNoTenantLeakage(array $results, int $idContador, int $idEmpresa): void
    {
        foreach ($results as $item) {
            $this->assertTenantOwnership($item, $idContador, $idEmpresa);
        }
    }
}
```

---

## 📝 TEMPLATES DE IMPLEMENTAÇÃO

### Template 1: INTEGRAÇÃO TEF

```php
// tests/multitenant/TefMultiTenantTest.php
<?php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\TefService;

class TefMultiTenantTest extends MultiTenantTestCase
{
    protected TefService $tefService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->tefService = new TefService();
    }
    
    /**
     * @test
     * STEP 1.1: Teste de isolamento - transações TEF devem ser isoladas por tenant
     */
    public function tef_transactions_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar transação TEF para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result1 = $this->tefService->authorize(
            idEmpresa: $this->tenant1Empresa,
            valor: 100.00,
            tipo: 'credit',
            parcelas: 1,
            dadosCartao: [
                'numero' => '4111111111111111',
                'titular' => 'TESTE TENANT 1',
                'validade' => '12/2030',
                'cvv' => '123',
            ]
        );
        
        $this->assertTrue($result1['success'], 'Autorização tenant 1 deve ter sucesso');
        $idTransaction1 = $result1['transaction']['id_tef_transaction'];
        
        // ACT: Tentar acessar transação do tenant 1 usando sessão do tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $transactionModel = new \App\Models\TefTransactionModel();
        $transaction = $transactionModel->find($idTransaction1);
        
        // ASSERT: Não deve retornar transação de outro tenant
        $this->assertNull($transaction, 'Não deve acessar transação de outro tenant');
    }
    
    /**
     * @test
     * STEP 1.2: Validar tenant ID obrigatório
     */
    public function tef_authorization_must_require_valid_tenant(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tenant ID obrigatório');
        
        // Tentar autorizar sem tenant na sessão
        session()->remove('id_empresa');
        
        $this->tefService->authorize(
            idEmpresa: 0, // Inválido
            valor: 100.00,
            tipo: 'credit',
            parcelas: 1,
            dadosCartao: []
        );
    }
    
    /**
     * @test
     * STEP 1.3: Queries devem filtrar por tenant
     */
    public function tef_queries_must_filter_by_tenant(): void
    {
        // ARRANGE: Criar transações para 2 tenants
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        $result1 = $this->tefService->authorize($this->tenant1Empresa, 50.00, 'credit', 1, []);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        $result2 = $this->tefService->authorize($this->tenant2Empresa, 75.00, 'credit', 1, []);
        
        // ACT: Buscar transações do tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $transactionModel = new \App\Models\TefTransactionModel();
        $transactions = $transactionModel->findAll();
        
        // ASSERT: Deve retornar apenas transações do tenant 1
        $this->assertCount(1, $transactions);
        $this->assertNoTenantLeakage($transactions, $this->tenant1Contador, $this->tenant1Empresa);
    }
    
    /**
     * @test
     * STEP 1.4: Confirmar/Cancelar deve validar ownership
     */
    public function tef_confirm_must_validate_tenant_ownership(): void
    {
        // ARRANGE: Criar transação para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        $result = $this->tefService->authorize($this->tenant1Empresa, 100.00, 'credit', 1, []);
        $idTransaction = $result['transaction']['id_tef_transaction'];
        
        // ACT: Tentar confirmar com tenant 2 (não é dono)
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $confirmResult = $this->tefService->confirm($idTransaction);
        
        // ASSERT: Deve falhar (transação não encontrada para tenant 2)
        $this->assertFalse($confirmResult['success']);
        $this->assertStringContainsString('não encontrada', $confirmResult['error']);
    }
}
```

---

### Template 2: PIX COM QR CODE

```php
// tests/multitenant/PixMultiTenantTest.php
<?php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\PixService;

class PixMultiTenantTest extends MultiTenantTestCase
{
    protected PixService $pixService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->pixService = new PixService();
    }
    
    /**
     * @test
     * STEP 2.1: QR Codes PIX devem ser isolados por tenant
     */
    public function pix_qrcodes_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Gerar QR Code para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result1 = $this->pixService->generate(
            idEmpresa: $this->tenant1Empresa,
            valor: 50.00,
            descricao: 'Teste Tenant 1'
        );
        
        $this->assertTrue($result1['success']);
        $txid1 = $result1['transaction']['txid'];
        
        // ACT: Tentar buscar QR Code do tenant 1 usando tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $pixModel = new \App\Models\PixTransactionModel();
        $transaction = $pixModel->where('txid', $txid1)->first();
        
        // ASSERT: Não deve retornar transação de outro tenant
        $this->assertNull($transaction, 'Não deve acessar PIX de outro tenant');
    }
    
    /**
     * @test
     * STEP 2.2: Confirmação via webhook deve validar tenant
     */
    public function pix_webhook_must_validate_tenant_ownership(): void
    {
        // ARRANGE: Gerar QR Code para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $result = $this->pixService->generate($this->tenant1Empresa, 100.00, 'Teste');
        $txid = $result['transaction']['txid'];
        
        // ACT: Confirmar pagamento (simular webhook)
        $confirmResult = $this->pixService->confirm($txid, 'E2E123456789');
        
        // ASSERT: Deve confirmar apenas se pertencer ao tenant correto
        $this->assertTrue($confirmResult['success']);
        
        // Verificar que status mudou
        $pixModel = new \App\Models\PixTransactionModel();
        $transaction = $pixModel->where('txid', $txid)
                                ->where('id_empresa', $this->tenant1Empresa)
                                ->first();
        
        $this->assertEquals('confirmed', $transaction['status']);
    }
    
    /**
     * @test
     * STEP 2.3: Expiração deve respeitar tenant
     */
    public function pix_expiration_must_respect_tenant_boundaries(): void
    {
        // ARRANGE: Criar QR Codes expirados para 2 tenants
        $db = \Config\Database::connect();
        
        $db->table('pix_transactions')->insertBatch([
            [
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
                'txid' => 'EXPIRED-TENANT1',
                'valor' => 50.00,
                'status' => 'pending',
                'qr_code' => 'xxx',
                'provider' => 'mercadopago',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
            ],
            [
                'id_contador' => $this->tenant2Contador,
                'id_empresa' => $this->tenant2Empresa,
                'txid' => 'EXPIRED-TENANT2',
                'valor' => 75.00,
                'status' => 'pending',
                'qr_code' => 'yyy',
                'provider' => 'mercadopago',
                'expires_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
            ],
        ]);
        
        // ACT: Expirar QR Codes
        $expired = $this->pixService->expireOld();
        
        // ASSERT: Deve ter expirado 2 (um de cada tenant)
        $this->assertEquals(2, $expired);
        
        // Verificar que ambos foram expirados corretamente
        $pixModel = new \App\Models\PixTransactionModel();
        
        $tx1 = $pixModel->where('txid', 'EXPIRED-TENANT1')->first();
        $tx2 = $pixModel->where('txid', 'EXPIRED-TENANT2')->first();
        
        $this->assertEquals('expired', $tx1['status']);
        $this->assertEquals('expired', $tx2['status']);
    }
}
```

---

### Template 3: MÚLTIPLAS FORMAS DE PAGAMENTO

```php
// tests/multitenant/MultiPaymentMultiTenantTest.php
<?php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Models\PosSaleModel;
use App\Models\PosSalePaymentModel;

class MultiPaymentMultiTenantTest extends MultiTenantTestCase
{
    /**
     * @test
     * STEP 3.1: Pagamentos devem ser vinculados ao tenant correto
     */
    public function multi_payments_must_belong_to_correct_tenant(): void
    {
        // ARRANGE: Criar venda e pagamentos para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $saleModel = new PosSaleModel();
        $idSale = $saleModel->insert([
            'sale_number' => 'VENDA-TEST-1',
            'total' => 150.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        $paymentModel = new PosSalePaymentModel();
        
        // Adicionar 2 pagamentos
        $paymentModel->insertBatch([
            [
                'id_pos_sale' => $idSale,
                'payment_type' => 'cash',
                'amount' => 50.00,
                'installments' => 1,
            ],
            [
                'id_pos_sale' => $idSale,
                'payment_type' => 'credit',
                'amount' => 100.00,
                'installments' => 3,
            ],
        ]);
        
        // ACT: Buscar pagamentos da venda
        $payments = $paymentModel->getBySale($idSale);
        
        // ASSERT: Deve retornar 2 pagamentos
        $this->assertCount(2, $payments);
        
        // Validar que soma = total da venda
        $totalPaid = $paymentModel->getTotalPaid($idSale);
        $this->assertEquals(150.00, $totalPaid);
    }
    
    /**
     * @test
     * STEP 3.2: Não pode buscar pagamentos de venda de outro tenant
     */
    public function cannot_access_payments_from_other_tenant_sale(): void
    {
        // ARRANGE: Criar venda para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $saleModel = new PosSaleModel();
        $idSale1 = $saleModel->insert([
            'sale_number' => 'VENDA-TENANT1',
            'total' => 100.00,
            'status' => 'finalized',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // Adicionar pagamento
        $paymentModel = new PosSalePaymentModel();
        $paymentModel->insert([
            'id_pos_sale' => $idSale1,
            'payment_type' => 'cash',
            'amount' => 100.00,
            'installments' => 1,
        ]);
        
        // ACT: Tentar buscar venda com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sale = $saleModel->find($idSale1);
        
        // ASSERT: Não deve encontrar venda de outro tenant
        $this->assertNull($sale, 'Não deve acessar venda de outro tenant');
    }
    
    /**
     * @test
     * STEP 3.3: Validar soma de pagamentos = total da venda
     */
    public function multi_payment_sum_must_equal_sale_total(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $saleModel = new PosSaleModel();
        $idSale = $saleModel->insert([
            'sale_number' => 'VENDA-MULTI-PAY',
            'total' => 200.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $paymentModel = new PosSalePaymentModel();
        
        // Tentar adicionar pagamentos que somam diferente do total
        $paymentModel->insertBatch([
            ['id_pos_sale' => $idSale, 'payment_type' => 'cash', 'amount' => 50.00, 'installments' => 1],
            ['id_pos_sale' => $idSale, 'payment_type' => 'credit', 'amount' => 100.00, 'installments' => 2],
        ]);
        
        $totalPaid = $paymentModel->getTotalPaid($idSale);
        $sale = $saleModel->find($idSale);
        
        // Validação customizada (pode ser feita no controller)
        $diff = abs($sale['total'] - $totalPaid);
        
        $this->assertGreaterThan(0.01, $diff, 'Soma dos pagamentos deve ser validada antes de finalizar');
    }
}
```

---

### Template 4: SANGRIA E SUPRIMENTO

```php
// tests/multitenant/CaixaMovimentacaoMultiTenantTest.php
<?php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Models\CaixaMovimentacaoModel;
use App\Models\CaixaSessaoModel;

class CaixaMovimentacaoMultiTenantTest extends MultiTenantTestCase
{
    /**
     * @test
     * STEP 4.1: Sangrias devem ser isoladas por tenant
     */
    public function sangria_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Abrir caixa para cada tenant
        $caixaModel = new CaixaSessaoModel();
        
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        $caixa1 = $caixaModel->abrirSessao(1, 100.00, $this->tenant1Contador, $this->tenant1Empresa);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        $caixa2 = $caixaModel->abrirSessao(2, 150.00, $this->tenant2Contador, $this->tenant2Empresa);
        
        // ACT: Registrar sangria para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $movimentacaoModel = new CaixaMovimentacaoModel();
        $idSangria1 = $movimentacaoModel->insert([
            'id_caixa_sessao' => $caixa1['id'],
            'tipo' => 'sangria',
            'valor' => 50.00,
            'motivo' => 'Retirada banco',
            'id_usuario' => 1,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        // Tentar buscar sangria com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $sangria = $movimentacaoModel->find($idSangria1);
        
        // ASSERT: Não deve acessar sangria de outro tenant
        $this->assertNull($sangria, 'Não deve acessar sangria de outro tenant');
    }
    
    /**
     * @test
     * STEP 4.2: Movimentações devem aparecer apenas no relatório do tenant correto
     */
    public function movimentacoes_must_appear_only_in_correct_tenant_report(): void
    {
        // ARRANGE: Criar caixas e movimentações para 2 tenants
        $caixaModel = new CaixaSessaoModel();
        $movimentacaoModel = new CaixaMovimentacaoModel();
        
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        $caixa1 = $caixaModel->abrirSessao(1, 100.00, $this->tenant1Contador, $this->tenant1Empresa);
        
        $movimentacaoModel->insertBatch([
            [
                'id_caixa_sessao' => $caixa1['id'],
                'tipo' => 'sangria',
                'valor' => 30.00,
                'motivo' => 'Sangria 1',
                'id_usuario' => 1,
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
                'data_hora' => date('Y-m-d H:i:s'),
            ],
            [
                'id_caixa_sessao' => $caixa1['id'],
                'tipo' => 'suprimento',
                'valor' => 20.00,
                'motivo' => 'Suprimento 1',
                'id_usuario' => 1,
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
                'data_hora' => date('Y-m-d H:i:s'),
            ],
        ]);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        $caixa2 = $caixaModel->abrirSessao(2, 200.00, $this->tenant2Contador, $this->tenant2Empresa);
        
        $movimentacaoModel->insert([
            'id_caixa_sessao' => $caixa2['id'],
            'tipo' => 'sangria',
            'valor' => 50.00,
            'motivo' => 'Sangria 2',
            'id_usuario' => 2,
            'id_contador' => $this->tenant2Contador,
            'id_empresa' => $this->tenant2Empresa,
            'data_hora' => date('Y-m-d H:i:s'),
        ]);
        
        // ACT: Buscar movimentações de cada tenant
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        $movimentacoes1 = $movimentacaoModel->getBySessao($caixa1['id']);
        
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        $movimentacoes2 = $movimentacaoModel->getBySessao($caixa2['id']);
        
        // ASSERT
        $this->assertCount(2, $movimentacoes1, 'Tenant 1 deve ter 2 movimentações');
        $this->assertCount(1, $movimentacoes2, 'Tenant 2 deve ter 1 movimentação');
        
        $this->assertNoTenantLeakage($movimentacoes1, $this->tenant1Contador, $this->tenant1Empresa);
        $this->assertNoTenantLeakage($movimentacoes2, $this->tenant2Contador, $this->tenant2Empresa);
    }
}
```

---

## 🔍 CHECKLIST DE VALIDAÇÃO MULTI-TENANT

### Para Cada Funcionalidade Implementada

```markdown
## VALIDAÇÃO MULTI-TENANT - [Nome da Funcionalidade]

### ✅ Testes
- [ ] Teste de isolamento criado e passando
- [ ] Teste de validação de tenant ID criado e passando
- [ ] Teste de acesso cross-tenant criado e falhando corretamente
- [ ] Teste de queries filtering criado e passando
- [ ] Cobertura de testes > 80%

### ✅ Código
- [ ] Todas as queries incluem `id_contador` AND `id_empresa`
- [ ] Validação de tenant no início de TODAS as operações
- [ ] Model estende `BaseAppModel` com `$enforceTenant = true`
- [ ] Controller usa `getTenantIds()` ou trait equivalente
- [ ] Método `validateTenantOwnership()` usado em updates/deletes

### ✅ Banco de Dados
- [ ] Tabela possui colunas `id_contador` e `id_empresa`
- [ ] Índice composto criado: `INDEX idx_tenant (id_contador, id_empresa)`
- [ ] Foreign keys configuradas com CASCADE
- [ ] Campos de tenant em `$allowedFields` do Model

### ✅ Logs e Auditoria
- [ ] Logs incluem `tenant_id` em todas as operações
- [ ] Eventos críticos registrados em `audit_logs`
- [ ] Logs estruturados (JSON) com contexto completo

### ✅ Cache (se aplicável)
- [ ] Chaves de cache incluem tenant ID
- [ ] Formato: `chave:{id_contador}:{id_empresa}:{recurso}`
- [ ] Invalidação por tenant funciona corretamente

### ✅ Documentação
- [ ] Comentários explicam considerações multi-tenant
- [ ] README atualizado com exemplos de uso
- [ ] Edge cases documentados
- [ ] Breaking changes listados (se houver)

### ✅ Code Review
- [ ] Revisado por desenvolvedor sênior
- [ ] Checklist de segurança multi-tenant aplicado
- [ ] Queries SQL revisadas manualmente
- [ ] Sem violações de Psalm/PHPStan

### ✅ Deploy
- [ ] Migration testada em ambiente de staging
- [ ] Rollback testado
- [ ] Monitoramento configurado
- [ ] Alerta de erros multi-tenant ativo
```

---

## 📚 COMANDOS ÚTEIS

### Rodar Testes

```bash
# Todos os testes
./vendor/bin/phpunit

# Apenas testes multi-tenant
./vendor/bin/phpunit --testsuite multitenant

# Teste específico
./vendor/bin/phpunit tests/multitenant/TefMultiTenantTest.php

# Com cobertura
./vendor/bin/phpunit --coverage-html coverage/
```

### Rodar Linter

```bash
# PHPStan
./vendor/bin/phpstan analyse app

# PHP CS Fixer
./vendor/bin/php-cs-fixer fix app --dry-run
```

### Migrations

```bash
# Rodar migrations
php spark migrate

# Rollback última migration
php spark migrate:rollback

# Status
php spark migrate:status
```

---

## 🎯 EXEMPLO COMPLETO: IMPLEMENTAR SANGRIA

### STEP 1: Criar Testes PRIMEIRO

```php
// tests/multitenant/SangriaTest.php
<?php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\CaixaService;

class SangriaTest extends MultiTenantTestCase
{
    /**
     * @test
     */
    public function deve_registrar_sangria_apenas_para_tenant_correto(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $caixaService = new CaixaService();
        
        // Abrir caixa
        $caixa = $caixaService->abrirCaixa(1, 200.00);
        
        // Registrar sangria
        $sangria = $caixaService->registrarSangria(
            idCaixaSessao: $caixa['id'],
            valor: 50.00,
            motivo: 'Pagamento fornecedor'
        );
        
        $this->assertNotNull($sangria);
        $this->assertEquals(50.00, $sangria['valor']);
        $this->assertEquals('sangria', $sangria['tipo']);
        $this->assertTenantOwnership($sangria, $this->tenant1Contador, $this->tenant1Empresa);
    }
}
```

### STEP 2: Implementar (vai falhar)

```bash
# Rodar teste
./vendor/bin/phpunit tests/multitenant/SangriaTest.php
```

**Resultado esperado:** 🔴 RED (falha - classe não existe)

### STEP 3: Criar Migration

```php
// app/Database/Migrations/2025-10-05-000001_CreateCaixaMovimentacoes.php
<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCaixaMovimentacoes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_movimentacao' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_caixa_sessao' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tipo' => [
                'type'       => 'ENUM',
                'constraint' => ['sangria', 'suprimento'],
            ],
            'valor' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'motivo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'id_usuario' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'data_hora' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id_movimentacao', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_caixa_sessao');
        
        $this->forge->addForeignKey('id_caixa_sessao', 'caixa_sessoes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('caixa_movimentacoes');
    }
    
    public function down()
    {
        $this->forge->dropTable('caixa_movimentacoes');
    }
}
```

### STEP 4: Criar Model

```php
// app/Models/CaixaMovimentacaoModel.php
<?php
namespace App\Models;

class CaixaMovimentacaoModel extends BaseAppModel
{
    protected $table = 'caixa_movimentacoes';
    protected $primaryKey = 'id_movimentacao';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_caixa_sessao', 'tipo', 'valor', 'motivo',
        'id_usuario', 'id_contador', 'id_empresa', 'data_hora',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'id_caixa_sessao' => 'required|integer',
        'tipo' => 'required|in_list[sangria,suprimento]',
        'valor' => 'required|decimal|greater_than[0]',
        'motivo' => 'required|min_length[3]|max_length[255]',
    ];
    
    /**
     * Busca movimentações de uma sessão de caixa
     */
    public function getBySessao(int $idCaixaSessao): array
    {
        return $this->where('id_caixa_sessao', $idCaixaSessao)
                    ->orderBy('data_hora', 'ASC')
                    ->findAll();
    }
}
```

### STEP 5: Criar Service

```php
// app/Libraries/CaixaService.php
<?php
namespace App\Libraries;

use App\Models\CaixaSessaoModel;
use App\Models\CaixaMovimentacaoModel;
use App\Traits\TenantAwareTrait;

class CaixaService
{
    use TenantAwareTrait;
    
    protected CaixaSessaoModel $caixaModel;
    protected CaixaMovimentacaoModel $movimentacaoModel;
    
    public function __construct()
    {
        $this->caixaModel = new CaixaSessaoModel();
        $this->movimentacaoModel = new CaixaMovimentacaoModel();
    }
    
    /**
     * Abre caixa com valor inicial
     */
    public function abrirCaixa(int $idUsuario, float $valorInicial): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        return $this->caixaModel->abrirSessao($idUsuario, $valorInicial, $idContador, $idEmpresa);
    }
    
    /**
     * Registra sangria (retirada de dinheiro)
     */
    public function registrarSangria(int $idCaixaSessao, float $valor, string $motivo): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Validar que caixa pertence ao tenant e está aberto
        $caixa = $this->caixaModel->find($idCaixaSessao);
        
        if (!$caixa) {
            throw new \RuntimeException('Caixa não encontrado');
        }
        
        if (!$this->validateTenantOwnership($caixa, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Caixa não pertence a este tenant');
        }
        
        if ($caixa['status'] !== 'aberto') {
            throw new \RuntimeException('Caixa não está aberto');
        }
        
        // Registrar movimentação
        $session = session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        
        $data = [
            'id_caixa_sessao' => $idCaixaSessao,
            'tipo' => 'sangria',
            'valor' => $valor,
            'motivo' => $motivo,
            'id_usuario' => $idUsuario ?: null,
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
            'data_hora' => date('Y-m-d H:i:s'),
        ];
        
        $idMovimentacao = $this->movimentacaoModel->insert($data);
        
        log_message('info', '[Caixa] Sangria registrada', [
            'id_movimentacao' => $idMovimentacao,
            'id_caixa_sessao' => $idCaixaSessao,
            'valor' => $valor,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return $this->movimentacaoModel->find($idMovimentacao);
    }
    
    /**
     * Registra suprimento (entrada de dinheiro)
     */
    public function registrarSuprimento(int $idCaixaSessao, float $valor, string $motivo): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Validar caixa (mesma lógica de sangria)
        $caixa = $this->caixaModel->find($idCaixaSessao);
        
        if (!$caixa || !$this->validateTenantOwnership($caixa, $idContador, $idEmpresa)) {
            throw new \RuntimeException('Caixa inválido');
        }
        
        if ($caixa['status'] !== 'aberto') {
            throw new \RuntimeException('Caixa não está aberto');
        }
        
        // Registrar movimentação
        $session = session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        
        $data = [
            'id_caixa_sessao' => $idCaixaSessao,
            'tipo' => 'suprimento',
            'valor' => $valor,
            'motivo' => $motivo,
            'id_usuario' => $idUsuario ?: null,
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
            'data_hora' => date('Y-m-d H:i:s'),
        ];
        
        $idMovimentacao = $this->movimentacaoModel->insert($data);
        
        log_message('info', '[Caixa] Suprimento registrado', [
            'id_movimentacao' => $idMovimentacao,
            'id_caixa_sessao' => $idCaixaSessao,
            'valor' => $valor,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return $this->movimentacaoModel->find($idMovimentacao);
    }
}
```

### STEP 6: Rodar Testes Novamente

```bash
# Rodar migration
php spark migrate

# Rodar testes
./vendor/bin/phpunit tests/multitenant/SangriaTest.php
```

**Resultado esperado:** 🟢 GREEN (testes passando)

### STEP 7: Refatorar (se necessário)

- Extrair validações comuns
- Melhorar nomenclatura
- Adicionar comments
- **Manter testes verdes**

### STEP 8: Code Review

- Aplicar checklist multi-tenant
- Revisar queries SQL
- Validar logs

### STEP 9: Commit

```bash
git add .
git commit -m "feat(caixa): Implementar sangria e suprimento com isolamento multi-tenant

- Criar migration caixa_movimentacoes
- Criar CaixaMovimentacaoModel com $enforceTenant
- Criar CaixaService com registrarSangria() e registrarSuprimento()
- Validação de tenant ownership antes de registrar movimentação
- Logs incluem tenant_id
- Testes multi-tenant com 100% cobertura

Closes #123"
```

---

## 🎓 CONCLUSÃO

### Princípios TDD Multi-Tenant

1. **Testes PRIMEIRO** - Sempre escrever testes antes do código
2. **Isolamento SEMPRE** - Todo teste deve validar isolamento por tenant
3. **Mínimo Viável** - Implementar apenas o necessário para passar nos testes
4. **Refatorar SEM MEDO** - Testes garantem que nada quebrou
5. **Documentar DURANTE** - Documentação vem junto com código

### Benefícios

✅ **Segurança:** Isolamento multi-tenant garantido desde o início  
✅ **Qualidade:** Código testado = código confiável  
✅ **Manutenibilidade:** Testes facilitam refatoração futura  
✅ **Documentação:** Testes servem como documentação viva  
✅ **Confiança:** Deploy seguro em produção  

---

**Versão:** 1.0  
**Última atualização:** 01/10/2025  
**Mantido por:** Time de Desenvolvimento xFiscal ERP

