# 🎬 ROTEIRO DE TESTE RÁPIDO - 10 MINUTOS

**Para:** Alguém que vai testar pela primeira vez  
**Tempo:** 10 minutos  
**Resultado:** Validar PIX e TEF funcionando  

---

## 🚀 PREPARAÇÃO (2 MINUTOS)

### 1. Abra o MySQL Workbench (ou similar)

Cole e execute este SQL:

```sql
-- PREPARAR TUDO DE UMA VEZ
INSERT INTO produtos (id_produto, xProd, vUnCom, qCom, id_contador, id_empresa, cProd, uCom, cEAN)
VALUES (999, 'Produto Teste', 50.00, 100, 1, 100, 'PROD999', 'UN', '7891234567890')
ON DUPLICATE KEY UPDATE xProd = 'Produto Teste';

UPDATE empresas SET 
    tef_acquirer = 'cielo',
    tef_environment = 'sandbox',
    pix_provider = 'mercadopago',
    pix_key = '11111111000111',
    pix_access_token = 'TEST_TOKEN'
WHERE id_empresa = 100;
```

✅ **OK? Próximo passo!**

---

## 🧪 TESTE 1: PIX (3 MINUTOS)

### Passo 1: Abra o PDV

1. Navegador: `http://localhost/erp.local/public/pdv`
2. Login: `admin` / sua senha
3. Empresa: `100`

### Passo 2: Adicione Produto

1. Digite na busca: `Produto Teste`
2. Clique para adicionar
3. Adicione 2 unidades
4. Total deve mostrar: **R$ 100,00**

### Passo 3: Selecione PIX

1. Clique no botão **"PIX"** (fica azul)
2. Clique em **"Finalizar (sem NFC-e)"**

### Passo 4: Observe o Resultado

**✅ O QUE DEVE ACONTECER:**

1. Aparece "Processando..." ⏳
2. Depois aparece modal grande com:
   - ✅ "PIX - Aguardando Pagamento"
   - ✅ Valor: R$ 100,00
   - ✅ QR Code (imagem quadrada)
   - ✅ Código PIX (texto longo)
   - ✅ Expira em: (data)

**❌ SE DER ERRO:**

- Tire print do erro
- Anote a mensagem completa
- Pule para Teste 2

**✅ SE FUNCIONOU:**

🎉 **PIX ESTÁ FUNCIONANDO!** 

Clique em OK e vá para o próximo teste.

---

## 💳 TESTE 2: TEF/CARTÃO (3 MINUTOS)

### Passo 1: Nova Venda

1. Adicione o produto novamente
2. Adicione 3 unidades
3. Total: **R$ 150,00**

### Passo 2: Selecione Cartão

1. Clique no botão **"Cartão Crédito"** (fica azul)
2. Clique em **"Finalizar (sem NFC-e)"**

### Passo 3: Observe o Resultado

**✅ O QUE DEVE ACONTECER:**

1. Aparece "Processando..." ⏳
2. Depois aparece modal com:
   - ✅ "Pagamento Aprovado!"
   - ✅ "Cartão Crédito"
   - ✅ NSU: (código)
   - ✅ Autorização: (código)

**❌ SE DER ERRO:**

- Tire print do erro
- Anote a mensagem completa
- Continue para validação

**✅ SE FUNCIONOU:**

🎉 **TEF ESTÁ FUNCIONANDO!**

---

## 🔍 VALIDAÇÃO FINAL (2 MINUTOS)

### Abra o MySQL e execute:

```sql
-- Ver transações PIX
SELECT 
    'PIX' as tipo,
    COUNT(*) as total,
    status
FROM pix_transactions 
WHERE id_empresa = 100 
GROUP BY status;

-- Ver transações TEF
SELECT 
    'TEF' as tipo,
    COUNT(*) as total,
    status
FROM tef_transactions 
WHERE id_empresa = 100 
GROUP BY status;
```

**✅ RESULTADO ESPERADO:**

```
PIX: 1 registro, status = pending
TEF: 1 registro, status = confirmed
```

Se aparecerem esses registros = **SISTEMA FUNCIONANDO 100%!** ✅

---

## 📸 TIRE PRINTS!

Se funcionou, tire print de:

1. ✅ Modal do PIX com QR Code
2. ✅ Modal do TEF com aprovação
3. ✅ Resultado do SQL (transações criadas)

Envie os prints para confirmar que está tudo OK!

---

## ❌ SE DEU ERRO

### Erro no PIX: "There is no data to update"

**Solução:**
```sql
-- Verificar se a correção foi aplicada
SELECT TABLE_NAME, COLUMN_NAME 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'erp_local' 
AND TABLE_NAME = 'pix_transactions';
```

Se `qr_code` aparecer na lista, a tabela está OK.

**Se ainda der erro:**
- Copie a mensagem completa do erro
- Copie o log: `writable/logs/log-2025-10-02.log`
- Envie para análise

### Erro no TEF: Qualquer erro

**Verificar:**
```sql
SELECT tef_acquirer, tef_environment 
FROM empresas 
WHERE id_empresa = 100;
```

Deve retornar:
- `tef_acquirer: cielo`
- `tef_environment: sandbox`

---

## 🎯 RESULTADO FINAL

### ✅ SE OS 2 TESTES PASSARAM:

**PARABÉNS! SISTEMA 100% FUNCIONAL!** 🎉

Você validou:
- ✅ PIX gera QR Code
- ✅ TEF aprova transações
- ✅ Registros são criados corretamente
- ✅ Isolamento multi-tenant funcionando

### ❌ SE ALGUM TESTE FALHOU:

**NÃO SE PREOCUPE!**

1. Tire print do erro
2. Copie os logs
3. Anote qual teste falhou
4. Envie para análise

A correção será rápida! 🚀

---

## 📞 CONTATO

**Se precisar de ajuda:**

1. ✅ Envie prints dos erros
2. ✅ Envie última linha do log: `writable/logs/log-2025-10-02.log`
3. ✅ Diga qual teste falhou (PIX ou TEF)

**Resposta em minutos!** ⚡

---

**🎬 FIM DO ROTEIRO**

**Tempo total:** ~10 minutos  
**Testes executados:** 2 (PIX + TEF)  
**Validação:** SQL

