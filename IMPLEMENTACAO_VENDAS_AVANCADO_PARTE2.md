# 🛒 IMPLEMENTAÇÃO - VENDAS AVANÇADO (PARTE 2)

**Complemento de:** IMPLEMENTACAO_VENDAS_AVANCADO.md  
**Conteúdo:** Descontos completos + Devoluções completas  

---

## 1.3 DESCONTOS E PROMOÇÕES (CONTINUAÇÃO)

> ⚠️ **NOTA:** As Migrations já estão no arquivo principal. Aqui começamos dos TESTES.

### 🧪 TESTES - DESCONTOS

```php
<?php
// tests/multitenant/DiscountTest.php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\DiscountService;
use App\Models\DiscountCouponModel;
use App\Models\PosSaleModel;

class DiscountTest extends MultiTenantTestCase
{
    protected DiscountService $discountService;
    protected DiscountCouponModel $couponModel;
    protected PosSaleModel $saleModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->discountService = new DiscountService();
        $this->couponModel = new DiscountCouponModel();
        $this->saleModel = new PosSaleModel();
    }
    
    /**
     * @test
     * REGRA: Cupons devem ser isolados por tenant
     */
    public function coupons_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar cupom para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $idCoupon1 = $this->couponModel->insert([
            'code' => 'PROMO20',
            'description' => 'Desconto 20%',
            'type' => 'percentage',
            'value' => 20.00,
            'valid_from' => date('Y-m-d H:i:s'),
            'valid_until' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'is_active' => 1,
        ]);
        
        // ACT: Tentar usar cupom com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $coupon = $this->couponModel->where('code', 'PROMO20')->first();
        
        // ASSERT: Não deve encontrar cupom de outro tenant
        $this->assertNull($coupon, 'Não deve ver cupom de outro tenant');
    }
    
    /**
     * @test
     * REGRA: Operador só pode dar desconto até limite configurado
     */
    public function operator_discount_must_respect_limit(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Simular usuário operador (tipo = 1, sem perfil gerente)
        $_SESSION['tipo'] = 1;
        $_SESSION['perfil'] = 'operador';
        
        // ARRANGE: Criar venda
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SALE-DISCOUNT-001',
            'total' => 1000.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Tentar aplicar desconto de 10% (acima do limite de 5%)
        $result = $this->discountService->applyDiscount(
            idSale: $idSale,
            discountType: 'percentage',
            discountValue: 10.00,
            reason: 'Teste'
        );
        
        // ASSERT: Deve exigir autorização
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('autorização', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Gerente pode autorizar desconto até limite dele
     */
    public function manager_can_authorize_higher_discount(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // Simular usuário gerente
        $_SESSION['tipo'] = 1;
        $_SESSION['perfil'] = 'gerente';
        $_SESSION['id_usuario'] = 100;
        
        // ARRANGE
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SALE-DISCOUNT-002',
            'total' => 1000.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Aplicar desconto de 15% (gerente pode até 20%)
        $result = $this->discountService->applyDiscount(
            idSale: $idSale,
            discountType: 'percentage',
            discountValue: 15.00,
            reason: 'Cliente especial',
            authorizedBy: 100
        );
        
        // ASSERT: Deve funcionar
        $this->assertTrue($result['success']);
        $this->assertEquals(150.00, $result['discount_amount']); // 15% de 1000
    }
    
    /**
     * @test
     * REGRA: Cupom deve estar válido (data, ativo, limite de uso)
     */
    public function coupon_must_be_valid_to_use(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // ARRANGE: Criar cupom expirado
        $idCoupon = $this->couponModel->insert([
            'code' => 'EXPIRED',
            'description' => 'Cupom expirado',
            'type' => 'percentage',
            'value' => 10.00,
            'valid_from' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'valid_until' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'is_active' => 1,
        ]);
        
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SALE-COUPON-001',
            'total' => 500.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Tentar usar cupom expirado
        $result = $this->discountService->applyCoupon($idSale, 'EXPIRED');
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expirado', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Auditoria deve registrar todos descontos aplicados
     */
    public function all_discounts_must_be_audited(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $_SESSION['id_usuario'] = 1;
        $_SESSION['perfil'] = 'gerente';
        
        // ARRANGE
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SALE-AUDIT-001',
            'total' => 300.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Aplicar desconto
        $result = $this->discountService->applyDiscount(
            idSale: $idSale,
            discountType: 'fixed_value',
            discountValue: 50.00,
            reason: 'Cliente VIP',
            authorizedBy: 1
        );
        
        $this->assertTrue($result['success']);
        
        // ASSERT: Deve existir log de auditoria
        $db = \Config\Database::connect();
        $logs = $db->table('discount_audit_logs')
                   ->where('id_pos_sale', $idSale)
                   ->where('id_contador', $this->tenant1Contador)
                   ->where('id_empresa', $this->tenant1Empresa)
                   ->get()
                   ->getResultArray();
        
        $this->assertCount(1, $logs);
        $this->assertEquals(50.00, $logs[0]['discount_amount']);
        $this->assertEquals('Cliente VIP', $logs[0]['reason']);
    }
}
```

### 📦 MODELS - DESCONTOS

```php
<?php
// app/Models/DiscountCouponModel.php
namespace App\Models;

class DiscountCouponModel extends BaseAppModel
{
    protected $table = 'discount_coupons';
    protected $primaryKey = 'id_coupon';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'code', 'description', 'type', 'value', 'min_purchase', 'max_discount',
        'usage_limit', 'usage_count', 'usage_per_customer', 'valid_from',
        'valid_until', 'is_active', 'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'code' => 'required|min_length[3]|max_length[50]',
        'type' => 'required|in_list[percentage,fixed_value]',
        'value' => 'required|decimal|greater_than[0]',
        'valid_from' => 'required|valid_date',
        'valid_until' => 'required|valid_date',
    ];
    
    /**
     * Buscar cupom por código (validando tenant)
     */
    public function findByCode(string $code): ?array
    {
        return $this->where('code', strtoupper($code))
                    ->where('is_active', 1)
                    ->first();
    }
    
    /**
     * Validar se cupom está válido
     */
    public function isValid(array $coupon, float $saleTotal = 0): array
    {
        $now = date('Y-m-d H:i:s');
        
        // Verificar data de validade
        if ($now < $coupon['valid_from']) {
            return ['valid' => false, 'error' => 'Cupom ainda não está válido'];
        }
        
        if ($now > $coupon['valid_until']) {
            return ['valid' => false, 'error' => 'Cupom expirado'];
        }
        
        // Verificar se está ativo
        if (!$coupon['is_active']) {
            return ['valid' => false, 'error' => 'Cupom desativado'];
        }
        
        // Verificar limite de uso
        if ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']) {
            return ['valid' => false, 'error' => 'Limite de uso do cupom atingido'];
        }
        
        // Verificar valor mínimo de compra
        if ($coupon['min_purchase'] && $saleTotal < $coupon['min_purchase']) {
            return [
                'valid' => false,
                'error' => "Valor mínimo de compra: R$ {$coupon['min_purchase']}"
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Incrementar contador de uso
     */
    public function incrementUsage(int $idCoupon): bool
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table)
                  ->where('id_coupon', $idCoupon)
                  ->set('usage_count', 'usage_count + 1', false)
                  ->update();
    }
}
```

```php
<?php
// app/Models/DiscountAuditLogModel.php
namespace App\Models;

class DiscountAuditLogModel extends BaseAppModel
{
    protected $table = 'discount_audit_logs';
    protected $primaryKey = 'id_discount_log';
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'id_pos_sale', 'id_pos_sale_item', 'id_coupon', 'discount_type',
        'discount_value', 'discount_amount', 'original_amount', 'final_amount',
        'applied_by', 'authorized_by', 'reason', 'requires_authorization',
        'id_contador', 'id_empresa',
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    
    /**
     * Buscar logs de desconto de uma venda
     */
    public function getBySale(int $idSale): array
    {
        return $this->where('id_pos_sale', $idSale)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Relatório de descontos por período
     */
    public function getReportByPeriod(string $dateFrom, string $dateTo): array
    {
        return $this->where('created_at >=', $dateFrom)
                    ->where('created_at <=', $dateTo)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}
```

### 📦 SERVICE - DESCONTOS

```php
<?php
// app/Libraries/DiscountService.php
namespace App\Libraries;

use App\Models\PosSaleModel;
use App\Models\DiscountCouponModel;
use App\Models\DiscountAuditLogModel;
use App\Traits\TenantAwareTrait;

class DiscountService
{
    use TenantAwareTrait;
    
    protected PosSaleModel $saleModel;
    protected DiscountCouponModel $couponModel;
    protected DiscountAuditLogModel $auditModel;
    
    /**
     * Limites de desconto por perfil (carregados da empresa)
     */
    protected float $operatorLimit = 5.00;
    protected float $managerLimit = 20.00;
    protected bool $requiresReason = true;
    
    public function __construct()
    {
        $this->saleModel = new PosSaleModel();
        $this->couponModel = new DiscountCouponModel();
        $this->auditModel = new DiscountAuditLogModel();
        
        $this->loadTenantConfig();
    }
    
    /**
     * Carregar configurações de desconto do tenant
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
            $this->operatorLimit = (float) ($config['discount_operator_limit'] ?? 5.00);
            $this->managerLimit = (float) ($config['discount_manager_limit'] ?? 20.00);
            $this->requiresReason = (bool) ($config['discount_requires_reason'] ?? true);
        }
    }
    
    /**
     * Aplicar desconto manual (percentual ou valor fixo)
     */
    public function applyDiscount(
        int $idSale,
        string $discountType,
        float $discountValue,
        ?string $reason = null,
        ?int $authorizedBy = null
    ): array {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Validar venda
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale || !$this->validateTenantOwnership($sale, $idContador, $idEmpresa)) {
            return ['success' => false, 'error' => 'Venda não encontrada'];
        }
        
        if ($sale['status'] !== 'draft') {
            return ['success' => false, 'error' => 'Apenas vendas em rascunho podem receber desconto'];
        }
        
        // Validar motivo
        if ($this->requiresReason && empty($reason)) {
            return ['success' => false, 'error' => 'Motivo é obrigatório'];
        }
        
        // Verificar permissões
        $session = session();
        $perfil = $session->get('perfil') ?? 'operador';
        $idUsuario = (int) ($session->get('id_usuario') ?? $session->get('id_login') ?? 0);
        
        $requiresAuth = false;
        
        if ($discountType === 'percentage') {
            if ($perfil === 'operador' && $discountValue > $this->operatorLimit) {
                $requiresAuth = true;
            }
            
            if ($perfil === 'gerente' && $discountValue > $this->managerLimit) {
                return [
                    'success' => false,
                    'error' => "Desconto de {$discountValue}% excede limite de gerente ({$this->managerLimit}%)"
                ];
            }
            
            if ($requiresAuth && !$authorizedBy) {
                return [
                    'success' => false,
                    'error' => "Desconto de {$discountValue}% requer autorização de gerente (limite: {$this->operatorLimit}%)",
                    'requires_authorization' => true,
                ];
            }
        }
        
        // Calcular desconto
        $originalAmount = (float) $sale['total'];
        $discountAmount = 0.00;
        
        if ($discountType === 'percentage') {
            $discountAmount = ($originalAmount * $discountValue) / 100;
        } else {
            $discountAmount = $discountValue;
        }
        
        $finalAmount = $originalAmount - $discountAmount;
        
        if ($finalAmount < 0) {
            return ['success' => false, 'error' => 'Desconto não pode ser maior que o total'];
        }
        
        // Atualizar venda
        $this->saleModel->update($idSale, [
            'discount' => $discountAmount,
            'total' => $finalAmount,
        ]);
        
        // Registrar auditoria
        $this->auditModel->insert([
            'id_pos_sale' => $idSale,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => $finalAmount,
            'applied_by' => $idUsuario,
            'authorized_by' => $authorizedBy,
            'reason' => $reason,
            'requires_authorization' => $requiresAuth ? 1 : 0,
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
        ]);
        
        log_message('info', '[Discount] Desconto aplicado', [
            'id_sale' => $idSale,
            'type' => $discountType,
            'value' => $discountValue,
            'amount' => $discountAmount,
            'requires_auth' => $requiresAuth,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return [
            'success' => true,
            'message' => 'Desconto aplicado com sucesso',
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }
    
    /**
     * Aplicar cupom de desconto
     */
    public function applyCoupon(int $idSale, string $couponCode): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        // Buscar cupom
        $coupon = $this->couponModel->findByCode($couponCode);
        
        if (!$coupon) {
            return ['success' => false, 'error' => 'Cupom não encontrado'];
        }
        
        // Buscar venda
        $sale = $this->saleModel->find($idSale);
        
        if (!$sale) {
            return ['success' => false, 'error' => 'Venda não encontrada'];
        }
        
        // Validar cupom
        $validation = $this->couponModel->isValid($coupon, (float) $sale['total']);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        // Calcular desconto
        $originalAmount = (float) $sale['total'];
        $discountAmount = 0.00;
        
        if ($coupon['type'] === 'percentage') {
            $discountAmount = ($originalAmount * $coupon['value']) / 100;
            
            // Aplicar limite máximo se configurado
            if ($coupon['max_discount'] && $discountAmount > $coupon['max_discount']) {
                $discountAmount = (float) $coupon['max_discount'];
            }
        } else {
            $discountAmount = (float) $coupon['value'];
        }
        
        $finalAmount = $originalAmount - $discountAmount;
        
        // Atualizar venda
        $this->saleModel->update($idSale, [
            'discount' => $discountAmount,
            'total' => $finalAmount,
        ]);
        
        // Incrementar uso do cupom
        $this->couponModel->incrementUsage($coupon['id_coupon']);
        
        // Registrar auditoria
        $session = session();
        $idUsuario = (int) ($session->get('id_usuario') ?? $session->get('id_login') ?? 0);
        
        $this->auditModel->insert([
            'id_pos_sale' => $idSale,
            'id_coupon' => $coupon['id_coupon'],
            'discount_type' => 'coupon',
            'discount_value' => $coupon['value'],
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => $finalAmount,
            'applied_by' => $idUsuario,
            'reason' => "Cupom: {$couponCode}",
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
        ]);
        
        log_message('info', '[Discount] Cupom aplicado', [
            'id_sale' => $idSale,
            'coupon' => $couponCode,
            'amount' => $discountAmount,
            'tenant' => "{$idContador}:{$idEmpresa}",
        ]);
        
        return [
            'success' => true,
            'message' => 'Cupom aplicado com sucesso',
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }
}
```

### 🎮 CONTROLLER - DESCONTOS

```php
<?php
// app/Controllers/Api/Discounts.php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\DiscountService;
use App\Models\DiscountCouponModel;
use CodeIgniter\HTTP\ResponseInterface;

class Discounts extends BaseController
{
    /**
     * Aplicar desconto manual
     * POST /api/discounts/apply
     */
    public function apply(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            $idSale = (int) ($data['id_sale'] ?? 0);
            $type = $data['type'] ?? ''; // percentage, fixed_value
            $value = (float) ($data['value'] ?? 0);
            $reason = $data['reason'] ?? null;
            $authorizedBy = isset($data['authorized_by']) ? (int) $data['authorized_by'] : null;
            
            if (!$idSale || !$type || !$value) {
                return $this->respond([
                    'success' => false,
                    'error' => 'Parâmetros inválidos',
                ], 400);
            }
            
            $service = new DiscountService();
            $result = $service->applyDiscount($idSale, $type, $value, $reason, $authorizedBy);
            
            return $this->respond($result, $result['success'] ? 200 : 400);
            
        } catch (\Exception $e) {
            log_message('error', '[Discounts::apply] Erro: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Aplicar cupom de desconto
     * POST /api/discounts/coupon
     */
    public function applyCoupon(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            $idSale = (int) ($data['id_sale'] ?? 0);
            $couponCode = $data['coupon_code'] ?? '';
            
            if (!$idSale || !$couponCode) {
                return $this->respond([
                    'success' => false,
                    'error' => 'Parâmetros inválidos',
                ], 400);
            }
            
            $service = new DiscountService();
            $result = $service->applyCoupon($idSale, $couponCode);
            
            return $this->respond($result, $result['success'] ? 200 : 400);
            
        } catch (\Exception $e) {
            log_message('error', '[Discounts::applyCoupon] Erro: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Listar cupons ativos
     * GET /api/discounts/coupons
     */
    public function listCoupons(): ResponseInterface
    {
        try {
            $couponModel = new DiscountCouponModel();
            
            $coupons = $couponModel->where('is_active', 1)
                                   ->where('valid_until >=', date('Y-m-d H:i:s'))
                                   ->orderBy('created_at', 'DESC')
                                   ->findAll();
            
            return $this->respond([
                'success' => true,
                'data' => $coupons,
                'count' => count($coupons),
            ], 200);
            
        } catch (\Exception $e) {
            log_message('error', '[Discounts::listCoupons] Erro: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

---

## 1.4 DEVOLUÇÕES E TROCAS (COMPLETO)

### 📊 Análise

**Objetivo:** Sistema completo de devoluções (total/parcial) e trocas de produtos.

**Casos de Uso:**
1. Cliente quer devolver produto (defeito, arrependimento)
2. Troca de produto (tamanho errado, cor errada)
3. Estorno de pagamento (dinheiro, cartão, PIX)
4. Reposição de estoque
5. Emissão de NF-e de devolução

**Regras de Negócio:**
- ✅ Apenas vendas finalizadas podem ser devolvidas
- ✅ Prazo configurável (padrão: 7 dias)
- ✅ Devolução total ou parcial (por item)
- ✅ Estorno proporcional aos pagamentos
- ✅ Reposição automática de estoque
- ✅ Motivo obrigatório
- ✅ Autorização de gerente/admin
- ✅ NF-e de devolução (se nota foi emitida)

### 🗄️ MIGRATION - DEVOLUÇÕES

> ⚠️ **NOTA:** Esta migration já está no arquivo principal (IMPLEMENTACAO_VENDAS_AVANCADO.md). Não duplicar!

### 🧪 TESTES - DEVOLUÇÕES

```php
<?php
// tests/multitenant/ReturnsTest.php
namespace Tests\MultiTenant;

use Tests\Support\MultiTenantTestCase;
use App\Libraries\ReturnService;
use App\Models\PosSaleModel;
use App\Models\ReturnModel;

class ReturnsTest extends MultiTenantTestCase
{
    protected ReturnService $returnService;
    protected PosSaleModel $saleModel;
    protected ReturnModel $returnModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->returnService = new ReturnService();
        $this->saleModel = new PosSaleModel();
        $this->returnModel = new ReturnModel();
    }
    
    /**
     * @test
     * REGRA: Devoluções devem ser isoladas por tenant
     */
    public function returns_must_be_isolated_by_tenant(): void
    {
        // ARRANGE: Criar venda e devolução para tenant 1
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $idSale1 = $this->saleModel->insert([
            'sale_number' => 'SALE-RET-001',
            'total' => 500.00,
            'status' => 'finalized',
            'finalized_at' => date('Y-m-d H:i:s'),
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $result = $this->returnService->createReturn(
            idSale: $idSale1,
            returnType: 'full',
            reason: 'Produto com defeito',
            items: []
        );
        
        $this->assertTrue($result['success']);
        $idReturn = $result['return']['id_return'];
        
        // ACT: Tentar buscar devolução com tenant 2
        $this->actingAsTenant($this->tenant2Contador, $this->tenant2Empresa);
        
        $return = $this->returnModel->find($idReturn);
        
        // ASSERT: Não deve ver devolução de outro tenant
        $this->assertNull($return);
    }
    
    /**
     * @test
     * REGRA: Apenas vendas finalizadas podem ser devolvidas
     */
    public function only_finalized_sales_can_be_returned(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // ARRANGE: Criar venda em rascunho
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SALE-DRAFT',
            'total' => 200.00,
            'status' => 'draft',
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Tentar devolver
        $result = $this->returnService->createReturn($idSale, 'full', 'Teste', []);
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('finalizada', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Respeitar prazo de devolução (7 dias padrão)
     */
    public function must_respect_return_deadline(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        // ARRANGE: Criar venda antiga (>7 dias)
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SALE-OLD',
            'total' => 300.00,
            'status' => 'finalized',
            'finalized_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        // ACT: Tentar devolver
        $result = $this->returnService->createReturn($idSale, 'full', 'Fora do prazo', []);
        
        // ASSERT: Deve falhar
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('prazo', $result['error']);
    }
    
    /**
     * @test
     * REGRA: Estoque deve ser reposto ao completar devolução
     */
    public function stock_must_be_restocked_on_return(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $db = \Config\Database::connect();
        
        // ARRANGE: Produto com estoque 10
        $db->table('produtos')->insert([
            'id_produto' => 999,
            'descricao' => 'Produto Teste Devolução',
            'estoque' => 10,
            'valor_venda' => 50.00,
            'id_contador' => $this->tenant1Contador,
            'id_empresa' => $this->tenant1Empresa,
        ]);
        
        // Criar venda que baixou 3 unidades
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SALE-RESTOCK',
            'total' => 150.00,
            'status' => 'finalized',
            'finalized_at' => date('Y-m-d H:i:s'),
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $idItem = $db->table('pos_sale_items')->insert([
            'id_pos_sale' => $idSale,
            'id_produto' => 999,
            'descricao' => 'Produto Teste',
            'quantidade' => 3,
            'valor_unitario' => 50.00,
            'subtotal' => 150.00,
        ]);
        
        // Baixar estoque manualmente (simular venda)
        $db->table('produtos')
           ->where('id_produto', 999)
           ->set('estoque', 'estoque - 3', false)
           ->update();
        
        // Estoque agora: 7
        
        // ACT: Fazer devolução completa
        $_SESSION['perfil'] = 'gerente';
        $_SESSION['id_usuario'] = 1;
        
        $result = $this->returnService->createReturn($idSale, 'full', 'Defeito', []);
        $this->assertTrue($result['success']);
        
        // Aprovar e completar
        $this->returnService->approveReturn($result['return']['id_return'], 1);
        $this->returnService->completeReturn($result['return']['id_return']);
        
        // ASSERT: Estoque deve voltar para 10
        $produto = $db->table('produtos')->where('id_produto', 999)->get()->getRowArray();
        $this->assertEquals(10, $produto['estoque']);
    }
    
    /**
     * @test
     * REGRA: Devolução parcial deve repor apenas itens devolvidos
     */
    public function partial_return_restocks_only_returned_items(): void
    {
        $this->actingAsTenant($this->tenant1Contador, $this->tenant1Empresa);
        
        $db = \Config\Database::connect();
        
        // ARRANGE: 2 produtos
        $db->table('produtos')->insertBatch([
            [
                'id_produto' => 100,
                'descricao' => 'Produto A',
                'estoque' => 50,
                'valor_venda' => 100.00,
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
            ],
            [
                'id_produto' => 101,
                'descricao' => 'Produto B',
                'estoque' => 30,
                'valor_venda' => 75.00,
                'id_contador' => $this->tenant1Contador,
                'id_empresa' => $this->tenant1Empresa,
            ],
        ]);
        
        // Venda com 2 itens
        $idSale = $this->saleModel->insert([
            'sale_number' => 'SALE-PARTIAL',
            'total' => 350.00,
            'status' => 'finalized',
            'finalized_at' => date('Y-m-d H:i:s'),
            'id_shift' => 1,
            'id_cash_register' => 1,
        ]);
        
        $db->table('pos_sale_items')->insertBatch([
            [
                'id_pos_sale_item' => 1000,
                'id_pos_sale' => $idSale,
                'id_produto' => 100,
                'descricao' => 'Produto A',
                'quantidade' => 2,
                'valor_unitario' => 100.00,
                'subtotal' => 200.00,
            ],
            [
                'id_pos_sale_item' => 1001,
                'id_pos_sale' => $idSale,
                'id_produto' => 101,
                'descricao' => 'Produto B',
                'quantidade' => 2,
                'valor_unitario' => 75.00,
                'subtotal' => 150.00,
            ],
        ]);
        
        // Baixar estoques
        $db->table('produtos')->where('id_produto', 100)->set('estoque', 48, false)->update(); // 50 - 2 = 48
        $db->table('produtos')->where('id_produto', 101)->set('estoque', 28, false)->update(); // 30 - 2 = 28
        
        // ACT: Devolver apenas Produto A (2 unidades)
        $_SESSION['perfil'] = 'gerente';
        $_SESSION['id_usuario'] = 1;
        
        $result = $this->returnService->createReturn(
            idSale: $idSale,
            returnType: 'partial',
            reason: 'Defeito no Produto A',
            items: [
                ['id_item' => 1000, 'quantidade' => 2],
            ]
        );
        
        $this->assertTrue($result['success']);
        
        // Completar
        $this->returnService->approveReturn($result['return']['id_return'], 1);
        $this->returnService->completeReturn($result['return']['id_return']);
        
        // ASSERT: Produto A volta para 50, Produto B fica em 28
        $produtoA = $db->table('produtos')->where('id_produto', 100)->get()->getRowArray();
        $produtoB = $db->table('produtos')->where('id_produto', 101)->get()->getRowArray();
        
        $this->assertEquals(50, $produtoA['estoque']); // Reposto
        $this->assertEquals(28, $produtoB['estoque']); // Não alterado
    }
}
```

Devido ao limite de espaço, vou criar um **terceiro arquivo** com o restante (Service e Controller de Devoluções). Continuo?

**Status Atual:**
- ✅ 1.1 Suspensão (COMPLETO - arquivo principal)
- ✅ 1.2 Multi-Payment (COMPLETO - arquivo principal)
- ✅ 1.3 Descontos (COMPLETO - este arquivo)
- 🟡 1.4 Devoluções (Migrations + Testes OK, faltam Service + Controller)

Quer que eu complete agora ou já está suficiente? 🚀

