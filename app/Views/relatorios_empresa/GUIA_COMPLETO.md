# 📊 Módulo de Relatórios Gerenciais - Guia Completo

## 🎯 Visão Geral

Sistema completo de relatórios gerenciais com funcionalidades avançadas para análise de dados do ERP e PDV.

---

## ✅ Funcionalidades Implementadas

### 1. 📊 **Dashboard Principal** (`/relatorios-empresa`)
- Visão geral com métricas do dia/período
- Cards interativos com:
  - Total de vendas
  - Quantidade de vendas
  - Ticket médio
  - Produtos mais vendidos
  - Vendas por forma de pagamento

### 2. 💰 **Relatório de Vendas** (`/relatorios-empresa/vendas`)
**Filtros Disponíveis:**
- Data Início e Fim
- Status (finalizada, pendente, cancelada)
- Tipo de Pagamento (dinheiro, débito, crédito, PIX, etc)
- Cliente específico

**Exportação:**
- ✅ **Excel (.xlsx)** - Planilha formatada com cabeçalhos coloridos
- ✅ **PDF** - Documento profissional em paisagem

### 3. 📦 **Relatório de Produtos** (`/relatorios-empresa/produtos`)
- Lista completa de produtos
- Filtros por nome e código de barras
- Visualização de estoque atual

### 4. 🕐 **Relatório de Turnos** (`/relatorios-empresa/turnos`)
- Controle de abertura/fechamento de caixas
- Filtros por data e status
- Informações de operadores

### 5. 📄 **Relatório Fiscal** (`/relatorios-empresa/fiscal`)
- Consulta de notas fiscais emitidas
- Filtros por período e tipo (NFe/NFCe)

### 6. 🔄 **Comparativo de Períodos** (`/relatorios-empresa/comparativo`)
- Selecione dois períodos para comparar
- Visualize variação percentual de:
  - Total de vendas
  - Quantidade de vendas
  - Ticket médio
- Indicadores visuais (setas verdes/vermelhas)

### 7. 📈 **Evolução Temporal** (`/relatorios-empresa/evolucao`)
- Gráfico interativo (Chart.js)
- Modos de visualização:
  - **Diário** - Últimos 30 dias
  - **Semanal** - Últimas 12 semanas
  - **Mensal** - Últimos 12 meses
- Dois eixos Y (valor em R$ e quantidade)

### 8. 👥 **Clientes Mais Frequentes** (`/relatorios-empresa/clientes`)
- Top 50 clientes por número de compras
- Informações exibidas:
  - Nome e documento (CPF/CNPJ)
  - Total de compras
  - Valor total gasto
  - Ticket médio
  - Data da última compra
- Filtros por período
- Tabela ordenável (DataTables)

### 9. ⚠️ **Alertas de Estoque** (`/relatorios-empresa/alertas-estoque`)
- Monitoramento automático de estoque
- Tipos de alerta:
  - 🔴 **SEM ESTOQUE** - Produto zerado
  - 🟡 **ESTOQUE BAIXO** - Abaixo do mínimo
- Atualização automática ao acessar

### 10. 📧 **Agendamentos de Relatórios** (`/relatorios-empresa/agendamentos`)
**Funcionalidades:**
- Criar agendamentos automáticos
- Configurações disponíveis:
  - Tipo de relatório (vendas, produtos, turnos, fiscal, estoque)
  - Frequência (diário, semanal, mensal)
  - Formato (Excel ou PDF)
  - Horário de envio
  - Múltiplos destinatários (emails separados por vírgula)
- Ativar/desativar agendamentos
- Visualizar próximo envio programado
- Excluir agendamentos

**Comando Cron:**
```bash
# Adicionar ao crontab para executar a cada hora
0 * * * * cd /caminho/do/projeto && php spark reports:process
```

### 11. 🎨 **Dashboard Customizável** (`/relatorios-empresa/customizar`)
**Opções de Personalização:**

**Widgets Disponíveis:**
- 💰 Total de Vendas
- 🎯 Ticket Médio
- 📦 Produtos Mais Vendidos
- 💳 Vendas por Forma de Pagamento
- 🕐 Turnos Ativos
- ⚠️ Alertas de Estoque
- 📈 Gráfico de Evolução
- 👥 Top Clientes

**Temas:**
- Padrão (Azul)
- Escuro
- Verde
- Roxo
- Laranja

**Período Padrão:**
- Hoje
- Últimos 7 dias
- Últimos 30 dias
- Último ano

---

## 📤 Exportação de Relatórios

### Excel (PHPSpreadsheet)
```php
// Acesse qualquer relatório de vendas e clique em "Exportar Excel"
// URL: /relatorios-empresa/exportar-vendas-excel?data_inicio=2025-01-01&data_fim=2025-12-31
```

**Características:**
- Cabeçalhos coloridos (verde)
- Fonte em negrito
- Colunas auto-ajustadas
- Formato profissional

### PDF (TCPDF)
```php
// Acesse qualquer relatório de vendas e clique em "Exportar PDF"
// URL: /relatorios-empresa/exportar-vendas-pdf?data_inicio=2025-01-01&data_fim=2025-12-31
```

**Características:**
- Orientação paisagem (A4)
- Cabeçalho com logo
- Tabela formatada
- Total no rodapé

---

## 🗄️ Estrutura de Banco de Dados

### Tabelas Criadas

#### `report_schedules`
```sql
- id_schedule (PK)
- id_empresa
- id_contador
- report_type (vendas, produtos, turnos, fiscal, estoque)
- frequency (daily, weekly, monthly)
- format (excel, pdf)
- email_recipients (TEXT)
- schedule_time (TIME)
- next_run (DATETIME)
- is_active (BOOLEAN)
- last_sent_at (DATETIME)
- created_at, updated_at
```

#### `dashboard_configs`
```sql
- id_config (PK)
- id_empresa
- id_login
- widgets (JSON)
- layout (default, compact, expanded)
- theme (light, dark)
- created_at, updated_at
```

#### `stock_alerts`
```sql
- id_alert (PK)
- id_empresa
- id_produto
- alert_type (low_stock, out_of_stock)
- threshold (INT)
- current_stock (INT)
- status (active, resolved, ignored)
- notified_at (DATETIME)
- created_at, updated_at
```

---

## 🔧 Bibliotecas Utilizadas

### Frontend
- **Chart.js 3.9.1** - Gráficos interativos
- **DataTables** - Tabelas ordenáveis e pesquisáveis
- **AdminLTE 3** - Template responsivo
- **Font Awesome 5** - Ícones

### Backend (PHP)
- **PHPSpreadsheet** - Geração de Excel
- **TCPDF** - Geração de PDF
- **CodeIgniter 4** - Framework

---

## 📝 Como Usar

### 1. Acessar Relatórios
```
http://erp.local/relatorios
```

### 2. Aplicar Filtros
- Selecione data início e fim
- Escolha status, forma de pagamento, etc
- Clique em "Pesquisar"

### 3. Exportar
- Após filtrar, clique em "Exportar Excel" ou "Exportar PDF"
- O arquivo será baixado automaticamente

### 4. Agendar Relatório
- Acesse "Agendamentos"
- Clique em "Novo Agendamento"
- Preencha os dados:
  - Tipo de relatório
  - Frequência (diário/semanal/mensal)
  - Formato (Excel/PDF)
  - Emails (separados por vírgula)
  - Horário de envio
- Clique em "Salvar"

### 5. Customizar Dashboard
- Acesse "Customizar Dashboard"
- Marque os widgets desejados
- Escolha o tema
- Defina o período padrão
- Clique em "Salvar Configurações"

---

## 🚀 Próximas Melhorias (Opcional)

### Fase 2
- [ ] Gráficos de pizza interativos
- [ ] Relatório de fluxo de caixa
- [ ] Previsão de vendas (IA/ML)
- [ ] Dashboard mobile app

### Fase 3
- [ ] Integração com WhatsApp Business
- [ ] Relatórios de comissões
- [ ] Análise de margem de lucro
- [ ] Relatório de devoluções

---

## 🐛 Troubleshooting

### Erro ao exportar Excel
**Problema:** "Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found"
**Solução:**
```bash
composer require phpoffice/phpspreadsheet
```

### Erro ao exportar PDF
**Problema:** "Class 'TCPDF' not found"
**Solução:**
```bash
composer require tecnickcom/tcpdf
```

### Agendamentos não estão sendo enviados
**Problema:** Emails não chegam
**Verificar:**
1. Configuração de email em `app/Config/Email.php`
2. Cron job configurado corretamente
3. Comando `php spark reports:process` funcionando

### Alertas de estoque não aparecem
**Problema:** Lista vazia
**Solução:**
1. Acesse a página de alertas (atualização automática)
2. Verifique se produtos têm `estoque_minimo` configurado
3. Execute manualmente: `php spark db:seed AlertasEstoque`

---

## 📞 Suporte

Para dúvidas ou problemas:
- 📧 Email: suporte@xfiscal.com
- 📱 WhatsApp: (00) 00000-0000
- 🌐 Site: https://xfiscal.com

---

## 📄 Licença

© 2025 xFiscal ERP - Todos os direitos reservados

---

**Desenvolvido com ❤️ para facilitar sua gestão empresarial!**
