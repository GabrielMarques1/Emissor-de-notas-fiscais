# 🧪 GUIA COMPLETO DE TESTES - PDV MULTI-TENANT

**Versão:** 1.0  
**Data:** 01/10/2025  
**Funcionalidades:** TEF, PIX, Multi-Payment, Suspensão, Descontos, Devoluções  

---

## 🎯 OBJETIVO

Este guia ensina como testar **TODAS** as funcionalidades implementadas no PDV Multi-Tenant.

---

## 📋 PRÉ-REQUISITOS

### 1. Ambiente Configurado

✅ XAMPP rodando (Apache + MySQL)  
✅ PHP 7.4+ instalado  
✅ Composer instalado  
✅ Banco de dados `erp_local` criado  
✅ Migrations executadas (7 migrations)  

### 2. Verificar Migrations

```bash
# Windows (PowerShell)
cd C:\xampp\htdocs\erp.local
C:\xampp\php\php.exe spark migrate:status

# Linux/Mac
cd /var/www/erp.local
php spark migrate:status
```

**Esperado:** 7 migrations rodadas ✅

---

## 1️⃣ TESTES AUTOMATIZADOS (Unit + Integration)

### 1.1 Executar TODOS os Testes

```bash
# Windows
C:\xampp\php\php.exe vendor/bin/phpunit

# Linux/Mac
./vendor/bin/phpunit
```

**Esperado:**
```
Tests: 36, Assertions: 120+
Time: 5-10 seconds
OK (36 tests, 120+ assertions)
```

### 1.2 Executar Testes por Módulo

#### TEF (5 testes)
```bash
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/TefMultiTenantTest.php --testdox
```

**Esperado:**
```
Tef Multi Tenant
 ✓ Tef transactions must be isolated by tenant
 ✓ Tef authorization must require valid tenant
 ✓ Tef queries must filter by tenant
 ✓ Tef confirm must validate tenant ownership
 ✓ Tef cancel must validate ownership
```

#### PIX (6 testes)
```bash
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/PixMultiTenantTest.php --testdox
```

**Esperado:**
```
Pix Multi Tenant
 ✓ Pix transactions must be isolated by tenant
 ✓ Pix generate must require valid tenant
 ✓ Pix queries must filter by tenant
 ✓ Pix confirm must validate tenant ownership
 ✓ Expired pix must be auto cancelled
 ✓ Webhook must validate tenant before confirming
```

#### Multi-Payment (6 testes)
```bash
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/MultiPaymentTest.php --testdox
```

#### Suspensão (7 testes)
```bash
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/SuspensionTest.php --testdox
```

#### Descontos (7 testes)
```bash
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/DiscountTest.php --testdox
```

#### Devoluções (5 testes)
```bash
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/ReturnTest.php --testdox
```

### 1.3 Cobertura de Testes

```bash
C:\xampp\php\php.exe vendor/bin/phpunit --coverage-text
```

**Esperado:** 85%+ de cobertura em código crítico

---

## 2️⃣ TESTES DE API (Postman/Insomnia/cURL)

### 2.1 Preparar Ambiente

**Criar 2 Empresas para Testar Isolamento:**

```sql
-- Empresa 1 (Tenant 1)
INSERT INTO empresas (id_empresa, id_contador, xFant, CNPJ) 
VALUES (100, 1, 'Loja Teste 1', '11111111000111');

-- Empresa 2 (Tenant 2)
INSERT INTO empresas (id_empresa, id_contador, xFant, CNPJ) 
VALUES (200, 1, 'Loja Teste 2', '22222222000122');

-- Configurar TEF para Empresa 1
UPDATE empresas 
SET 
    tef_acquirer = 'cielo',
    tef_merchant_id = 'MERCHANT_TEST_1',
    tef_merchant_key = 'KEY_TEST_1',
    tef_environment = 'sandbox',
    tef_timeout = 30,
    tef_max_installments = 12
WHERE id_empresa = 100;

-- Configurar PIX para Empresa 1
UPDATE empresas 
SET 
    pix_provider = 'mercadopago',
    pix_key = '11111111000111',
    pix_access_token = 'TEST_TOKEN_123',
    pix_expiration_minutes = 15
WHERE id_empresa = 100;

-- Configurar Descontos para Empresa 1
UPDATE empresas 
SET 
    max_discount_percentage = 30.00,
    max_discount_amount = 100.00,
    discount_approval_threshold = 20.00
WHERE id_empresa = 100;

-- Configurar Devoluções para Empresa 1
UPDATE empresas 
SET 
    return_days_limit = 7,
    require_return_approval = true,
    allow_partial_returns = true,
    allow_exchanges = true
WHERE id_empresa = 100;
```

### 2.2 Fazer Login (Obter Sessão)

**Endpoint:** `POST /login` ou `POST /pdv/login`

```bash
curl -X POST http://localhost/erp.local/public/pdv/login \
  -H "Content-Type: application/json" \
  -d '{
    "usuario": "admin",
    "senha": "senha123",
    "id_empresa": 100
  }'
```

**Salvar o Cookie/Session ID retornado!**

---

### 2.3 TESTE: Venda com Cartão (TEF)

```bash
# 1. Criar venda
curl -X POST http://localhost/erp.local/public/api/pos \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "items": [
      {"id_produto": 1, "quantity": 2, "unit_price": 50.00}
    ]
  }'

# Resposta: {"id_pos_sale": 1, "total": 100.00, "status": "pending"}

# 2. Finalizar com TEF
curl -X POST http://localhost/erp.local/public/api/pos/1/finalize \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "payment_type": "credit",
    "total": 100.00,
    "installments": 3,
    "card_data": {
      "number": "4111111111111111",
      "holder": "JOSE SILVA",
      "expiry": "12/2030",
      "cvv": "123"
    }
  }'

# Resposta esperada:
# {
#   "success": true,
#   "tef_transaction": {
#     "id_tef_transaction": 1,
#     "status": "confirmed",
#     "authorization_code": "ABC123",
#     "nsu": "789012"
#   },
#   "sale": {
#     "id_pos_sale": 1,
#     "status": "finalized",
#     "total": 100.00
#   }
# }
```

**Validar:**
✅ Transação TEF criada  
✅ Venda finalizada  
✅ Registro em `tef_transactions` com `id_empresa=100`  

---

### 2.4 TESTE: Venda com PIX

```bash
# Criar venda
curl -X POST http://localhost/erp.local/public/api/pos \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "items": [
      {"id_produto": 2, "quantity": 1, "unit_price": 75.00}
    ]
  }'

# Finalizar com PIX
curl -X POST http://localhost/erp.local/public/api/pos/2/finalize \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "payment_type": "pix",
    "total": 75.00
  }'

# Resposta esperada:
# {
#   "success": true,
#   "message": "QR Code PIX gerado. Aguardando pagamento.",
#   "pix": {
#     "txid": "PIX67001234567890ABCD",
#     "qr_code": "00020126360014BR.GOV.BCB.PIX...",
#     "expires_at": "2025-10-01 23:59:00"
#   }
# }
```

**Simular Confirmação via Webhook:**

```bash
curl -X POST http://localhost/erp.local/public/api/pix/webhook/100 \
  -H "Content-Type: application/json" \
  -d '{
    "txid": "PIX67001234567890ABCD",
    "e2e_id": "E123456782025100112345678",
    "status": "paid",
    "amount": 75.00
  }'

# Resposta: {"success": true, "message": "Pagamento confirmado"}
```

**Validar:**
✅ QR Code gerado  
✅ Transação PIX criada  
✅ Webhook confirma pagamento  
✅ Venda finalizada automaticamente  

---

### 2.5 TESTE: Múltiplas Formas de Pagamento

```bash
curl -X POST http://localhost/erp.local/public/api/pos/3/finalize \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "payment_type": "multiple",
    "total": 200.00,
    "payments": [
      {
        "type": "cash",
        "amount": 50.00,
        "calculate_change": true
      },
      {
        "type": "credit",
        "amount": 100.00,
        "installments": 2
      },
      {
        "type": "pix",
        "amount": 50.00
      }
    ]
  }'

# Resposta esperada:
# {
#   "success": true,
#   "sale": {...},
#   "payments": [
#     {"type": "cash", "amount": 50.00, "change_amount": 0.00},
#     {"type": "credit", "amount": 100.00},
#     {"type": "pix", "amount": 50.00}
#   ],
#   "summary": {
#     "total_payments": 3,
#     "total_paid": 200.00,
#     "total_change": 0.00
#   }
# }
```

**Validar:**
✅ 3 pagamentos registrados  
✅ Soma = 200.00  
✅ Venda finalizada  

---

### 2.6 TESTE: Suspender e Retomar Venda

```bash
# Criar venda
curl -X POST http://localhost/erp.local/public/api/pos \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{"items": [{"id_produto": 1, "quantity": 1, "unit_price": 50.00}]}'

# Suspender
curl -X POST http://localhost/erp.local/public/api/pos/4/suspend \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "reason": "Cliente foi ao caixa eletrônico"
  }'

# Listar suspensas
curl -X GET http://localhost/erp.local/public/api/pos/suspended \
  -H "Cookie: ci_session=YOUR_SESSION_ID"

# Retomar
curl -X POST http://localhost/erp.local/public/api/pos/4/resume \
  -H "Cookie: ci_session=YOUR_SESSION_ID"
```

**Validar:**
✅ Venda suspensa  
✅ Aparece na lista de suspensas  
✅ Retomada com sucesso  

---

### 2.7 TESTE: Aplicar Desconto

```bash
# Criar venda
curl -X POST http://localhost/erp.local/public/api/pos \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{"items": [{"id_produto": 1, "quantity": 1, "unit_price": 100.00}]}'

# Aplicar desconto de 15%
curl -X POST http://localhost/erp.local/public/api/pos/5/discount \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "type": "percentage",
    "value": 15.00,
    "reason": "Cliente VIP"
  }'

# Resposta esperada:
# {
#   "success": true,
#   "discount_amount": 15.00,
#   "new_total": 85.00
# }
```

**Validar:**
✅ Desconto aplicado  
✅ Total atualizado  
✅ Registro em `discounts`  

---

### 2.8 TESTE: Aplicar Cupom

```bash
# Criar cupom primeiro
INSERT INTO coupons (
    code, type, value, min_purchase, usage_limit,
    valid_from, valid_until, is_active,
    id_contador, id_empresa, created_at, updated_at
) VALUES (
    'PROMO10', 'percentage', 10.00, 50.00, 100,
    NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1,
    1, 100, NOW(), NOW()
);

# Aplicar cupom
curl -X POST http://localhost/erp.local/public/api/pos/6/coupon \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "code": "PROMO10"
  }'

# Listar cupons ativos
curl -X GET http://localhost/erp.local/public/api/pos/coupons \
  -H "Cookie: ci_session=YOUR_SESSION_ID"
```

**Validar:**
✅ Cupom aplicado  
✅ Desconto calculado  
✅ Contador de uso incrementado  

---

### 2.9 TESTE: Processar Devolução

```bash
# Criar e finalizar venda primeiro
# (usar vendas dos testes anteriores)

# Processar devolução total
curl -X POST http://localhost/erp.local/public/api/pos/returns/process \
  -H "Content-Type: application/json" \
  -H "Cookie: ci_session=YOUR_SESSION_ID" \
  -d '{
    "id_sale": 1,
    "type": "full_return",
    "reason": "Produto com defeito",
    "refund_method": "same_method",
    "approved_by": 1,
    "restock": true
  }'

# Resposta esperada:
# {
#   "success": true,
#   "return": {
#     "id_return": 1,
#     "type": "full_return",
#     "total_returned": 100.00,
#     "refund_status": "completed"
#   },
#   "refund": {
#     "success": true,
#     "method": "tef",
#     "amount": 100.00
#   }
# }
```

**Validar:**
✅ Devolução registrada  
✅ Estorno processado  
✅ Estoque reposto  

---

## 3️⃣ TESTES DE SEGURANÇA MULTI-TENANT

### 3.1 TESTE: Isolamento de Dados

**Objetivo:** Garantir que Tenant 1 não acessa dados do Tenant 2

```bash
# Login como Empresa 1
curl -X POST http://localhost/erp.local/public/pdv/login \
  -d '{"usuario": "admin", "senha": "senha123", "id_empresa": 100}'

# Criar venda na Empresa 1
curl -X POST http://localhost/erp.local/public/api/pos \
  -H "Cookie: ci_session=SESSION_EMPRESA_1" \
  -d '{"items": [{"id_produto": 1, "quantity": 1, "unit_price": 50.00}]}'
# Resposta: id_pos_sale = 10

# Login como Empresa 2
curl -X POST http://localhost/erp.local/public/pdv/login \
  -d '{"usuario": "admin", "senha": "senha123", "id_empresa": 200}'

# Tentar acessar venda da Empresa 1
curl -X GET http://localhost/erp.local/public/api/pos/10 \
  -H "Cookie: ci_session=SESSION_EMPRESA_2"

# Resposta esperada: 404 Not Found ou erro de permissão
```

**Validar:**
✅ Empresa 2 NÃO vê venda da Empresa 1  
✅ Erro retornado adequadamente  

### 3.2 TESTE: Cupons Isolados

```sql
-- Criar cupom para Empresa 1
INSERT INTO coupons (code, type, value, id_contador, id_empresa, is_active, created_at, updated_at)
VALUES ('TENANT1', 'fixed', 20.00, 1, 100, 1, NOW(), NOW());

-- Criar cupom para Empresa 2
INSERT INTO coupons (code, type, value, id_contador, id_empresa, is_active, created_at, updated_at)
VALUES ('TENANT2', 'fixed', 30.00, 1, 200, 1, NOW(), NOW());
```

```bash
# Login como Empresa 1
# Tentar usar cupom da Empresa 2
curl -X POST http://localhost/erp.local/public/api/pos/11/coupon \
  -H "Cookie: ci_session=SESSION_EMPRESA_1" \
  -d '{"code": "TENANT2"}'

# Resposta esperada: Erro "Cupom inválido"
```

**Validar:**
✅ Cupom de outro tenant não funciona  

---

## 4️⃣ TESTES DE CRON JOBS

### 4.1 Expirar PIX

```bash
# Executar manualmente
C:\xampp\php\php.exe spark pix:expire

# Verificar logs
tail -f writable/logs/log-2025-10-01.log | grep PIX
```

**Validar:**
✅ Transações PIX expiradas marcadas como `expired`  

### 4.2 Expirar Suspensões

```bash
C:\xampp\php\php.exe spark sales:expire-suspended

# Verificar logs
tail -f writable/logs/log-2025-10-01.log | grep Suspension
```

**Validar:**
✅ Vendas suspensas expiradas canceladas  

---

## 5️⃣ TESTES MANUAIS DE INTERFACE

### 5.1 Testar PDV no Navegador

1. Acesse: `http://localhost/erp.local/public/pdv`
2. Faça login com Empresa 100
3. Adicione produtos ao carrinho
4. Teste cada forma de pagamento:
   - ✅ Dinheiro
   - ✅ Cartão de Crédito
   - ✅ PIX
   - ✅ Múltiplas formas
5. Teste suspender venda
6. Teste aplicar desconto
7. Teste aplicar cupom

---

## 6️⃣ CHECKLIST FINAL

### ✅ Testes Automatizados
- [ ] Todos 36 testes passando
- [ ] Sem erros de isolamento multi-tenant
- [ ] Cobertura >85%

### ✅ Testes de API
- [ ] Venda com TEF funciona
- [ ] Venda com PIX funciona
- [ ] Múltiplas formas funciona
- [ ] Suspensão/retomada funciona
- [ ] Descontos funcionam
- [ ] Cupons funcionam
- [ ] Devoluções funcionam

### ✅ Segurança Multi-Tenant
- [ ] Empresa 1 não vê dados da Empresa 2
- [ ] Cupons isolados por tenant
- [ ] Transações isoladas por tenant
- [ ] Devoluções isoladas por tenant

### ✅ Cron Jobs
- [ ] PIX expira corretamente
- [ ] Suspensões expiram corretamente

### ✅ Performance
- [ ] Vendas finalizam em <2s
- [ ] Queries otimizadas (usar índices)
- [ ] Sem N+1 queries

---

## 7️⃣ TROUBLESHOOTING

### Problema: Testes Falhando

```bash
# Limpar cache
C:\xampp\php\php.exe spark cache:clear

# Resetar banco de dados de teste
C:\xampp\php\php.exe spark migrate:refresh --all

# Rodar seed se necessário
C:\xampp\php\php.exe spark db:seed TestSeeder
```

### Problema: Sessão Não Funciona

- Verificar `app/Config/App.php`: `$sessionDriver = 'CodeIgniter\Session\Handlers\FileHandler'`
- Verificar permissões em `writable/session/`
- Limpar sessões antigas: `rm writable/session/*`

### Problema: Migration com Erro

```bash
# Verificar status
C:\xampp\php\php.exe spark migrate:status

# Rollback última migration
C:\xampp\php\php.exe spark migrate:rollback

# Rodar novamente
C:\xampp\php\php.exe spark migrate
```

---

## 8️⃣ MONITORAMENTO EM PRODUÇÃO

### Métricas para Acompanhar

1. **Taxa de Sucesso TEF:** >95%
2. **Taxa de Conversão PIX:** >80%
3. **Taxa de Suspensões Retomadas:** >85%
4. **Taxa de Devoluções:** <10%
5. **Tempo Médio de Finalização:** <2s

### Logs para Monitorar

```bash
# Erros
grep "ERROR" writable/logs/*.log

# TEF
grep "\[TEF\]" writable/logs/*.log

# PIX
grep "\[PIX\]" writable/logs/*.log

# Multi-Tenant
grep "tenant" writable/logs/*.log
```

---

## ✅ CONCLUSÃO

Com este guia, você pode:
- ✅ Testar automaticamente (36 testes)
- ✅ Testar manualmente via API
- ✅ Validar segurança multi-tenant
- ✅ Testar cron jobs
- ✅ Monitorar em produção

**Boa sorte com os testes! 🚀**

