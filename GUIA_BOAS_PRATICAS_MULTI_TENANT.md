# 🔒 GUIA DE BOAS PRÁTICAS - MULTI-TENANT

**Sistema:** xFiscal ERP - PDV Multi-Tenant SaaS  
**Objetivo:** Garantir isolamento total de dados entre tenants  
**Versão:** 1.0  

---

## 📋 ÍNDICE

1. [Princípios Fundamentais](#princípios-fundamentais)
2. [Estrutura de Banco de Dados](#estrutura-de-banco-de-dados)
3. [Models e BaseAppModel](#models-e-baseappmodel)
4. [Controllers e Filtros](#controllers-e-filtros)
5. [Queries Manuais](#queries-manuais)
6. [Validações e Segurança](#validações-e-segurança)
7. [Logs e Auditoria](#logs-e-auditoria)
8. [Testes](#testes)
9. [Checklist de Revisão](#checklist-de-revisão)
10. [Anti-Patterns](#anti-patterns)

---

## 1️⃣ PRINCÍPIOS FUNDAMENTAIS

### Regra de Ouro 🏆
> **Todo acesso a dados DEVE filtrar por `id_contador` e `id_empresa`**

### Hierarquia de Tenants

```
Contador (Master)
    └── Empresa 1 (Tenant)
        ├── Loja A
        ├── Loja B
    └── Empresa 2 (Tenant)
        ├── Loja C
```

### Campos Obrigatórios

Toda tabela transacional DEVE ter:
```sql
id_contador INT NOT NULL,
id_empresa  INT NOT NULL,
INDEX idx_tenant (id_contador, id_empresa)
```

**Exceções permitidas:**
- Tabelas de configuração global (sem dados de cliente)
- Tabelas de lookup (UFs, municípios, unidades)
- Tabelas de sistema (logins, permissões)

---

## 2️⃣ ESTRUTURA DE BANCO DE DADOS

### ✅ Exemplo CORRETO

```sql
CREATE TABLE pos_sales (
    id_pos_sale INT AUTO_INCREMENT PRIMARY KEY,
    
    -- OBRIGATÓRIO: Campos de tenant
    id_contador INT NOT NULL,
    id_empresa  INT NOT NULL,
    
    -- Dados da venda
    sale_number VARCHAR(32) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL,
    
    -- Timestamps
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    
    -- Índices
    INDEX idx_tenant (id_contador, id_empresa),
    INDEX idx_status_date (status, created_at),
    
    -- Foreign Keys
    FOREIGN KEY (id_contador) REFERENCES contadores(id_contador) ON DELETE CASCADE,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### ❌ Exemplo ERRADO

```sql
CREATE TABLE pos_sales (
    id_pos_sale INT AUTO_INCREMENT PRIMARY KEY,
    
    -- ❌ ERRADO: Faltam campos de tenant
    sale_number VARCHAR(32) NOT NULL,
    total DECIMAL(10,2) NOT NULL
);
```

### Índices Compostos

**Sempre criar índice composto** para queries por tenant:

```sql
-- ✅ CORRETO
INDEX idx_tenant_status (id_contador, id_empresa, status);
INDEX idx_tenant_date (id_contador, id_empresa, created_at);

-- ❌ ERRADO (índices separados são menos eficientes)
INDEX idx_contador (id_contador);
INDEX idx_empresa (id_empresa);
```

---

## 3️⃣ MODELS E BASEAPPMODEL

### Herdar de BaseAppModel

✅ **SEMPRE** estenda `BaseAppModel`:

```php
<?php
namespace App\Models;

class PosSaleModel extends BaseAppModel
{
    protected $table = 'pos_sales';
    protected $primaryKey = 'id_pos_sale';
    protected $returnType = 'array'; // ou 'App\\Entities\\PosSale'
    
    // ✅ Filtragem automática ativada (padrão)
    protected $enforceTenant = true;
    
    protected $allowedFields = [
        'sale_number', 'total', 'discount', 'status',
        'id_contador', 'id_empresa', // Incluir nos allowed
    ];
    
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    
    protected $validationRules = [
        'sale_number' => 'required|min_length[3]|max_length[32]',
        'total'       => 'required|decimal|greater_than[0]',
        'status'      => 'required|in_list[draft,finalized,cancelled]',
    ];
}
```

### Quando Desabilitar $enforceTenant

⚠️ **Apenas se:**
1. Tabela não possui campos de tenant (ex: `ufs`, `municipios`)
2. Você implementa filtragem manual em TODOS os métodos custom

```php
<?php
namespace App\Models;

class UfModel extends BaseAppModel
{
    // ✅ OK para desabilitar (tabela de lookup sem tenant)
    protected $enforceTenant = false;
    
    protected $table = 'ufs';
    protected $primaryKey = 'id_uf';
}
```

### Métodos Custom em Models

✅ **SEMPRE filtrar por tenant**:

```php
<?php
namespace App\Models;

class PosSaleModel extends BaseAppModel
{
    /**
     * Busca vendas finalizadas do mês atual
     * 
     * ✅ CORRETO: Filtra por tenant
     */
    public function getMonthSales(int $idContador, int $idEmpresa): array
    {
        $firstDay = date('Y-m-01 00:00:00');
        $lastDay = date('Y-m-t 23:59:59');
        
        return $this->where('id_contador', $idContador)
                    ->where('id_empresa', $idEmpresa)
                    ->where('status', 'finalized')
                    ->where('created_at >=', $firstDay)
                    ->where('created_at <=', $lastDay)
                    ->findAll();
    }
    
    /**
     * ❌ ERRADO: Não filtra por tenant
     */
    public function getAllSales(): array
    {
        return $this->findAll(); // ❌ Vai retornar de TODOS os tenants!
    }
}
```

### Desabilitando Enforcement Temporariamente

⚠️ **Use com extremo cuidado**:

```php
<?php
// Cenário: Migração de dados ou operação administrativa

$model = new PosSaleModel();

// Desabilitar temporariamente
$model->enforceTenant = false;

// Operação administrativa
$allSales = $model->findAll();

// ✅ Reabilitar imediatamente
$model->enforceTenant = true;
```

---

## 4️⃣ CONTROLLERS E FILTROS

### Aplicar Filtros em Rotas

```php
// app/Config/Routes.php

// ✅ CORRETO: Filtros de tenant em TODAS as rotas protegidas
$routes->group('api/pos', ['filter' => ['auth', 'pdvaccess']], function($routes) {
    $routes->get('', 'Api\Pos::index');
    $routes->get('(:num)', 'Api\Pos::show/$1');
    $routes->post('', 'Api\Pos::create');
    $routes->put('(:num)', 'Api\Pos::update/$1');
    $routes->delete('(:num)', 'Api\Pos::delete/$1');
});

// ❌ ERRADO: Sem filtros
$routes->group('api/pos', function($routes) {
    $routes->get('', 'Api\Pos::index'); // ❌ Inseguro!
});
```

### Extrair Tenant IDs em Controllers

✅ **Criar Trait para evitar duplicação**:

```php
// app/Traits/TenantAwareTrait.php
<?php
namespace App\Traits;

trait TenantAwareTrait
{
    /**
     * Obtém IDs de tenant da sessão
     * 
     * @return array [idContador, idEmpresa]
     */
    protected function getTenantIds(): array
    {
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador, $idEmpresa] = resolve_tenant_ids();
        }
        
        if ($idContador === 0 || $idEmpresa === 0) {
            throw new \RuntimeException('Tenant não identificado');
        }
        
        return [$idContador, $idEmpresa];
    }
    
    /**
     * Valida que recurso pertence ao tenant
     */
    protected function validateTenantOwnership(array $resource, int $idContador, int $idEmpresa): bool
    {
        $resourceContador = (int) ($resource['id_contador'] ?? 0);
        $resourceEmpresa  = (int) ($resource['id_empresa'] ?? 0);
        
        return ($resourceContador === $idContador && $resourceEmpresa === $idEmpresa);
    }
}
```

### Uso do Trait em Controllers

```php
<?php
namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Traits\TenantAwareTrait;

class Pos extends ResourceController
{
    use TenantAwareTrait; // ✅ Incluir trait
    
    protected $format = 'json';
    protected $modelName = 'App\\Models\\PosSaleModel';
    
    public function index()
    {
        try {
            // ✅ Usar método do trait
            [$idContador, $idEmpresa] = $this->getTenantIds();
            
            $builder = $this->model->builder();
            
            // ✅ Filtrar por tenant
            $builder->where('pos_sales.id_contador', $idContador)
                    ->where('pos_sales.id_empresa', $idEmpresa);
            
            $items = $builder->orderBy('id_pos_sale', 'DESC')->get()->getResultArray();
            
            return $this->respond($items);
            
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    public function show($id = null)
    {
        try {
            [$idContador, $idEmpresa] = $this->getTenantIds();
            
            // ✅ BaseAppModel já filtra automaticamente
            $data = $this->model->find($id);
            
            if (!$data) {
                return $this->failNotFound('Recurso não encontrado');
            }
            
            // ✅ VALIDAÇÃO ADICIONAL (defesa em profundidade)
            if (!$this->validateTenantOwnership($data, $idContador, $idEmpresa)) {
                return $this->failForbidden('Acesso negado');
            }
            
            return $this->respond($data);
            
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
```

---

## 5️⃣ QUERIES MANUAIS

### Query Builder

✅ **SEMPRE filtrar manualmente**:

```php
<?php
// ✅ CORRETO
[$idContador, $idEmpresa] = $this->getTenantIds();

$db = \Config\Database::connect();

$rows = $db->table('pos_sales')
    ->select('payment_type, SUM(total) as total')
    ->where('id_contador', $idContador)
    ->where('id_empresa', $idEmpresa)
    ->where('status', 'finalized')
    ->groupBy('payment_type')
    ->get()->getResultArray();
```

### Queries SQL Raw

⚠️ **Use prepared statements**:

```php
<?php
// ✅ CORRETO: Prepared statements
[$idContador, $idEmpresa] = $this->getTenantIds();

$sql = "
    SELECT 
        DATE(created_at) as date,
        SUM(total) as total,
        COUNT(*) as count
    FROM pos_sales
    WHERE id_contador = ? 
      AND id_empresa = ?
      AND status = 'finalized'
      AND created_at >= ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";

$rows = $db->query($sql, [$idContador, $idEmpresa, date('Y-m-01')])->getResultArray();
```

```php
// ❌ ERRADO: SQL Injection vulnerability
$sql = "SELECT * FROM pos_sales WHERE id_empresa = {$idEmpresa}"; // ❌ PERIGO!
```

### JOINs

✅ **Filtrar em TODAS as tabelas do JOIN**:

```php
<?php
// ✅ CORRETO
$rows = $db->table('pos_sales as s')
    ->select('s.*, c.nome as cliente_nome')
    ->join('clientes as c', 'c.id_cliente = s.id_cliente', 'left')
    ->where('s.id_contador', $idContador)
    ->where('s.id_empresa', $idEmpresa)
    ->where('c.id_contador', $idContador) // ✅ Filtrar tabela cliente também
    ->where('c.id_empresa', $idEmpresa)
    ->get()->getResultArray();
```

---

## 6️⃣ VALIDAÇÕES E SEGURANÇA

### Validação de ID de Tenant

✅ **SEMPRE validar antes de operações críticas**:

```php
<?php
public function update($id = null)
{
    try {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // 1. Buscar recurso
        $existing = $this->model->find($id);
        
        if (!$existing) {
            return $this->failNotFound('Recurso não encontrado');
        }
        
        // 2. ✅ Validar tenant ownership
        if (!$this->validateTenantOwnership($existing, $idContador, $idEmpresa)) {
            log_message('warning', "Tentativa de acesso cross-tenant: user={$idContador},{$idEmpresa} tentou acessar recurso de {$existing['id_contador']},{$existing['id_empresa']}");
            
            return $this->failForbidden('Você não tem permissão para acessar este recurso');
        }
        
        // 3. Atualizar
        $payload = $this->request->getJSON(true);
        
        // ✅ Não permitir alterar tenant IDs
        unset($payload['id_contador'], $payload['id_empresa']);
        
        if (!$this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }
        
        return $this->respond($this->model->find($id));
        
    } catch (\Throwable $e) {
        return $this->failServerError($e->getMessage());
    }
}
```

### Prevenção de Parameter Tampering

```php
<?php
// ✅ CORRETO: Ignorar tenant IDs do payload e usar da sessão
public function create()
{
    [$idContador, $idEmpresa] = $this->getTenantIds();
    
    $payload = $this->request->getJSON(true);
    
    // ✅ Forçar tenant IDs da sessão (ignorar do payload)
    $payload['id_contador'] = $idContador;
    $payload['id_empresa']  = $idEmpresa;
    
    if (!$this->model->insert($payload)) {
        return $this->failValidationErrors($this->model->errors());
    }
    
    return $this->respondCreated($this->model->find($this->model->getInsertID()));
}
```

```php
// ❌ ERRADO: Confiar no payload do usuário
public function create()
{
    $payload = $this->request->getJSON(true);
    
    // ❌ PERIGO: Usuário pode enviar id_empresa de outro tenant!
    $this->model->insert($payload);
}
```

---

## 7️⃣ LOGS E AUDITORIA

### Formato de Logs

✅ **SEMPRE incluir tenant ID nos logs**:

```php
<?php
// ✅ CORRETO
log_message('info', '[Venda] Venda finalizada: id={id}, tenant={tenant}', [
    'id' => $idVenda,
    'tenant' => "{$idContador}:{$idEmpresa}",
    'user' => $idUsuario,
    'valor' => $total,
]);

// ✅ CORRETO: Usar contexto estruturado
log_message('warning', 'Tentativa de acesso cross-tenant', [
    'tenant_origem' => "{$idContadorSessao}:{$idEmpresaSessao}",
    'tenant_destino' => "{$resourceContador}:{$resourceEmpresa}",
    'usuario' => $idUsuario,
    'ip' => $this->request->getIPAddress(),
    'recurso' => $recursoType,
    'recurso_id' => $recursoId,
]);
```

### Logs de Auditoria

Criar tabela dedicada para eventos críticos:

```sql
CREATE TABLE audit_logs (
    id_log BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- Tenant
    id_contador INT NOT NULL,
    id_empresa INT NOT NULL,
    
    -- Evento
    event_type VARCHAR(32) NOT NULL, -- 'sale.create', 'sale.cancel', 'payment.tef', etc
    event_action VARCHAR(16) NOT NULL, -- 'create', 'update', 'delete', 'view'
    
    -- Recurso afetado
    resource_type VARCHAR(32) NOT NULL, -- 'pos_sales', 'products', etc
    resource_id INT,
    
    -- Usuário
    id_usuario INT,
    user_ip VARCHAR(45),
    user_agent TEXT,
    
    -- Dados
    before_data JSON NULL,
    after_data JSON NULL,
    
    -- Timestamp
    created_at DATETIME NOT NULL,
    
    INDEX idx_tenant (id_contador, id_empresa),
    INDEX idx_event (event_type, created_at),
    INDEX idx_resource (resource_type, resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Trait de Auditoria:

```php
// app/Traits/AuditableTrait.php
<?php
namespace App\Traits;

trait AuditableTrait
{
    protected function audit(
        string $eventType,
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?array $beforeData = null,
        ?array $afterData = null
    ): void {
        try {
            [$idContador, $idEmpresa] = $this->getTenantIds();
            
            $session = session();
            $idUsuario = (int) ($session->get('id_usuario') ?? 0);
            
            $auditModel = new \App\Models\AuditLogModel();
            
            $auditModel->insert([
                'id_contador' => $idContador,
                'id_empresa'  => $idEmpresa,
                'event_type'  => $eventType,
                'event_action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'id_usuario' => $idUsuario ?: null,
                'user_ip' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()->getAgentString(),
                'before_data' => $beforeData ? json_encode($beforeData) : null,
                'after_data' => $afterData ? json_encode($afterData) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
        } catch (\Throwable $e) {
            // Não falhar operação por erro de auditoria
            log_message('error', '[Audit] Falha ao registrar: ' . $e->getMessage());
        }
    }
}
```

Uso:

```php
<?php
class Pos extends ResourceController
{
    use TenantAwareTrait, AuditableTrait;
    
    public function delete($id = null)
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $existing = $this->model->find($id);
        
        if (!$existing) {
            return $this->failNotFound();
        }
        
        if (!$this->validateTenantOwnership($existing, $idContador, $idEmpresa)) {
            return $this->failForbidden();
        }
        
        // Registrar antes de deletar
        $this->audit(
            eventType: 'sale.delete',
            action: 'delete',
            resourceType: 'pos_sales',
            resourceId: $id,
            beforeData: $existing
        );
        
        $this->model->delete($id);
        
        return $this->respondDeleted(['id' => $id]);
    }
}
```

---

## 8️⃣ TESTES

### Testes Unitários de Isolamento

```php
// tests/Models/PosSaleModelTest.php
<?php
namespace Tests\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\PosSaleModel;

class PosSaleModelTest extends CIUnitTestCase
{
    protected PosSaleModel $model;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PosSaleModel();
    }
    
    public function testFindRespectsMultiTenant()
    {
        // Criar vendas de 2 tenants diferentes
        $idSale1 = $this->model->insert([
            'id_contador' => 1,
            'id_empresa'  => 5,
            'sale_number' => 'VENDA-1',
            'total' => 100,
            'status' => 'finalized',
        ]);
        
        $idSale2 = $this->model->insert([
            'id_contador' => 1,
            'id_empresa'  => 6, // ← Empresa diferente
            'sale_number' => 'VENDA-2',
            'total' => 200,
            'status' => 'finalized',
        ]);
        
        // Simular sessão do tenant 1 (empresa 5)
        $_SESSION['id_contador'] = 1;
        $_SESSION['id_empresa']  = 5;
        
        // ✅ Deve retornar apenas venda do tenant 1
        $result = $this->model->findAll();
        
        $this->assertCount(1, $result);
        $this->assertEquals('VENDA-1', $result[0]['sale_number']);
        
        // ❌ Não deve retornar venda de outro tenant
        $this->assertEmpty(array_filter($result, fn($s) => $s['sale_number'] === 'VENDA-2'));
    }
    
    public function testUpdatePreventsCrossTenant()
    {
        // Criar venda do tenant 1
        $idSale = $this->model->insert([
            'id_contador' => 1,
            'id_empresa'  => 5,
            'sale_number' => 'VENDA-X',
            'total' => 100,
            'status' => 'draft',
        ]);
        
        // Simular sessão de tenant 2 (empresa 6)
        $_SESSION['id_contador'] = 1;
        $_SESSION['id_empresa']  = 6; // ← Diferente!
        
        // Tentar atualizar venda de outro tenant
        $result = $this->model->update($idSale, [
            'status' => 'finalized',
        ]);
        
        // ✅ Deve falhar (BaseAppModel previne)
        $this->assertFalse($result);
        
        // Verificar que não foi alterado
        $_SESSION['id_empresa'] = 5; // Voltar ao tenant correto
        $sale = $this->model->find($idSale);
        $this->assertEquals('draft', $sale['status']); // ✅ Ainda draft
    }
}
```

### Testes de Integração de API

```php
// tests/Api/PosApiTest.php
<?php
namespace Tests\Api;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;

class PosApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar sessão do tenant 1
        $_SESSION['id_contador'] = 1;
        $_SESSION['id_empresa']  = 5;
        $_SESSION['tipo'] = 1; // Admin
    }
    
    public function testListSalesReturnsOnlyOwnTenant()
    {
        // Criar vendas de 2 tenants
        $db = \Config\Database::connect();
        
        $db->table('pos_sales')->insertBatch([
            ['id_contador' => 1, 'id_empresa' => 5, 'sale_number' => 'VENDA-T1', 'total' => 100, 'status' => 'finalized'],
            ['id_contador' => 1, 'id_empresa' => 6, 'sale_number' => 'VENDA-T2', 'total' => 200, 'status' => 'finalized'], // Outro tenant
        ]);
        
        // Requisitar lista
        $result = $this->withSession($_SESSION)
                       ->get('/api/pos');
        
        $result->assertOK();
        $result->assertJSONFragment(['sale_number' => 'VENDA-T1']);
        
        // ✅ Não deve retornar venda de outro tenant
        $body = json_decode($result->getJSON(), true);
        $saleNumbers = array_column($body['data'] ?? [], 'sale_number');
        
        $this->assertNotContains('VENDA-T2', $saleNumbers);
    }
    
    public function testCannotAccessOtherTenantSale()
    {
        // Criar venda de outro tenant
        $db = \Config\Database::connect();
        
        $idSaleOtherTenant = $db->table('pos_sales')->insert([
            'id_contador' => 1,
            'id_empresa'  => 6, // ← Outro tenant
            'sale_number' => 'VENDA-HACKER',
            'total' => 999,
            'status' => 'finalized',
        ]);
        
        // Tentar acessar
        $result = $this->withSession($_SESSION)
                       ->get("/api/pos/{$idSaleOtherTenant}");
        
        // ✅ Deve retornar 404 ou 403
        $this->assertContains($result->getStatusCode(), [403, 404]);
    }
}
```

---

## 9️⃣ CHECKLIST DE REVISÃO

### Antes de Fazer Pull Request

- [ ] **Tabela possui `id_contador` e `id_empresa`?**
- [ ] **Índice composto criado?** `INDEX idx_tenant (id_contador, id_empresa)`
- [ ] **Foreign keys configuradas com CASCADE?**
- [ ] **Model estende `BaseAppModel`?**
- [ ] **`$enforceTenant = true` (ou desabilitado justificadamente)?**
- [ ] **Campos `id_contador` e `id_empresa` nos `$allowedFields`?**
- [ ] **Controller usa `getTenantIds()` ou trait equivalente?**
- [ ] **Queries manuais filtram por tenant?**
- [ ] **JOINs filtram TODAS as tabelas envolvidas?**
- [ ] **Validação de ownership em updates/deletes?**
- [ ] **Logs incluem tenant ID?**
- [ ] **Testes de isolamento escritos?**
- [ ] **Código revisado por par (pair review)?**

### Code Review - Perguntas

1. **Esta query pode retornar dados de outro tenant?**
2. **E se o usuário alterar os IDs no payload?**
3. **E se o usuário forçar um ID no URL de outro tenant?**
4. **Os logs permitem rastrear ações por tenant?**
5. **O cache está isolado por tenant?**

---

## 🔟 ANTI-PATTERNS

### ❌ Anti-Pattern 1: Confiar no Input do Usuário

```php
// ❌ PERIGO CRÍTICO
public function create()
{
    $payload = $this->request->getJSON(true);
    
    // Usuário pode enviar qualquer id_empresa!
    $this->model->insert($payload);
}
```

**✅ Correção:**
```php
public function create()
{
    [$idContador, $idEmpresa] = $this->getTenantIds();
    
    $payload = $this->request->getJSON(true);
    $payload['id_contador'] = $idContador; // Forçar da sessão
    $payload['id_empresa']  = $idEmpresa;
    
    $this->model->insert($payload);
}
```

---

### ❌ Anti-Pattern 2: Query sem Filtro

```php
// ❌ VAZAMENTO DE DADOS
public function getTotalSales()
{
    $db = \Config\Database::connect();
    
    $result = $db->table('pos_sales')
        ->selectSum('total')
        ->where('status', 'finalized')
        ->get()->getRowArray();
    
    return $result['total']; // Retorna de TODOS os tenants!
}
```

**✅ Correção:**
```php
public function getTotalSales()
{
    [$idContador, $idEmpresa] = $this->getTenantIds();
    
    $db = \Config\Database::connect();
    
    $result = $db->table('pos_sales')
        ->selectSum('total')
        ->where('id_contador', $idContador)
        ->where('id_empresa', $idEmpresa)
        ->where('status', 'finalized')
        ->get()->getRowArray();
    
    return $result['total'] ?? 0;
}
```

---

### ❌ Anti-Pattern 3: Desabilitar Enforcement Sem Razão

```php
// ❌ PERIGO
class PosSaleModel extends BaseAppModel
{
    protected $enforceTenant = false; // Por quê???
}
```

**✅ Correção:**
```php
class PosSaleModel extends BaseAppModel
{
    // ✅ Manter ativado (padrão)
    protected $enforceTenant = true;
}
```

---

### ❌ Anti-Pattern 4: JOIN sem Filtro Duplo

```php
// ❌ PODE VAZAR DADOS
$rows = $db->table('pos_sales as s')
    ->join('clientes as c', 'c.id_cliente = s.id_cliente')
    ->where('s.id_empresa', $idEmpresa) // ✅ Filtra vendas
    // ❌ Mas não filtra clientes!
    ->get()->getResultArray();
```

**✅ Correção:**
```php
$rows = $db->table('pos_sales as s')
    ->join('clientes as c', 'c.id_cliente = s.id_cliente')
    ->where('s.id_contador', $idContador)
    ->where('s.id_empresa', $idEmpresa)
    ->where('c.id_contador', $idContador) // ✅ Filtrar também
    ->where('c.id_empresa', $idEmpresa)
    ->get()->getResultArray();
```

---

### ❌ Anti-Pattern 5: Cache Global

```php
// ❌ CACHE COMPARTILHADO ENTRE TENANTS
$cacheKey = 'produtos_lista';
$produtos = cache($cacheKey);

if (!$produtos) {
    $produtos = $produtoModel->findAll();
    cache()->save($cacheKey, $produtos, 3600);
}
```

**✅ Correção:**
```php
// ✅ Cache isolado por tenant
[$idContador, $idEmpresa] = $this->getTenantIds();

$cacheKey = "produtos_lista:{$idContador}:{$idEmpresa}";
$produtos = cache($cacheKey);

if (!$produtos) {
    $produtos = $produtoModel->findAll(); // BaseAppModel já filtra
    cache()->save($cacheKey, $produtos, 3600);
}
```

---

### ❌ Anti-Pattern 6: Logs sem Context

```php
// ❌ Impossível rastrear por tenant
log_message('info', 'Venda finalizada: ID=' . $idVenda);
```

**✅ Correção:**
```php
log_message('info', '[Venda] Finalizada', [
    'id_venda' => $idVenda,
    'tenant' => "{$idContador}:{$idEmpresa}",
    'valor' => $total,
    'usuario' => $idUsuario,
]);
```

---

## 📚 REFERÊNCIAS

### Documentos Relacionados
- [AUDITORIA_COMPLETA_PDV_MULTI_TENANT.md](./AUDITORIA_COMPLETA_PDV_MULTI_TENANT.md)
- [ROADMAP_IMPLEMENTACAO_PDV.md](./ROADMAP_IMPLEMENTACAO_PDV.md)
- [CHECKLIST_FINAL.md](./CHECKLIST_FINAL.md)

### Arquivos-Chave do Sistema
- `app/Models/BaseAppModel.php` - Isolamento automático
- `app/Filters/PdvAccessFilter.php` - Validação de acesso
- `app/Traits/TenantAwareTrait.php` - Helper de tenant (criar)
- `app/Config/Routes.php` - Aplicação de filtros

### Leitura Recomendada
- [CodeIgniter 4 - Query Builder](https://codeigniter.com/user_guide/database/query_builder.html)
- [Multi-Tenancy Patterns](https://docs.microsoft.com/en-us/azure/architecture/patterns/sharding)
- [OWASP - Broken Access Control](https://owasp.org/Top10/A01_2021-Broken_Access_Control/)

---

## ✅ CONCLUSÃO

### Princípios Imutáveis

1. **NUNCA confie no input do usuário para tenant IDs**
2. **SEMPRE filtre por `id_contador` E `id_empresa`**
3. **SEMPRE valide ownership antes de update/delete**
4. **SEMPRE use prepared statements em queries manuais**
5. **SEMPRE inclua tenant ID nos logs**
6. **SEMPRE escreva testes de isolamento**
7. **SEMPRE revise código com foco em segurança multi-tenant**

### Lembre-se

> **Um único vazamento de dados entre tenants pode destruir a confiança no sistema e resultar em perdas irreparáveis.**

**Segurança multi-tenant não é opcional — é OBRIGATÓRIA.**

---

**Versão:** 1.0  
**Última atualização:** 01/10/2025  
**Mantido por:** Time de Desenvolvimento xFiscal ERP

