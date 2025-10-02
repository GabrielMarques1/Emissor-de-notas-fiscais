# ✅ CHECKLIST DE TESTES MANUAL - PDV MULTI-TENANT

**Para:** Pessoa que vai testar o sistema  
**Tempo estimado:** 30 minutos  
**Conhecimento necessário:** Básico (saber usar navegador)  

---

## 🎯 OBJETIVO

Validar se as 6 funcionalidades implementadas estão funcionando corretamente.

---

## 📋 PRÉ-REQUISITOS (FAZER ANTES DOS TESTES)

### 1. Preparar Banco de Dados

Abra o MySQL e execute:

```sql
-- Criar produto de teste
INSERT INTO produtos (id_produto, xProd, vUnCom, qCom, id_contador, id_empresa, cProd, uCom, cEAN)
VALUES (999, 'Produto Teste PDV', 50.00, 100, 1, 100, 'PROD999', 'UN', '7891234567890')
ON DUPLICATE KEY UPDATE xProd = 'Produto Teste PDV';

-- Criar cupom de teste
INSERT INTO coupons (
    code, type, value, min_purchase, usage_limit,
    valid_from, valid_until, is_active,
    id_contador, id_empresa, created_at, updated_at
) VALUES (
    'PROMO10', 'percentage', 10.00, 50.00, 100,
    NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1,
    1, 100, NOW(), NOW()
) ON DUPLICATE KEY UPDATE is_active = 1;

-- Configurar TEF
UPDATE empresas SET 
    tef_acquirer = 'cielo',
    tef_merchant_id = 'TEST_MERCHANT',
    tef_merchant_key = 'TEST_KEY',
    tef_environment = 'sandbox',
    tef_timeout = 30,
    tef_max_installments = 12
WHERE id_empresa = 100;

-- Configurar PIX
UPDATE empresas SET 
    pix_provider = 'mercadopago',
    pix_key = '11111111000111',
    pix_access_token = 'TEST_TOKEN',
    pix_expiration_minutes = 15
WHERE id_empresa = 100;

-- Configurar Descontos
UPDATE empresas SET 
    max_discount_percentage = 30.00,
    max_discount_amount = 100.00,
    discount_approval_threshold = 20.00
WHERE id_empresa = 100;

-- Configurar Devoluções
UPDATE empresas SET 
    return_days_limit = 7,
    require_return_approval = true,
    allow_partial_returns = true,
    allow_exchanges = true
WHERE id_empresa = 100;
```

✅ **Feito? Marque aqui:** [ ]

---

## 🧪 TESTES

### TESTE 1: PIX ⏱️ 5 minutos

**Objetivo:** Validar se o QR Code PIX é gerado corretamente

#### Passos:

1. ✅ Abra: `http://localhost/erp.local/public/pdv`
2. ✅ Faça login (usuário admin, empresa 100)
3. ✅ Adicione o produto "Produto Teste PDV" ao carrinho (2 unidades)
4. ✅ Clique no botão **"PIX"** (deve ficar azul)
5. ✅ Clique em **"Finalizar (sem NFC-e)"**
6. ✅ **RESULTADO ESPERADO:**
   - [ ] Aparece modal "Processando..."
   - [ ] Depois aparece modal "PIX - Aguardando Pagamento"
   - [ ] Modal mostra:
     - [ ] Valor da venda (R$ 100,00)
     - [ ] QR Code (imagem)
     - [ ] Código PIX (texto longo)
     - [ ] Data de expiração
7. ✅ Copie o **TXID** (código que aparece no modal)
8. ✅ Abra outra aba do navegador
9. ✅ Acesse: `http://localhost/erp.local/public/api/pix/webhook/100`
10. ✅ Use Postman ou cURL para enviar:
    ```json
    POST /api/pix/webhook/100
    {
      "txid": "COLE_O_TXID_AQUI",
      "e2e_id": "E123456782025100212345678",
      "status": "paid",
      "amount": 100.00
    }
    ```
11. ✅ Volte para o PDV
12. ✅ **RESULTADO FINAL ESPERADO:**
    - [ ] Venda foi confirmada automaticamente

**Status:** [ ] PASSOU ✅ | [ ] FALHOU ❌  
**Observações:** _________________________________

---

### TESTE 2: TEF (CARTÃO) ⏱️ 3 minutos

**Objetivo:** Validar pagamento com cartão via TEF

#### Passos:

1. ✅ Adicione o produto ao carrinho (3 unidades = R$ 150,00)
2. ✅ Clique em **"Cartão Crédito"** (deve ficar azul)
3. ✅ Clique em **"Finalizar (sem NFC-e)"**
4. ✅ **RESULTADO ESPERADO:**
   - [ ] Aparece modal "Processando..."
   - [ ] Depois aparece modal "Pagamento Aprovado!"
   - [ ] Modal mostra:
     - [ ] "Cartão Crédito"
     - [ ] NSU: (código)
     - [ ] Autorização: (código)
5. ✅ Clique em OK
6. ✅ Verifique no banco:
    ```sql
    SELECT * FROM tef_transactions ORDER BY created_at DESC LIMIT 1;
    ```
7. ✅ **RESULTADO BANCO:**
   - [ ] Transação existe
   - [ ] status = 'confirmed'
   - [ ] id_empresa = 100 (isolamento OK)

**Status:** [ ] PASSOU ✅ | [ ] FALHOU ❌  
**Observações:** _________________________________

---

### TESTE 3: MÚLTIPLAS FORMAS ⏱️ 5 minutos

**Objetivo:** Validar venda com dinheiro + cartão + PIX

#### Passos:

1. ✅ Adicione o produto ao carrinho (4 unidades = R$ 200,00)
2. ✅ **NÃO** clique em nenhum botão de pagamento ainda
3. ✅ Abra console do navegador (F12)
4. ✅ Digite no console:
    ```javascript
    fetch('/api/pos/' + window.PDV.saleId + '/finalize', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        payment_type: 'multiple',
        total: 200.00,
        payments: [
          { type: 'cash', amount: 50.00 },
          { type: 'credit', amount: 100.00, installments: 2 },
          { type: 'pix', amount: 50.00 }
        ]
      })
    }).then(r => r.json()).then(d => console.log(d));
    ```
5. ✅ Pressione Enter
6. ✅ **RESULTADO ESPERADO NO CONSOLE:**
   - [ ] `success: true`
   - [ ] `payments: [3 itens]`
   - [ ] `summary.total_paid: 200`
7. ✅ Verifique no banco:
    ```sql
    SELECT * FROM pos_sale_payments ORDER BY created_at DESC LIMIT 3;
    ```
8. ✅ **RESULTADO BANCO:**
   - [ ] 3 registros (cash, credit, pix)
   - [ ] Soma = R$ 200,00
   - [ ] Todos com id_empresa = 100

**Status:** [ ] PASSOU ✅ | [ ] FALHOU ❌  
**Observações:** _________________________________

---

### TESTE 4: SUSPENSÃO DE VENDAS ⏱️ 4 minutos

**Objetivo:** Validar pausar e retomar venda

#### Passos:

1. ✅ Adicione o produto ao carrinho (1 unidade = R$ 50,00)
2. ✅ Anote o ID da venda (aparece no topo do PDV ou no console: `window.PDV.saleId`)
3. ✅ Abra console do navegador (F12)
4. ✅ Digite no console:
    ```javascript
    fetch('/api/pos/' + window.PDV.saleId + '/suspend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason: 'Cliente foi ao caixa eletrônico' })
    }).then(r => r.json()).then(d => console.log(d));
    ```
5. ✅ **RESULTADO ESPERADO:**
   - [ ] `success: true`
   - [ ] Carrinho foi limpo
6. ✅ Verifique no banco:
    ```sql
    SELECT * FROM pos_sales WHERE is_suspended = 1 ORDER BY created_at DESC LIMIT 1;
    ```
7. ✅ **RESULTADO BANCO:**
   - [ ] is_suspended = 1
   - [ ] suspended_at preenchido
   - [ ] expires_at preenchido (futuro)
8. ✅ Para retomar, digite no console:
    ```javascript
    fetch('/api/pos/SEU_SALE_ID/resume', { method: 'POST' })
      .then(r => r.json()).then(d => console.log(d));
    ```
9. ✅ **RESULTADO:**
   - [ ] Venda retomada (is_suspended = 0)

**Status:** [ ] PASSOU ✅ | [ ] FALHOU ❌  
**Observações:** _________________________________

---

### TESTE 5: DESCONTOS ⏱️ 4 minutos

**Objetivo:** Validar descontos manuais e cupons

#### Passos:

1. ✅ Adicione o produto ao carrinho (2 unidades = R$ 100,00)
2. ✅ Anote o ID da venda
3. ✅ Aplicar desconto de 15%:
    ```javascript
    fetch('/api/pos/' + window.PDV.saleId + '/discount', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'percentage',
        value: 15.00,
        reason: 'Cliente VIP'
      })
    }).then(r => r.json()).then(d => console.log(d));
    ```
4. ✅ **RESULTADO ESPERADO:**
   - [ ] `success: true`
   - [ ] `discount_amount: 15.00`
   - [ ] `new_total: 85.00`
5. ✅ Aplicar cupom PROMO10:
    ```javascript
    fetch('/api/pos/' + window.PDV.saleId + '/coupon', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code: 'PROMO10' })
    }).then(r => r.json()).then(d => console.log(d));
    ```
6. ✅ **RESULTADO ESPERADO:**
   - [ ] `success: true`
   - [ ] Desconto adicional aplicado
7. ✅ Verifique no banco:
    ```sql
    SELECT * FROM discounts ORDER BY created_at DESC LIMIT 2;
    ```

**Status:** [ ] PASSOU ✅ | [ ] FALHOU ❌  
**Observações:** _________________________________

---

### TESTE 6: DEVOLUÇÕES ⏱️ 5 minutos

**Objetivo:** Validar devolução de venda

#### Passos:

1. ✅ Primeiro, finalize uma venda qualquer (pode usar dinheiro)
2. ✅ Anote o ID da venda finalizada
3. ✅ Processar devolução:
    ```javascript
    fetch('/api/pos/returns/process', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_sale: SEU_SALE_ID,
        type: 'full_return',
        reason: 'Produto com defeito',
        refund_method: 'same_method',
        approved_by: 1,
        restock: true
      })
    }).then(r => r.json()).then(d => console.log(d));
    ```
4. ✅ **RESULTADO ESPERADO:**
   - [ ] `success: true`
   - [ ] `return.type: 'full_return'`
   - [ ] `refund.success: true`
5. ✅ Verifique no banco:
    ```sql
    SELECT * FROM returns ORDER BY created_at DESC LIMIT 1;
    ```
6. ✅ **RESULTADO BANCO:**
   - [ ] Devolução registrada
   - [ ] id_empresa = 100
   - [ ] refund_status = 'completed'

**Status:** [ ] PASSOU ✅ | [ ] FALHOU ❌  
**Observações:** _________________________________

---

## 🔐 TESTE DE ISOLAMENTO MULTI-TENANT ⏱️ 5 minutos

**Objetivo:** Garantir que Empresa 100 não vê dados da Empresa 200

#### Passos:

1. ✅ Execute no MySQL:
    ```sql
    -- Verificar isolamento
    SELECT 
        'PIX' as tabela,
        COUNT(CASE WHEN id_empresa = 100 THEN 1 END) as empresa_100,
        COUNT(CASE WHEN id_empresa = 200 THEN 1 END) as empresa_200,
        COUNT(CASE WHEN id_empresa NOT IN (100, 200) THEN 1 END) as outras
    FROM pix_transactions
    UNION ALL
    SELECT 
        'TEF',
        COUNT(CASE WHEN id_empresa = 100 THEN 1 END),
        COUNT(CASE WHEN id_empresa = 200 THEN 1 END),
        COUNT(CASE WHEN id_empresa NOT IN (100, 200) THEN 1 END)
    FROM tef_transactions
    UNION ALL
    SELECT 
        'Multi-Payment',
        COUNT(CASE WHEN id_empresa = 100 THEN 1 END),
        COUNT(CASE WHEN id_empresa = 200 THEN 1 END),
        COUNT(CASE WHEN id_empresa NOT IN (100, 200) THEN 1 END)
    FROM pos_sale_payments;
    ```
2. ✅ **RESULTADO ESPERADO:**
   - [ ] Empresa 100: tem dados
   - [ ] Empresa 200: sem dados (ou dados separados)
   - [ ] Outras: 0 (sem vazamento)

**Status:** [ ] PASSOU ✅ | [ ] FALHOU ❌

---

## 📊 RESULTADO FINAL

### Resumo dos Testes:

- [ ] TESTE 1: PIX
- [ ] TESTE 2: TEF (Cartão)
- [ ] TESTE 3: Múltiplas Formas
- [ ] TESTE 4: Suspensão
- [ ] TESTE 5: Descontos
- [ ] TESTE 6: Devoluções
- [ ] TESTE 7: Isolamento Multi-Tenant

### Score:

**___ / 7 testes passaram** (___%)

---

## 📝 OBSERVAÇÕES GERAIS

(Anote aqui qualquer problema encontrado)

```
_____________________________________________

_____________________________________________

_____________________________________________
```

---

## ✅ SE TODOS OS TESTES PASSARAM

**PARABÉNS! O SISTEMA ESTÁ 100% FUNCIONAL!** 🎉

O PDV Multi-Tenant está pronto para:
- ✅ Receber pagamentos via PIX
- ✅ Receber pagamentos via cartão (TEF)
- ✅ Processar múltiplas formas de pagamento
- ✅ Suspender e retomar vendas
- ✅ Aplicar descontos e cupons
- ✅ Processar devoluções
- ✅ Garantir isolamento total entre empresas

---

**Data do teste:** ___ / ___ / _____  
**Testado por:** ____________________  
**Assinatura:** ____________________

