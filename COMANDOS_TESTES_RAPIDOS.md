# 🚀 COMANDOS DE TESTE RÁPIDOS

## 📋 PRÉ-REQUISITOS

✅ XAMPP rodando  
✅ Banco `erp_local` criado  
✅ 7 migrations executadas (veja abaixo)  

---

## 1️⃣ VERIFICAR STATUS

```powershell
# Ver migrations executadas
C:\xampp\php\php.exe spark migrate:status

# Esperado: 7 novas migrations nos batches 11-16
# ✅ 2025-10-05-100000_CreateTefTransactions (batch 11)
# ✅ 2025-10-05-110000_AddTefFieldToPosSales (batch 11)
# ✅ 2025-10-05-120000_CreatePixTransactions (batch 12)
# ✅ 2025-10-05-130000_CreatePosSalePayments (batch 13)
# ✅ 2025-10-05-140000_AddSuspensionToPosSales (batch 14)
# ✅ 2025-10-05-150000_CreateDiscountsAndCoupons (batch 15)
# ✅ 2025-10-05-160000_CreateReturnsAndExchanges (batch 16)
```

---

## 2️⃣ EXECUTAR TESTES AUTOMATIZADOS

### Opção 1: Script Interativo (RECOMENDADO)
```powershell
.\test-runner.bat
```

**Menu aparecerá:**
```
1 - Executar TODOS os testes
2 - Testar TEF (5 testes)
3 - Testar PIX (6 testes)
4 - Testar Multi-Payment (6 testes)
5 - Testar Suspensao (7 testes)
6 - Testar Descontos (7 testes)
7 - Testar Devolucoes (5 testes)
```

### Opção 2: Comandos Manuais

```powershell
# TODOS os testes (36 testes)
C:\xampp\php\php.exe vendor/bin/phpunit --testdox

# TEF (5 testes)
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/TefMultiTenantTest.php --testdox

# PIX (6 testes)
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/PixMultiTenantTest.php --testdox

# Multi-Payment (6 testes)
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/MultiPaymentTest.php --testdox

# Suspensão (7 testes)
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/SuspensionTest.php --testdox

# Descontos (7 testes)
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/DiscountTest.php --testdox

# Devoluções (5 testes)
C:\xampp\php\php.exe vendor/bin/phpunit tests/multitenant/ReturnTest.php --testdox
```

---

## 3️⃣ PREPARAR BANCO PARA TESTES MANUAIS

```sql
-- Conectar no MySQL
mysql -u root -p erp_local

-- Criar Empresas de Teste
INSERT INTO empresas (id_empresa, id_contador, xFant, CNPJ, IE) 
VALUES 
(100, 1, 'Loja Teste 1', '11111111000111', '123456789'),
(200, 1, 'Loja Teste 2', '22222222000122', '987654321');

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

-- Criar Cupom de Teste
INSERT INTO coupons (
    code, type, value, min_purchase, usage_limit,
    valid_from, valid_until, is_active,
    id_contador, id_empresa, created_at, updated_at
) VALUES (
    'PROMO10', 'percentage', 10.00, 50.00, 100,
    NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1,
    1, 100, NOW(), NOW()
);

-- Criar Produto de Teste
INSERT INTO produtos (id_produto, xProd, vUnCom, qCom, id_contador, id_empresa, cProd, uCom, cEAN)
VALUES (999, 'Produto Teste PDV', 50.00, 100, 1, 100, 'PROD999', 'UN', '7891234567890');
```

---

## 4️⃣ TESTAR VIA POSTMAN/INSOMNIA

### A) Fazer Login

```http
POST http://localhost/erp.local/public/pdv/login
Content-Type: application/json

{
  "usuario": "admin",
  "senha": "sua_senha",
  "id_empresa": 100
}
```

**Salvar o Cookie retornado!**

### B) Criar Venda

```http
POST http://localhost/erp.local/public/api/pos
Content-Type: application/json
Cookie: ci_session=YOUR_SESSION_ID

{
  "items": [
    {
      "id_produto": 999,
      "quantity": 2,
      "unit_price": 50.00
    }
  ]
}
```

**Resposta:** `{"id_pos_sale": 1, "total": 100.00, "status": "pending"}`

### C) Finalizar com TEF

```http
POST http://localhost/erp.local/public/api/pos/1/finalize
Content-Type: application/json
Cookie: ci_session=YOUR_SESSION_ID

{
  "payment_type": "credit",
  "total": 100.00,
  "installments": 3,
  "card_data": {
    "number": "4111111111111111",
    "holder": "JOSE SILVA",
    "expiry": "12/2030",
    "cvv": "123"
  }
}
```

### D) Finalizar com PIX

```http
POST http://localhost/erp.local/public/api/pos/2/finalize
Content-Type: application/json
Cookie: ci_session=YOUR_SESSION_ID

{
  "payment_type": "pix",
  "total": 75.00
}
```

**Resposta:** QR Code PIX gerado

### E) Múltiplas Formas

```http
POST http://localhost/erp.local/public/api/pos/3/finalize
Content-Type: application/json
Cookie: ci_session=YOUR_SESSION_ID

{
  "payment_type": "multiple",
  "total": 200.00,
  "payments": [
    {"type": "cash", "amount": 50.00},
    {"type": "credit", "amount": 100.00, "installments": 2},
    {"type": "pix", "amount": 50.00}
  ]
}
```

### F) Suspender Venda

```http
POST http://localhost/erp.local/public/api/pos/4/suspend
Content-Type: application/json
Cookie: ci_session=YOUR_SESSION_ID

{
  "reason": "Cliente foi ao caixa eletrônico"
}
```

### G) Aplicar Desconto

```http
POST http://localhost/erp.local/public/api/pos/5/discount
Content-Type: application/json
Cookie: ci_session=YOUR_SESSION_ID

{
  "type": "percentage",
  "value": 15.00,
  "reason": "Cliente VIP"
}
```

### H) Aplicar Cupom

```http
POST http://localhost/erp.local/public/api/pos/6/coupon
Content-Type: application/json
Cookie: ci_session=YOUR_SESSION_ID

{
  "code": "PROMO10"
}
```

### I) Processar Devolução

```http
POST http://localhost/erp.local/public/api/pos/returns/process
Content-Type: application/json
Cookie: ci_session=YOUR_SESSION_ID

{
  "id_sale": 1,
  "type": "full_return",
  "reason": "Produto com defeito",
  "refund_method": "same_method",
  "approved_by": 1,
  "restock": true
}
```

---

## 5️⃣ CRON JOBS (Executar Manualmente)

```powershell
# Expirar PIX pendentes
C:\xampp\php\php.exe spark pix:expire

# Expirar vendas suspensas
C:\xampp\php\php.exe spark sales:expire-suspended
```

---

## 6️⃣ VERIFICAR LOGS

```powershell
# Ver últimas 50 linhas
Get-Content -Tail 50 writable/logs/log-2025-10-01.log

# Filtrar por TEF
Select-String -Path "writable/logs/*.log" -Pattern "\[TEF\]" | Select-Object -Last 20

# Filtrar por PIX
Select-String -Path "writable/logs/*.log" -Pattern "\[PIX\]" | Select-Object -Last 20

# Filtrar erros
Select-String -Path "writable/logs/*.log" -Pattern "ERROR" | Select-Object -Last 20
```

---

## 7️⃣ TROUBLESHOOTING

### Erro: "Class MultiTenantTestCase not found"

```powershell
# Atualizar autoload
composer dump-autoload
```

### Erro: "Database connection failed"

```powershell
# Verificar .env
notepad .env

# Verificar:
# database.default.hostname = localhost
# database.default.database = erp_local
# database.default.username = root
# database.default.password = 
```

### Erro: "Migration already ran"

```powershell
# Ver status
C:\xampp\php\php.exe spark migrate:status

# Rollback se necessário
C:\xampp\php\php.exe spark migrate:rollback
```

---

## ✅ CHECKLIST RÁPIDO

- [ ] Migrations executadas (7)
- [ ] Empresas de teste criadas (100, 200)
- [ ] Configurações TEF/PIX setadas
- [ ] Produto de teste criado (999)
- [ ] Cupom de teste criado (PROMO10)
- [ ] Testes automatizados passando (36/36)
- [ ] API respondendo corretamente
- [ ] Logs sem erros críticos

---

## 🎯 RESULTADO ESPERADO

```
TODOS OS TESTES PASSANDO:
✅ TEF: 5/5 tests passing
✅ PIX: 6/6 tests passing
✅ Multi-Payment: 6/6 tests passing
✅ Suspensão: 7/7 tests passing
✅ Descontos: 7/7 tests passing
✅ Devoluções: 5/5 tests passing

TOTAL: 36/36 ✅✅✅
```

---

**Boa sorte com os testes! 🚀**

