# Módulo de Relatórios Gerenciais

## 📊 Visão Geral

Sistema completo de relatórios gerenciais para empresas (tipo 3) com funcionalidades avançadas de análise de dados, filtros personalizados e visualizações interativas.

## ✨ Funcionalidades

### 1. Dashboard Principal (`/relatorios-empresa`)
- **Estatísticas do Mês Atual**
  - Total de vendas
  - Quantidade de vendas
  - Ticket médio
  - Formas de pagamento utilizadas

- **Gráficos Interativos**
  - Vendas por forma de pagamento (Gráfico de Pizza)
  - Produtos mais vendidos (Gráfico de Barras)

- **Menu de Acesso Rápido**
  - Cards interativos para acessar cada tipo de relatório

### 2. Relatório de Vendas (`/relatorios-empresa/vendas`)
- **Filtros Avançados:**
  - Período (data início e fim)
  - Status (finalizadas, canceladas, rascunho)
  - Forma de pagamento
  - Cliente específico

- **Totalizadores:**
  - Total em vendas
  - Quantidade de vendas
  - Ticket médio
  - Total em descontos
  - Resumo por forma de pagamento

- **Recursos:**
  - Tabela paginada e ordenável (DataTables)
  - Exportação para Excel e PDF
  - Visualização de detalhes da venda

### 3. Relatório de Produtos (`/relatorios-empresa/produtos`)
- **Filtros:**
  - Nome do produto
  - Código de barras

- **Informações Exibidas:**
  - Código do produto
  - Nome
  - Código de barras
  - Valor unitário
  - Estoque atual com indicadores visuais
  - Status (ativo/inativo)

### 4. Relatório de Turnos (`/relatorios-empresa/turnos`)
- **Filtros:**
  - Período
  - Status (aberto/fechado)

- **Informações Exibidas:**
  - Caixa
  - Operador de abertura/fechamento
  - Horários de abertura/fechamento
  - Valores inicial e final
  - Diferença calculada
  - Status visual

### 5. Relatório Fiscal (`/relatorios-empresa/fiscal`)
- **Filtros:**
  - Período de emissão

- **Informações Exibidas:**
  - Tipo de nota (NFe/NFCe)
  - Número e chave
  - Data e hora de emissão
  - Valor da nota
  - Status (autorizada, cancelada, etc.)
  - Download de XML

## 🎨 Design e UX

- Interface moderna e responsiva
- Cards interativos com hover effects
- Cores consistentes com AdminLTE
- Gráficos interativos com Chart.js
- Tabelas com DataTables (ordenação, busca, paginação)
- Indicadores visuais (badges, cores)
- Ícones FontAwesome para melhor comunicação visual

## 🔒 Segurança

- Acesso restrito a usuários tipo 3 (empresas/gerentes)
- Verificação de permissões em todos os métodos
- Filtros de empresa garantem isolamento de dados
- Proteção contra SQL injection via Query Builder

## 📱 Responsividade

Todos os relatórios são totalmente responsivos e funcionam em:
- Desktop
- Tablets
- Smartphones

## 🚀 Tecnologias Utilizadas

- **Backend:** CodeIgniter 4
- **Frontend:** Bootstrap 4, AdminLTE 3
- **Gráficos:** Chart.js 3.9
- **Tabelas:** DataTables
- **Ícones:** FontAwesome 5

## 📈 Próximas Funcionalidades

- [ ] Exportação real para Excel (PHPSpreadsheet)
- [ ] Exportação real para PDF (TCPDF/DOMPDF)
- [ ] Relatórios agendados por email
- [ ] Comparativos entre períodos
- [ ] Gráficos de evolução temporal
- [ ] Relatório de clientes mais frequentes
- [ ] Relatório de produtos com baixo estoque
- [ ] Dashboard customizável

## 📝 Como Usar

1. **Acesse o menu lateral** → "Relatórios Gerenciais"
2. **Dashboard:** Visualize as estatísticas gerais
3. **Escolha um relatório específico** clicando nos cards
4. **Aplique filtros** conforme necessário
5. **Exporte os dados** quando necessário

## 🔧 Manutenção

Para adicionar novos relatórios:

1. Adicione método no controlador `RelatoriosEmpresa.php`
2. Crie a view correspondente em `app/Views/relatorios_empresa/`
3. Adicione a rota em `app/Config/Routes.php`
4. Adicione card no dashboard (opcional)

---

**Desenvolvido para:** ERP xFiscal  
**Versão:** 1.0  
**Data:** Setembro 2025
