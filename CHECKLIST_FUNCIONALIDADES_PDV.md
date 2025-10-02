# ✅ CHECKLIST COMPLETO - FUNCIONALIDADES PDV

**Sistema:** xFiscal ERP - PDV Multi-Tenant SaaS  
**Objetivo:** Guia completo de funcionalidades para PDV profissional  
**Status:** 🟡 70% Implementado  

---

## 📊 LEGENDA

```
✅ Implementado e testado
🟡 Parcialmente implementado
🔴 Não implementado
⚠️ Requer atenção/refatoração
🚀 Prioridade ALTA
💡 Melhoria futura
```

---

## 1️⃣ VENDAS E OPERAÇÕES

### 1.1 Gestão de Carrinho
- [x] **Adicionar produto ao carrinho** ✅
  - Status: Implementado (`Cart.php::create()`)
  - Modelo: `ProdutoProvisorioModel`
  - Validação multi-tenant: ✅
  - Testes: 🔴 Faltam

- [x] **Remover item do carrinho** ✅
  - Status: Implementado (`Cart.php::delete()`)
  - Validação multi-tenant: ✅
  - Testes: 🔴 Faltam

- [x] **Atualizar quantidade de item** ✅
  - Status: Implementado (`Cart.php::update()`)
  - Campos: quantidade, desconto, observação
  - Validação: quantidade >= 1
  - Testes: 🔴 Faltam

- [x] **Limpar carrinho completo** ✅
  - Status: Implementado (`Cart.php::clear()`)
  - Uso: Após finalizar venda
  - Validação multi-tenant: ✅

- [ ] **Buscar produto por código de barras** 🔴 🚀
  - Endpoint: `GET /api/products/barcode/{code}`
  - Resposta: Produto com dados fiscais completos
  - Cache: Sim (30 minutos)
  - Validação: Código único por tenant
  - Prioridade: ALTA

- [ ] **Adicionar produto sem cadastro (genérico)** 🔴
  - Uso: Produtos eventuais
  - Campos: nome, valor, quantidade
  - NCM: Usar padrão (00000000)
  - CFOP: Usar padrão por estado

### 1.2 Descontos
- [x] **Desconto em item individual** 🟡
  - Status: Campo existe, falta validação
  - Modelo: `pos_sale_items.desconto`
  - Tipos: Valor fixo ou percentual
  - Limite: Configurável por perfil
  - Validação: 🔴 Faltando

- [x] **Desconto no total da venda** 🟡
  - Status: Campo existe, falta validação
  - Modelo: `pos_sales.discount`
  - Tipos: Valor fixo, percentual, cupom
  - Limite: Configurável por perfil
  - Autorização: Gerente (>10%)
  - Validação: 🔴 Faltando

- [ ] **Sistema de cupons de desconto** 🔴 💡
  - Tabela: `discount_coupons`
  - Campos: código, tipo, valor, validade, uso_único
  - Validação: Data validade, limite de uso
  - Multi-tenant: ✅ Obrigatório
  - Prioridade: MÉDIA

- [ ] **Histórico de descontos aplicados** 🔴 🚀
  - Tabela: `discount_authorizations`
  - Campos: id_venda, tipo, valor, autorizado_por, motivo
  - Auditoria: ✅ Completa
  - Prioridade: ALTA (compliance)

### 1.3 Suspensão e Retomada
- [ ] **Suspender venda (deixar em aberto)** 🔴 🚀
  - Status: `suspended`
  - Usar: Cliente saiu para buscar dinheiro
  - Limite: 10 vendas suspensas simultâneas
  - Timeout: 2 horas (configurable)
  - Prioridade: ALTA

- [ ] **Listar vendas suspensas** 🔴 🚀
  - Endpoint: `GET /api/pos/suspended`
  - Filtros: Data, operador, valor
  - Ordenação: Mais recente primeiro
  - Prioridade: ALTA

- [ ] **Retomar venda suspensa** 🔴 🚀
  - Endpoint: `POST /api/pos/{id}/resume`
  - Validar: Apenas 1 venda ativa por caixa
  - Carregar: Itens de `pos_sale_items`
  - Prioridade: ALTA

- [ ] **Cancelar venda suspensa** 🔴
  - Endpoint: `POST /api/pos/{id}/cancel-suspended`
  - Motivo: Obrigatório
  - Estorno: Não (pois não finalizou)
  - Auditoria: ✅

### 1.4 Finalização
- [x] **Finalizar venda com pagamento único** ✅
  - Status: Implementado (`Pos.php::finalize()`)
  - Fluxos: Cash, TEF, PIX
  - Validação: Turno aberto
  - Baixa estoque: ✅ Automática
  - NFC-e: ⚠️ Opcional (emite se `emit_nfce=true`)

- [ ] **Finalizar com múltiplos pagamentos** 🔴 🚀
  - Endpoint: `POST /api/pos/{id}/finalize` (modificar)
  - Payload: `payments: [{type, amount, installments}]`
  - Validação: Soma = total da venda
  - Processamento: Sequencial (falha rollback)
  - Prioridade: CRÍTICA

- [x] **Calcular troco** ✅
  - Campo: `pos_sales.change_amount`
  - Cálculo: paid_amount - total
  - Exibição: UI do PDV

- [ ] **Emitir comprovante não fiscal** 🟡
  - Status: Implementado (`Pos.php::receiptNonFiscal()`)
  - Formato: HTML (impressão térmica)
  - Layout: 80mm (padrão)
  - Melhoria: 🔴 Adicionar logo da empresa

### 1.5 Cancelamento
- [x] **Cancelar venda finalizada** ✅
  - Status: Implementado (`Pos.php::cancel()`)
  - Requisitos: Motivo obrigatório
  - Estorno: Estoque ✅ + Financeiro ✅
  - NFC-e: ✅ Cancela na SEFAZ se emitida
  - Validação multi-tenant: ✅

- [ ] **Cancelar item antes de finalizar** 🔴 🚀
  - Endpoint: `DELETE /api/cart/{id_item}`
  - Usar: Erro de lançamento
  - Auditoria: ✅
  - Prioridade: ALTA

- [ ] **Prazo para cancelamento** 🔴 💡
  - Regra: Até 24h após finalização
  - Validação: Data venda vs data atual
  - Exceção: Gerente pode cancelar sempre
  - Prioridade: MÉDIA

### 1.6 Clientes
- [x] **Vincular cliente à venda** 🟡
  - Campo: `pos_sales.id_cliente`
  - Status: Campo existe
  - Busca: 🔴 Falta autocomplete no front-end
  - CPF/CNPJ: ✅ Validação

- [ ] **Busca rápida de cliente (autocomplete)** 🔴 🚀
  - Endpoint: `GET /api/clients/search?q={query}`
  - Buscar por: Nome, CPF, CNPJ, Telefone
  - Limite: 10 resultados
  - Cache: Sim (5 minutos)
  - Prioridade: ALTA

- [ ] **Cadastro rápido de cliente no PDV** 🔴 💡
  - Modal: Campos essenciais (nome, CPF, telefone)
  - Validação: CPF único por tenant
  - Completar depois: Sim
  - Prioridade: MÉDIA

- [ ] **Histórico de compras do cliente** 🔴 💡
  - Endpoint: `GET /api/clients/{id}/purchases`
  - Dados: Últimas 10 compras, ticket médio
  - Uso: Fidelização
  - Prioridade: BAIXA

---

## 2️⃣ PAGAMENTOS

### 2.1 Dinheiro
- [x] **Pagamento em dinheiro** ✅
  - Status: Implementado
  - Cálculo de troco: ✅
  - Validação: paid_amount >= total
  - Multi-tenant: ✅

### 2.2 TEF (Cartões)
- [ ] **Integração TEF - Cielo** 🔴 🚀
  - Status: Não implementado
  - Operações: Autorizar, Confirmar, Cancelar
  - Parcelamento: Até 12x (configurável)
  - Timeout: 60s
  - Retry: 3 tentativas
  - Prioridade: CRÍTICA
  - Estimativa: 40 horas

- [ ] **Integração TEF - Stone** 🔴 💡
  - Status: Não implementado
  - API: REST
  - Prioridade: BAIXA (após Cielo)

- [ ] **Integração TEF - Rede** 🔴 💡
  - Status: Não implementado
  - Prioridade: BAIXA (após Cielo)

- [ ] **Parcelamento configurável** 🔴 🚀
  - Campo: `empresas.tef_max_parcelas`
  - Padrão: 12x
  - Validação: Débito não parcela
  - Prioridade: ALTA (junto com TEF)

- [ ] **Estorno TEF** 🔴 🚀
  - Uso: Cancelamento de venda
  - Prazo: Até 23:59 do dia (mesmo dia = cancelamento, dia seguinte = estorno)
  - Log: Auditoria completa
  - Prioridade: ALTA

- [ ] **Retry automático em falha TEF** 🔴 💡
  - Tentativas: 3x
  - Backoff: Exponencial (1s, 2s, 4s)
  - Fallback: Sugerir outro método
  - Prioridade: MÉDIA

### 2.3 PIX
- [ ] **Gerar QR Code PIX** 🔴 🚀
  - Provedor: Mercado Pago ou PagSeguro
  - Tipo: QR Code dinâmico
  - Expiração: 5 minutos (configurável)
  - Formato: BR Code + imagem Base64
  - Prioridade: CRÍTICA
  - Estimativa: 32 horas

- [ ] **Webhook de confirmação PIX** 🔴 🚀
  - Endpoint: `POST /api/pix/webhook/{id_empresa}`
  - Validação: Assinatura HMAC
  - Ação: Finalizar venda automaticamente
  - Notificação: Email/SMS ao cliente
  - Prioridade: CRÍTICA

- [ ] **Polling de status PIX** 🔴 💡
  - Uso: Fallback se webhook falhar
  - Intervalo: 5s por 2 minutos
  - Endpoint: `GET /api/pos/{id}/pix/status`
  - Prioridade: MÉDIA

- [ ] **Cancelar PIX expirado** 🔴 🚀
  - Cron: A cada 1 minuto
  - Ação: Marcar status = `expired`
  - Limpar: QR Codes > 10 minutos
  - Prioridade: ALTA

### 2.4 Voucher/Vale
- [ ] **Aceitar vouchers (Ticket, Sodexo, etc)** 🔴 💡
  - Validação: Número do voucher
  - Tipos: Refeição, Alimentação, Combustível
  - Regras: Produtos elegíveis por categoria
  - Prioridade: BAIXA

### 2.5 Múltiplos Pagamentos
- [ ] **Dividir pagamento em múltiplas formas** 🔴 🚀
  - Exemplo: R$ 100 dinheiro + R$ 150 cartão
  - Validação: Soma exata = total
  - Processamento: Transacional (tudo ou nada)
  - Tabela: `pos_sale_payments`
  - Prioridade: CRÍTICA
  - Estimativa: 16 horas

- [ ] **Cancelar pagamento individual** 🔴 💡
  - Uso: Erro em um dos pagamentos
  - Ação: Estornar apenas aquele pagamento
  - Reprocessar: Substituir por outro
  - Prioridade: MÉDIA

---

## 3️⃣ CAIXA E TURNOS

### 3.1 Abertura de Caixa
- [x] **Abrir caixa com valor inicial** ✅
  - Status: Implementado (`Caixa.php::abrir()`)
  - Validação: Apenas 1 caixa aberto por tenant
  - Lock: FOR UPDATE (evita race condition)
  - Multi-tenant: ✅

- [ ] **Conferir valor inicial** 🔴 💡
  - UI: Modal com campos por nota
  - Exemplo: 5x R$ 100, 10x R$ 50, etc
  - Total: Calculado automaticamente
  - Prioridade: BAIXA

### 3.2 Fechamento de Caixa
- [x] **Fechar caixa com conferência** ✅
  - Status: Implementado (`Caixa.php::fechar()`)
  - Cálculo: Automático por forma de pagamento
  - Diferença: Sobra/Falta registrada
  - Relatório: ✅ Gerado

- [ ] **Conferir valor por nota** 🔴 💡
  - UI: Formulário detalhado
  - Campos: Quantidade de cada nota/moeda
  - Total: Calculado
  - Diferença: Exibir claramente
  - Prioridade: MÉDIA

- [ ] **Justificar diferença de caixa** 🔴 💡
  - Obrigatório se: |diferença| > R$ 5
  - Campo: Motivo (texto)
  - Auditoria: ✅
  - Prioridade: MÉDIA

### 3.3 Movimentações
- [ ] **Sangria (retirada de dinheiro)** 🔴 🚀
  - Endpoint: `POST /api/caixa/sangria`
  - Campos: Valor, motivo, autorizado_por
  - Validação: Caixa aberto
  - Tabela: `caixa_movimentacoes`
  - Prioridade: ALTA
  - Estimativa: 12 horas

- [ ] **Suprimento (entrada de dinheiro)** 🔴 🚀
  - Endpoint: `POST /api/caixa/suprimento`
  - Uso: Troco insuficiente
  - Campos: Valor, motivo
  - Prioridade: ALTA

- [ ] **Histórico de movimentações** 🔴 🚀
  - Endpoint: `GET /api/caixa/{id}/movimentacoes`
  - Filtros: Tipo, período
  - Exibição: Timeline
  - Prioridade: ALTA

### 3.4 Múltiplos Caixas
- [x] **Suporte a múltiplos caixas por loja** ✅
  - Tabela: `cash_registers`
  - Campos: name, location, status
  - Multi-tenant: ✅
  - Uso: Lojas com vários PDVs

- [ ] **Relatório consolidado de caixas** 🔴 💡
  - Endpoint: `GET /api/cash-registers/report`
  - Agrupamento: Por caixa, por operador
  - Período: Dia, semana, mês
  - Prioridade: BAIXA

---

## 4️⃣ ESTOQUE

### 4.1 Baixa Automática
- [x] **Baixa de estoque ao finalizar venda** ✅
  - Status: Implementado (`EstoqueService::darBaixaPorVenda()`)
  - Transacional: ✅
  - Movimentações: ✅ Registradas
  - Multi-tenant: ✅

- [x] **Estorno de estoque ao cancelar venda** ✅
  - Status: Implementado (`Pos::cancel()`)
  - Movimentação: Tipo `entrada`
  - Motivo: PDV cancelamento
  - Multi-tenant: ✅

### 4.2 Validações
- [ ] **Alertar estoque insuficiente** 🔴 🚀
  - Momento: Ao adicionar ao carrinho
  - Ação: Exibir aviso, permitir continuar
  - Configurável: Bloquear venda se estoque zero
  - Prioridade: ALTA

- [x] **Alertas de estoque baixo** ✅
  - Status: Implementado (relatório)
  - Endpoint: `/relatorios-empresa/alertas-estoque`
  - Critério: estoque < estoque_minimo
  - Multi-tenant: ✅

### 4.3 Movimentações
- [x] **Histórico de movimentações** ✅
  - Tabela: `inventory_movements`
  - Campos: tipo, quantidade, motivo, id_pos_sale
  - Multi-tenant: ✅

- [ ] **Reserva de estoque ao adicionar no carrinho** 🔴 💡
  - Objetivo: Evitar overselling
  - Timeout: 10 minutos
  - Liberar: Ao remover do carrinho ou timeout
  - Prioridade: BAIXA (complexo)

- [ ] **Transferência entre lojas** 🔴 💡
  - Uso: Multi-loja por tenant
  - Validação: Aprovação gerente
  - Rastreamento: Completo
  - Prioridade: BAIXA

---

## 5️⃣ NFC-e / NF-e

### 5.1 NFC-e (Cupom Fiscal)
- [x] **Emitir NFC-e** 🟡
  - Status: Implementado (`NFCe.php`)
  - Biblioteca: NFePHP
  - Ambiente: Homologação ✅ / Produção ⚠️
  - Simulação: ✅ (teste)
  - Contingência: 🔴 Faltando

- [x] **Cancelar NFC-e** ✅
  - Status: Implementado (`Pos::cancel()`)
  - Prazo: Até 24h
  - Justificativa: Obrigatória (min 15 caracteres)
  - SEFAZ: ✅ Integrado

- [ ] **Contingência offline (FS-DA)** 🔴 🚀
  - Uso: Queda de SEFAZ
  - Armazenar: XMLs localmente
  - Transmitir: Ao voltar online
  - Prazo: Até 7 dias
  - Prioridade: ALTA

- [ ] **Inutilização de numeração** 🔴 💡
  - Uso: Falha sequencial (ex: 101, 102, 105 - inutilizar 103-104)
  - Validação: Automática
  - Prioridade: MÉDIA

- [x] **DANFE (impressão)** ✅
  - Status: Implementado (`Pos::receiptHtml()`)
  - Formato: HTML
  - Layouts: Térmica 80mm ✅ / A4 ✅
  - QR Code: ✅ Incluído

### 5.2 NF-e (Nota Fiscal Eletrônica)
- [ ] **Emitir NF-e** 🔴 💡
  - Uso: Vendas para outras empresas (B2B)
  - Diferença NFC-e: Mais campos (transportadora, volumes)
  - Biblioteca: NFePHP (mesma)
  - Prioridade: BAIXA (PDV é B2C)

### 5.3 Impostos
- [ ] **Cálculo automático de ICMS** 🔴 💡
  - Baseado em: NCM, CFOP, Estado origem/destino
  - Tabelas: Alíquotas por UF
  - Atualização: Manual ou API
  - Prioridade: BAIXA (geralmente fixo)

- [ ] **Cálculo de PIS/COFINS** 🔴 💡
  - Regimes: Lucro Real, Presumido, Simples Nacional
  - Fonte: Configuração da empresa
  - Prioridade: BAIXA

---

## 6️⃣ OFFLINE

### 6.1 Detecção
- [ ] **Detectar perda de conexão** 🔴 🚀
  - Método: `navigator.onLine` + ping periódico
  - Intervalo: 10 segundos
  - UI: Badge "Modo Offline" (vermelho)
  - Prioridade: ALTA
  - Estimativa: 24 horas

- [ ] **Notificar usuário** 🔴 🚀
  - Toast: "Você está offline. Vendas serão sincronizadas quando voltar online."
  - Persistente: Badge no topo
  - Prioridade: ALTA

### 6.2 Armazenamento Local
- [ ] **Service Worker para cache de assets** 🔴 🚀
  - Arquivos: CSS, JS, imagens
  - Estratégia: Cache-first
  - Versionamento: Por hash
  - Prioridade: ALTA

- [ ] **IndexedDB para dados** 🔴 🚀
  - Armazenar: Produtos, clientes, configurações
  - Atualizar: Ao iniciar (se online)
  - TTL: 24 horas
  - Prioridade: ALTA

- [x] **Outbox para operações pendentes** 🟡
  - Status: Implementado (`OutboxTrait`)
  - Tabela: `outbox`
  - Campos: operation, table_name, data
  - Sincronização: 🔴 Automática faltando

### 6.3 Sincronização
- [ ] **Sincronizar ao reconectar** 🔴 🚀
  - Trigger: Evento `online`
  - Endpoint: `POST /api/sync/batch`
  - Ordem: Pagamentos > Vendas > Estoque
  - Retry: Exponential backoff
  - Prioridade: ALTA

- [ ] **Resolução de conflitos** 🔴 💡
  - Estratégia: Last-write-wins (simples)
  - Avançado: Merge inteligente
  - Auditoria: ✅ Log de conflitos
  - Prioridade: MÉDIA

- [ ] **Notificar sucesso/falha** 🔴 🚀
  - Sucesso: Toast "X vendas sincronizadas"
  - Falha: Modal "Erro ao sincronizar: [detalhe]"
  - Retry manual: Botão
  - Prioridade: ALTA

---

## 7️⃣ RELATÓRIOS

### 7.1 Vendas
- [x] **Relatório de vendas por período** ✅
  - Status: 100% implementado
  - Endpoint: `/relatorios-empresa/vendas`
  - Filtros: Data, status, pagamento
  - Exportação: Excel ✅ PDF ✅
  - Multi-tenant: ✅

- [x] **Produtos mais vendidos** ✅
  - Status: Implementado
  - Endpoint: `/relatorios-empresa/produtos`
  - Agrupamento: Quantidade, Valor
  - Multi-tenant: ✅

- [x] **Vendas por forma de pagamento** ✅
  - Status: Implementado (dashboard)
  - Gráfico: Pizza
  - Multi-tenant: ✅

### 7.2 Caixa
- [x] **Relatório de turnos** ✅
  - Status: Implementado
  - Endpoint: `/relatorios-empresa/turnos`
  - Dados: Abertura, fechamento, diferença
  - Multi-tenant: ✅

- [ ] **Relatório de sangrias/suprimentos** 🔴 🚀
  - Depende: Implementação de sangria/suprimento
  - Agrupamento: Por dia, por usuário
  - Prioridade: ALTA

### 7.3 Performance
- [x] **Ticket médio** ✅
  - Status: Implementado (dashboard)
  - Cálculo: Total vendas / Quantidade vendas
  - Multi-tenant: ✅

- [x] **Vendas por operador** ✅
  - Status: Implementado (relatório geral)
  - Ranking: Top vendedores
  - Multi-tenant: ✅

### 7.4 Clientes
- [x] **Clientes mais frequentes** ✅
  - Status: Implementado
  - Endpoint: `/relatorios-empresa/clientes`
  - Top: 50 clientes
  - Multi-tenant: ✅

- [ ] **Relatório de comissões** 🔴 💡
  - Uso: Vendedores comissionados
  - Cálculo: Percentual sobre vendas
  - Configuração: Por vendedor
  - Prioridade: BAIXA

---

## 8️⃣ CONFIGURAÇÕES

### 8.1 Perfis e Permissões
- [ ] **Perfil Operador (permissões limitadas)** 🔴 🚀
  - Pode: Vender, visualizar estoque
  - Não pode: Descontos > 5%, cancelar vendas, sangria
  - Prioridade: ALTA

- [ ] **Perfil Gerente (permissões amplas)** 🔴 🚀
  - Pode: Tudo do operador + descontos altos, cancelamentos, sangria
  - Prioridade: ALTA

- [ ] **Perfil Administrador (acesso total)** 🔴 🚀
  - Pode: Tudo + configurações, relatórios gerenciais
  - Prioridade: ALTA

### 8.2 Empresa
- [ ] **Configurar credenciais TEF** 🔴 🚀
  - Campos: Adquirente, Merchant ID, Merchant Key
  - Criptografia: ✅ Merchant Key deve ser criptografado
  - Prioridade: ALTA

- [ ] **Configurar credenciais PIX** 🔴 🚀
  - Campos: Provedor, Access Token, Webhook Secret
  - Prioridade: ALTA

- [x] **Certificado digital (NFC-e)** ✅
  - Status: Implementado
  - Upload: ✅
  - Senha: ✅ (criptografada)
  - Multi-tenant: ✅

### 8.3 PDV
- [ ] **Configurar impressora térmica** 🔴 💡
  - Tipo: USB, Rede, Bluetooth
  - Modelo: ESC/POS compatível
  - Teste: Imprimir teste
  - Prioridade: MÉDIA

- [ ] **Layout de recibo** 🔴 💡
  - Logo: Upload
  - Mensagem rodapé: Texto personalizável
  - QR Code: Opcional
  - Prioridade: BAIXA

- [ ] **Atalhos de teclado** 🔴 💡
  - F1: Buscar produto
  - F2: Adicionar cliente
  - F3: Aplicar desconto
  - F4: Suspender venda
  - F5: Finalizar (dinheiro)
  - F6-F8: Formas de pagamento
  - F9: Cancelar item
  - F10: Fechar caixa
  - Prioridade: MÉDIA

---

## 9️⃣ AUDITORIA E SEGURANÇA

### 9.1 Logs
- [x] **Log de operações críticas** 🟡
  - Status: Parcialmente implementado
  - Incluir: tenant_id, usuário, IP, data/hora
  - Operações: Vendas, cancelamentos, descontos, sangrias
  - Melhorias: ⚠️ Padronizar formato (JSON estruturado)

- [ ] **Log de acessos cross-tenant (tentativas)** 🔴 🚀
  - Objetivo: Detectar ataques
  - Ação: Bloquear após 3 tentativas
  - Alerta: Email para admin
  - Prioridade: ALTA (segurança)

### 9.2 Auditoria
- [ ] **Tabela de auditoria** 🔴 🚀
  - Tabela: `audit_logs`
  - Campos: event_type, resource_type, resource_id, before_data, after_data
  - Retenção: 2 anos
  - Prioridade: ALTA (compliance)

- [ ] **Relatório de auditoria** 🔴 💡
  - Filtros: Usuário, período, tipo de evento
  - Exportação: Excel, PDF
  - Acesso: Apenas administradores
  - Prioridade: MÉDIA

### 9.3 Backup
- [ ] **Backup automático diário** 🔴 🚀
  - Cron: 03:00 AM
  - Retenção: 30 dias
  - Isolamento: Por tenant
  - Localização: AWS S3 ou similar
  - Prioridade: CRÍTICA (compliance)

- [ ] **Restore de backup** 🔴 💡
  - UI: Interface administrativa
  - Validação: Apenas admin
  - Teste: Mensal
  - Prioridade: ALTA

---

## 🔟 UX E INTERFACE

### 10.1 Responsividade
- [ ] **PDV responsivo (tablet)** 🔴 💡
  - Telas: 10" - 13"
  - Touch: Otimizado
  - Prioridade: MÉDIA

- [ ] **PDV Mobile (smartphone)** 🔴 💡
  - Uso: Vendedores externos
  - Limitações: Sem impressora
  - Prioridade: BAIXA

### 10.2 Acessibilidade
- [ ] **Atalhos de teclado** 🔴 💡
  - Ver seção 8.3
  - Documentação: Exibir na tela (F1)
  - Prioridade: MÉDIA

- [ ] **Leitor de código de barras USB** 🔴 🚀
  - Detecção: Automática (input rápido)
  - Formato: EAN-13, EAN-8, Code-128
  - Prioridade: ALTA

- [ ] **Modo escuro** 🔴 💡
  - Toggle: Sim
  - Persistir: LocalStorage
  - Prioridade: BAIXA

### 10.3 Performance
- [ ] **Lazy loading de produtos** 🔴 💡
  - Carregar: 50 produtos por vez
  - Scroll infinito: Sim
  - Prioridade: MÉDIA

- [ ] **Cache de imagens** 🔴 💡
  - Service Worker: Sim
  - Fallback: Imagem placeholder
  - Prioridade: BAIXA

---

## 📊 RESUMO POR PRIORIDADE

### 🔴 CRÍTICAS (Bloqueadores)
1. TEF - Integração Cielo (40h)
2. PIX - QR Code + Webhook (32h)
3. Múltiplos Pagamentos (16h)
4. Backup Automático (4h)

**TOTAL CRÍTICO:** 92 horas

---

### 🚀 ALTAS (Essenciais)
1. Sangria e Suprimento (12h)
2. Suspensão/Retomada de Vendas (8h)
3. Descontos com Validação (10h)
4. Sistema Offline Completo (24h)
5. Busca Rápida Cliente (4h)
6. Busca Produto por Código de Barras (4h)
7. Alertar Estoque Insuficiente (2h)
8. Contingência NFC-e (16h)
9. Histórico de Descontos (4h)
10. Perfis e Permissões (12h)
11. Log de Tentativas Cross-Tenant (4h)
12. Tabela de Auditoria (8h)
13. Leitor Código de Barras (4h)

**TOTAL ALTO:** 112 horas

---

### 💡 MELHORIAS (Desejáveis)
- Sistema de Cupons
- Cancelar Item Antes de Finalizar
- Prazo para Cancelamento
- Cadastro Rápido Cliente
- Histórico Compras Cliente
- Parcelamento TEF Avançado
- Retry Automático TEF
- Polling PIX
- Vouchers
- Conferir Valor Inicial/Final Detalhado
- Justificar Diferença Caixa
- Relatório Consolidado Caixas
- Reserva de Estoque
- Transferência Entre Lojas
- Inutilização NFC-e
- NF-e (B2B)
- Cálculo ICMS/PIS/COFINS
- Relatório Comissões
- Impressora Térmica Config
- Layout Recibo Personalizado
- PDV Responsivo/Mobile
- Modo Escuro
- Lazy Loading

**TOTAL MELHORIAS:** ~150 horas

---

## 🎯 ROADMAP SUGERIDO

### Fase 1 - Produção Mínima (2 semanas)
```
✅ TEF (40h)
✅ PIX (32h)
✅ Multi-Payment (16h)
✅ Backup (4h)
───────────────
TOTAL: 92h
```

### Fase 2 - Funcionalidades Essenciais (2-3 semanas)
```
✅ Sangria/Suprimento (12h)
✅ Suspensão/Retomada (8h)
✅ Descontos Validados (10h)
✅ Offline (24h)
✅ Busca Cliente (4h)
✅ Busca Produto Barcode (4h)
✅ Perfis/Permissões (12h)
✅ Auditoria (12h)
───────────────────────────
TOTAL: 86h
```

### Fase 3 - Melhorias (4-6 semanas)
```
✅ Todas as melhorias
TOTAL: ~150h
```

**TOTAL GERAL:** ~328 horas (~8 semanas com 2 devs)

---

**Versão:** 1.0  
**Última atualização:** 01/10/2025  
**Mantido por:** Time xFiscal ERP

