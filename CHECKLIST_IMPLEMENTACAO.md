# ✅ CHECKLIST DE IMPLEMENTAÇÃO - PDV MULTI-TENANT

**Status Geral:** 🟡 **EM ANDAMENTO**  
**Última Atualização:** 01/10/2025  

---

## 📊 PROGRESSO GERAL

```
█████████████████████░░░░░░░░░ 70%

Completado: 158h / 226h
Faltante:   68h
```

### Distribuição por Fase

| Fase | Status | Progresso | Tempo |
|------|--------|-----------|-------|
| **Base (Existente)** | ✅ | 100% | 158h |
| **Fase 1 (Crítica)** | 🔴 | 0% | 88h |
| **Fase 2 (Essencial)** | 🔴 | 0% | 54h |
| **Fase 3 (Qualidade)** | 🔴 | 0% | 84h |

---

## 🔴 FASE 1: BLOQUEADORES DE PRODUÇÃO (88 horas)

### SPRINT 1.1: Integração TEF (40 horas)
**Responsável:** _____________________  
**Prazo:** _____________________  
**Status:** 🔴 NÃO INICIADO

#### Subtarefas

- [ ] **1.1.1 - Escolher e configurar biblioteca TEF** (4h)
  - [ ] Avaliar Cloudwalk SDK vs Cielo API
  - [ ] Instalar biblioteca: `composer require cielo/api-3.0-php`
  - [ ] Criar `app/Config/Tef.php`
  - [ ] Documentar escolha no README
  - **Validação:** Código roda sem erro
  
- [ ] **1.1.2 - Criar migration tef_transactions** (2h)
  - [ ] Criar arquivo `2025-10-02-000001_CreateTefTransactions.php`
  - [ ] Campos: txid, nsu, authorization_code, status, valor, etc
  - [ ] Índices: idx_tenant, idx_nsu, idx_status
  - [ ] Foreign keys para tenants
  - **Validação:** `php spark migrate` executa sem erro
  
- [ ] **1.1.3 - Adicionar credenciais TEF em empresas** (1h)
  - [ ] Criar migration `AddTefCredentialsToEmpresas.php`
  - [ ] Campos: tef_merchant_id, tef_merchant_key, tef_adquirente
  - [ ] Criptografar merchant_key com `encrypt()`
  - **Validação:** Tabela possui novos campos
  
- [ ] **1.1.4 - Criar TefTransactionModel** (2h)
  - [ ] Estender BaseAppModel
  - [ ] Métodos: findByNsu(), getPending()
  - [ ] Validações
  - **Validação:** Testes unitários passam
  
- [ ] **1.1.5 - Criar TefService (núcleo)** (16h)
  - [ ] Método authorize()
  - [ ] Método confirm()
  - [ ] Método cancel()
  - [ ] Método query()
  - [ ] Logs detalhados
  - **Validação:** Autorização TEF bem-sucedida em homologação
  
- [ ] **1.1.6 - Criar adapters por adquirente** (12h)
  - [ ] Interface TefAdapterInterface
  - [ ] CieloAdapter::authorize()
  - [ ] CieloAdapter::confirm()
  - [ ] CieloAdapter::cancel()
  - [ ] CieloAdapter::query()
  - [ ] StoneAdapter (opcional)
  - [ ] RedeAdapter (opcional)
  - **Validação:** Transação Cielo completa (autorizar + confirmar)
  
- [ ] **1.1.7 - Integrar TEF no Pos Controller** (3h)
  - [ ] Modificar método finalize()
  - [ ] Validar forma de pagamento
  - [ ] Processar TEF antes de finalizar
  - [ ] Rollback em caso de falha
  - **Validação:** Venda com cartão finaliza corretamente
  
- [ ] **1.1.8 - Testes e homologação** (2h)
  - [ ] TefServiceTest::testAuthorize()
  - [ ] TefServiceTest::testConfirm()
  - [ ] TefServiceTest::testCancel()
  - [ ] Teste integrado completo
  - **Validação:** Cobertura > 80%

**Critérios de Conclusão:**
- ✅ Transações TEF funcionando em homologação
- ✅ Testes automatizados com > 80% cobertura
- ✅ Logs incluem todas as transações
- ✅ Código revisado e aprovado

---

### SPRINT 1.2: Múltiplas Formas de Pagamento (16 horas)
**Responsável:** _____________________  
**Prazo:** _____________________  
**Status:** 🔴 NÃO INICIADO

#### Subtarefas

- [ ] **1.2.1 - Criar tabela pos_sale_payments** (2h)
  - [ ] Migration com campos: id_payment, id_pos_sale, payment_type, amount
  - [ ] Vínculos: id_tef_transaction, id_pix_transaction
  - [ ] Índices adequados
  - **Validação:** Migration executa sem erro
  
- [ ] **1.2.2 - Criar PosSalePaymentModel** (1h)
  - [ ] Métodos: getBySale(), getTotalPaid()
  - [ ] Validações
  - **Validação:** Consultas retornam dados corretos
  
- [ ] **1.2.3 - Refatorar Pos::finalize()** (8h)
  - [ ] Aceitar array de pagamentos no payload
  - [ ] Validar soma = total da venda
  - [ ] Processar cada pagamento (TEF, PIX, Cash)
  - [ ] Rollback completo em falha
  - [ ] Registrar em pos_sale_payments
  - **Validação:** Venda com 2+ pagamentos finaliza
  
- [ ] **1.2.4 - Atualizar relatórios de caixa** (3h)
  - [ ] Modificar CaixaSessaoModel::closeOpenSession()
  - [ ] Buscar totais de pos_sale_payments
  - [ ] Atualizar ShiftModel::report()
  - **Validação:** Relatório exibe corretamente múltiplos pagamentos
  
- [ ] **1.2.5 - Migração de dados existentes** (2h)
  - [ ] Script para migrar payment_type de pos_sales → pos_sale_payments
  - [ ] Backup antes de migrar
  - [ ] Validação de integridade
  - **Validação:** Todos os pagamentos migrados corretamente

**Critérios de Conclusão:**
- ✅ Vendas com múltiplos pagamentos funcionam
- ✅ Relatórios de caixa corretos
- ✅ Dados existentes migrados sem perda

---

### SPRINT 1.3: PIX com QR Code e Webhook (32 horas)
**Responsável:** _____________________  
**Prazo:** _____________________  
**Status:** 🔴 NÃO INICIADO

#### Subtarefas

- [ ] **1.3.1 - Criar tabela pix_transactions** (2h)
  - [ ] Campos: txid, e2e_id, qr_code, status, expires_at
  - [ ] Índices: idx_txid, idx_status
  - **Validação:** Migration OK
  
- [ ] **1.3.2 - Adicionar credenciais PIX em empresas** (1h)
  - [ ] Campos: pix_provider, pix_access_token, pix_webhook_secret
  - **Validação:** Campos criados
  
- [ ] **1.3.3 - Criar PixService** (16h)
  - [ ] Método generate() - Gera QR Code
  - [ ] Método confirm() - Confirma pagamento
  - [ ] Método expireOld() - Expira QR Codes
  - [ ] Geração de TXID único
  - [ ] Integração com Mercado Pago/PagSeguro
  - **Validação:** QR Code gerado e pode ser pago
  
- [ ] **1.3.4 - Criar webhook controller** (4h)
  - [ ] PixWebhook::mercadopago()
  - [ ] Validação de assinatura HMAC
  - [ ] Processamento de evento payment.created
  - [ ] Confirmação automática da venda
  - **Validação:** Webhook recebe e processa evento
  
- [ ] **1.3.5 - Integrar PIX no fluxo de venda** (6h)
  - [ ] Endpoint Pos::generatePix()
  - [ ] Endpoint Pos::pixStatus()
  - [ ] Status pending_payment
  - [ ] Finalização automática ao confirmar
  - **Validação:** Venda com PIX completa
  
- [ ] **1.3.6 - Cron job de expiração** (1h)
  - [ ] Command ExpirePixQrCodes
  - [ ] Configurar crontab (*/1 * * * *)
  - **Validação:** QR Codes expirados marcados corretamente
  
- [ ] **1.3.7 - Testes** (2h)
  - [ ] PixServiceTest::testGenerate()
  - [ ] PixServiceTest::testConfirm()
  - [ ] PixWebhookTest::testWebhookProcessing()
  - **Validação:** Cobertura > 70%

**Critérios de Conclusão:**
- ✅ QR Code PIX gerado e exibido ao cliente
- ✅ Webhook confirma pagamento automaticamente
- ✅ Venda finaliza ao receber confirmação
- ✅ QR Codes expirados após timeout

---

## 🟡 FASE 2: FUNCIONALIDADES ESSENCIAIS (54 horas)

### SPRINT 2.1: Sangria e Suprimento (12 horas)
**Responsável:** _____________________  
**Prazo:** _____________________  
**Status:** 🔴 NÃO INICIADO

#### Subtarefas

- [ ] **2.1.1 - Criar tabela caixa_movimentacoes** (2h)
  - [ ] Campos: tipo (sangria/suprimento), valor, motivo
  - [ ] Foreign keys para caixa_sessoes
  - **Validação:** Migration OK
  
- [ ] **2.1.2 - Criar CaixaMovimentacaoModel** (1h)
  - [ ] Validações
  - [ ] Métodos: getBySessao()
  - **Validação:** CRUD funciona
  
- [ ] **2.1.3 - Criar endpoints de sangria/suprimento** (4h)
  - [ ] POST /api/caixa/sangria
  - [ ] POST /api/caixa/suprimento
  - [ ] GET /api/caixa/{id}/movimentacoes
  - [ ] Validar caixa aberto
  - **Validação:** Endpoints retornam 200
  
- [ ] **2.1.4 - Atualizar relatório de fechamento** (3h)
  - [ ] Incluir movimentações no relatório
  - [ ] Calcular valor esperado considerando sangrias/suprimentos
  - **Validação:** Relatório correto com movimentações
  
- [ ] **2.1.5 - Testes** (2h)
  - [ ] Testes unitários
  - [ ] Testes de integração
  - **Validação:** Cobertura > 70%

**Critérios de Conclusão:**
- ✅ Sangrias e suprimentos registrados
- ✅ Relatório de caixa considera movimentações
- ✅ Auditoria completa

---

### SPRINT 2.2: Suspensão/Retomada de Vendas (8 horas)
**Responsável:** _____________________  
**Prazo:** _____________________  
**Status:** 🔴 NÃO INICIADO

#### Subtarefas

- [ ] **2.2.1 - Criar status 'suspended'** (1h)
  - [ ] Alterar validação em PosSaleModel
  - [ ] Adicionar status em seed de configuração
  - **Validação:** Status aceito pelo banco
  
- [ ] **2.2.2 - Criar endpoints suspend/resume** (3h)
  - [ ] POST /api/pos/{id}/suspend
  - [ ] GET /api/pos/suspended
  - [ ] POST /api/pos/{id}/resume
  - [ ] Validar apenas 1 venda ativa por vez
  - **Validação:** Venda suspensa e retomada
  
- [ ] **2.2.3 - UI no PDV** (3h)
  - [ ] Botão "Suspender Venda"
  - [ ] Modal listar vendas suspensas
  - [ ] Botão "Retomar"
  - **Validação:** Fluxo completo no front-end
  
- [ ] **2.2.4 - Testes** (1h)
  - [ ] Testes de API
  - **Validação:** Cobertura > 70%

**Critérios de Conclusão:**
- ✅ Vendas podem ser suspensas e retomadas
- ✅ Apenas 1 venda ativa por vez

---

### SPRINT 2.3: Descontos com Validação (10 horas)
**Responsável:** _____________________  
**Prazo:** _____________________  
**Status:** 🔴 NÃO INICIADO

#### Subtarefas

- [ ] **2.3.1 - Criar tabela discount_authorizations** (2h)
  - [ ] Campos: id_pos_sale, discount_type, discount_value, authorized_by
  - **Validação:** Migration OK
  
- [ ] **2.3.2 - Criar permissões de desconto** (2h)
  - [ ] discount.apply.item (até 5%)
  - [ ] discount.apply.total (até 10%)
  - [ ] discount.manager (até 30%)
  - **Validação:** Permissões criadas
  
- [ ] **2.3.3 - Criar DiscountService** (3h)
  - [ ] Validar limite por perfil
  - [ ] Registrar autorização
  - [ ] Calcular desconto (percentual/fixo)
  - **Validação:** Descontos aplicados corretamente
  
- [ ] **2.3.4 - Integrar no Pos::finalize()** (2h)
  - [ ] Validar descontos antes de finalizar
  - [ ] Registrar em audit_logs
  - **Validação:** Desconto registrado e auditado
  
- [ ] **2.3.5 - Testes** (1h)
  - [ ] Testes de limites
  - [ ] Testes de permissões
  - **Validação:** Cobertura > 70%

**Critérios de Conclusão:**
- ✅ Descontos validados por perfil
- ✅ Auditoria completa de descontos

---

### SPRINT 2.4: Sistema Offline Completo (24 horas)
**Responsável:** _____________________  
**Prazo:** _____________________  
**Status:** 🔴 NÃO INICIADO

#### Subtarefas

- [ ] **2.4.1 - Service Worker** (8h)
  - [ ] Criar offline-service-worker.js
  - [ ] Cache de assets (CSS, JS, imagens)
  - [ ] Cache de API responses (produtos, configurações)
  - [ ] Interceptar requests offline
  - **Validação:** PDV funciona offline
  
- [ ] **2.4.2 - IndexedDB Manager** (6h)
  - [ ] Criar offline-manager.js
  - [ ] Armazenar produtos
  - [ ] Armazenar clientes
  - [ ] Armazenar configurações
  - [ ] Fila de requisições pendentes
  - **Validação:** Dados persistem offline
  
- [ ] **2.4.3 - Detecção de conexão** (2h)
  - [ ] Listener navigator.onLine
  - [ ] Ping periódico ao servidor
  - [ ] Badge visual "Modo Offline"
  - **Validação:** Badge aparece ao desconectar
  
- [ ] **2.4.4 - Sincronização** (6h)
  - [ ] Endpoint POST /api/sync/batch
  - [ ] Processar fila ao reconectar
  - [ ] Resolução de conflitos (last-write-wins)
  - [ ] Retry com backoff
  - **Validação:** Vendas offline sincronizam ao reconectar
  
- [ ] **2.4.5 - Testes** (2h)
  - [ ] Simular perda de conexão
  - [ ] Criar venda offline
  - [ ] Reconectar e validar sincronização
  - **Validação:** Fluxo completo offline → online

**Critérios de Conclusão:**
- ✅ PDV funciona sem internet
- ✅ Dados sincronizam automaticamente
- ✅ Conflitos resolvidos corretamente

---

## 🟢 FASE 3: QUALIDADE E REFATORAÇÃO (84 horas)

### SPRINT 3.1: Refatoração de Métodos Longos (16 horas)
**Status:** 🔴 NÃO INICIADO

- [ ] Extrair Pos::finalize() em serviços
  - [ ] SaleFinalizationService::validate()
  - [ ] SaleFinalizationService::processPayments()
  - [ ] SaleFinalizationService::emitFiscalNote()
  - [ ] SaleFinalizationService::updateInventory()
  
- [ ] Extrair Shifts::close() em serviços
  - [ ] ShiftService::calculateTotals()
  - [ ] ShiftService::generateReport()

**Validação:** Métodos < 50 linhas

---

### SPRINT 3.2: Testes Automatizados (40 horas)
**Status:** 🔴 NÃO INICIADO

- [ ] Configurar PHPUnit
- [ ] Testes unitários (20h)
  - [ ] BaseAppModelTest (isolamento)
  - [ ] TefServiceTest
  - [ ] PixServiceTest
  - [ ] DiscountServiceTest
  
- [ ] Testes de integração (15h)
  - [ ] PosApiTest (fluxo completo)
  - [ ] CaixaApiTest
  - [ ] OfflineSyncTest
  
- [ ] Mocking de APIs externas (5h)
  - [ ] Mock TEF
  - [ ] Mock PIX
  
**Validação:** Cobertura > 70%

---

### SPRINT 3.3: Otimizações (20 horas)

- [ ] Implementar cache de produtos (Redis) (8h)
- [ ] Criar TenantAwareTrait (4h)
- [ ] Contingência offline NFC-e (FS-DA) (8h)

**Validação:** Performance melhorada em 30%

---

### SPRINT 3.4: Melhorias de UI/UX (8 horas)

- [ ] Busca rápida de cliente (autocomplete) (3h)
- [ ] Atalhos de teclado (F1-F12) (2h)
- [ ] Modo escuro (3h)

**Validação:** UX validada com usuários

---

## 📈 MÉTRICAS DE QUALIDADE

### Código
- [ ] Cobertura de testes > 70%
- [ ] Métodos < 50 linhas
- [ ] Complexidade ciclomática < 10
- [ ] 0 violations Psalm/PHPStan

### Segurança Multi-Tenant
- [ ] 100% queries filtradas por tenant
- [ ] 0 acessos cross-tenant em logs
- [ ] Todos os endpoints validam ownership

### Performance
- [ ] Tempo de resposta API < 200ms (p95)
- [ ] Tempo de finalização de venda < 3s
- [ ] Uptime > 99.9%

### Documentação
- [ ] README atualizado
- [ ] Swagger/OpenAPI gerado
- [ ] Guia de deploy criado

---

## 🎯 CRITÉRIOS DE ACEITE - PRODUÇÃO

### Bloqueadores (OBRIGATÓRIOS)
- [ ] ✅ TEF funcionando com pelo menos 1 adquirente
- [ ] ✅ PIX com QR Code e webhook
- [ ] ✅ Múltiplas formas de pagamento
- [ ] ✅ Testes automatizados com > 50% cobertura
- [ ] ✅ Nenhum vazamento de dados entre tenants em teste de carga
- [ ] ✅ Backup automático configurado
- [ ] ✅ Monitoramento (logs, erros, performance)

### Desejáveis
- [ ] Sistema offline completo
- [ ] Sangria e suprimento
- [ ] Cobertura de testes > 70%
- [ ] Cache de produtos (Redis)

---

## 📅 CRONOGRAMA SUGERIDO

### Semana 1 (40h)
- Sprint 1.1: TEF (40h)

### Semana 2 (48h)
- Sprint 1.2: Multi-Payment (16h)
- Sprint 1.3: PIX (32h início)

### Semana 3 (32h)
- Sprint 1.3: PIX (32h conclusão)

### Semana 4 (54h)
- Sprint 2.1: Sangria/Suprimento (12h)
- Sprint 2.2: Suspensão (8h)
- Sprint 2.3: Descontos (10h)
- Sprint 2.4: Offline (24h início)

### Semana 5-6 (84h)
- Sprint 2.4: Offline (conclusão)
- Sprint 3.1-3.4: Qualidade e Refatoração

**TOTAL:** 6 semanas

---

## 📝 NOTAS E OBSERVAÇÕES

### Decisões Técnicas

**Data:** _____________________  
**Decisão:** _____________________  
**Justificativa:** _____________________

---

### Bloqueios Identificados

**Data:** _____________________  
**Bloqueio:** _____________________  
**Resolução:** _____________________

---

### Lições Aprendidas

**Data:** _____________________  
**Lição:** _____________________  
**Ação:** _____________________

---

## ✅ SIGN-OFF

### Desenvolvimento
- [ ] Código implementado conforme especificação
- [ ] Testes passando (> 70% cobertura)
- [ ] Code review aprovado
- [ ] Documentação atualizada

**Responsável:** _____________________  
**Data:** _____________________

### QA
- [ ] Testes funcionais passando
- [ ] Testes de segurança passando
- [ ] Testes de carga passando
- [ ] Bugs críticos resolvidos

**Responsável:** _____________________  
**Data:** _____________________

### Product Owner
- [ ] Funcionalidades atendem requisitos
- [ ] UX validada com usuários
- [ ] Pronto para produção

**Responsável:** _____________________  
**Data:** _____________________

---

**Documento de acompanhamento vivo - Atualizar semanalmente**  
**Versão:** 1.0  
**Última atualização:** 01/10/2025

