# 🏢 Sistema Multi-Tenant de Relatórios - Guia Completo

## 📋 Visão Geral

O sistema de relatórios foi desenvolvido com **arquitetura multi-tenant**, onde cada empresa tem seus próprios agendamentos, configurações e dados completamente isolados.

---

## 🔒 Isolamento de Dados

### Como Funciona

Cada empresa (`id_empresa`) possui:
- ✅ **Agendamentos próprios** - Não compartilha com outras empresas
- ✅ **Configurações de dashboard** - Personalizadas por empresa e usuário
- ✅ **Alertas de estoque** - Apenas dos produtos da empresa
- ✅ **Relatórios isolados** - Dados apenas da própria empresa

### Estrutura de Isolamento

```sql
-- Todas as queries incluem filtro por empresa
WHERE id_empresa = :id_empresa_logada

-- Exemplos:
report_schedules   -> WHERE id_empresa = 3
dashboard_configs  -> WHERE id_empresa = 3
stock_alerts       -> WHERE id_empresa = 3
pos_sales          -> WHERE id_empresa = 3
```

---

## 🚀 Setup Automático para Novas Empresas

### O que Acontece Automaticamente

Quando uma nova empresa é cadastrada, o sistema **automaticamente**:

#### 1. ✅ Cria 3 Agendamentos Padrão

**a) Relatório de Vendas Diário** 📊
- **Tipo:** Vendas
- **Frequência:** Diário
- **Horário:** 08:00
- **Formato:** Excel
- **Status:** Ativo
- **Email:** Email da empresa cadastrada

**b) Alerta de Estoque Semanal** ⚠️
- **Tipo:** Estoque
- **Frequência:** Semanal (toda segunda-feira)
- **Horário:** 09:00
- **Formato:** Excel
- **Status:** Ativo
- **Email:** Email da empresa cadastrada

**c) Relatório Fiscal Mensal** 📄
- **Tipo:** Fiscal
- **Frequência:** Mensal (dia 1)
- **Horário:** 10:00
- **Formato:** PDF
- **Status:** **Inativo** (empresa ativa se desejar)
- **Email:** Email da empresa cadastrada

#### 2. ✅ Cria Configuração de Dashboard Padrão

**Widgets Ativos:**
- 💰 Total de Vendas
- 🎯 Ticket Médio
- 📦 Produtos Mais Vendidos
- 💳 Vendas por Pagamento
- 📈 Gráfico de Evolução

**Configurações:**
- **Tema:** Padrão (Azul)
- **Layout:** Default
- **Período:** Últimos 30 dias

---

## ⚙️ Código de Setup Automático

### Arquivo: `app/Libraries/EmpresaSetupService.php`

```php
public function setupNovaEmpresa($idEmpresa, $idContador, $emailEmpresa, $idLogin = null)
{
    // 1. Criar agendamentos padrão
    $this->criarAgendamentosPadrao($idEmpresa, $idContador, $emailEmpresa);

    // 2. Criar configuração de dashboard padrão
    if ($idLogin) {
        $this->criarDashboardPadrao($idEmpresa, $idLogin);
    }

    return true;
}
```

### Integração no Cadastro

**Arquivo:** `app/Controllers/Empresas.php` (linha ~330)

```php
// Após criar a empresa...
try {
    $setupService = new \App\Libraries\EmpresaSetupService();
    $emailEmpresa = $dados['usuario'] ?? 'noreply@empresa.com';
    
    $setupService->setupNovaEmpresa(
        $novoIdEmpresa,
        (int) $this->id_contador,
        $emailEmpresa,
        $id_login
    );
    
    log_message('info', "✓ Agendamentos automáticos criados para empresa #{$novoIdEmpresa}");
} catch (\Throwable $e) {
    // Não bloqueia a criação da empresa
}
```

---

## 🔄 Processamento de Agendamentos (Cron Job)

### Comando CLI

```bash
php spark reports:process
```

### Como Funciona

1. **Busca TODOS os agendamentos** de **TODAS as empresas** que estão:
   - ✅ Ativos (`is_active = 1`)
   - ✅ Com horário de envio atingido (`next_run <= NOW()`)

2. **Processa cada agendamento:**
   - Gera o relatório específico da empresa
   - Envia por email para os destinatários
   - Atualiza `last_sent_at`
   - Calcula e atualiza `next_run`

3. **Log detalhado:**
```
Empresa #3 | Agendamento #15
Tipo: VENDAS | Formato: EXCEL
  ✓ Relatório gerado
  ✓ Email enviado para: empresa@exemplo.com
  ✓ Próximo envio: 01/10/2025 08:00
✓ SUCESSO!
```

### Configuração do Cron

#### Linux/Mac
```bash
# Executar a cada hora
0 * * * * cd /var/www/erp && php spark reports:process >> /var/log/reports.log 2>&1

# Executar a cada 30 minutos
*/30 * * * * cd /var/www/erp && php spark reports:process

# Executar todos os dias às 8h
0 8 * * * cd /var/www/erp && php spark reports:process
```

#### Windows (Task Scheduler)
```powershell
# Criar tarefa agendada
schtasks /create /tn "ERP Reports" /tr "php C:\xampp\htdocs\erp.local\spark reports:process" /sc hourly

# Ou via interface gráfica:
# 1. Abrir "Agendador de Tarefas"
# 2. Criar Tarefa Básica
# 3. Nome: "ERP Reports"
# 4. Gatilho: Por hora
# 5. Ação: Iniciar programa
# 6. Programa: C:\xampp\php\php.exe
# 7. Argumentos: C:\xampp\htdocs\erp.local\spark reports:process
```

---

## 👥 Gerenciamento por Empresa

### Cada Empresa Pode:

#### 1. Visualizar Seus Agendamentos
```
URL: /relatorios-empresa/agendamentos
Filtra automaticamente: WHERE id_empresa = {empresa_logada}
```

#### 2. Criar Novos Agendamentos
- Escolhe tipo de relatório
- Define frequência e horário
- Adiciona múltiplos emails
- Ativa/desativa quando quiser

#### 3. Editar Agendamentos Existentes
- Alterar emails
- Mudar horário
- Trocar formato (Excel/PDF)
- Ativar/desativar

#### 4. Excluir Agendamentos
- Remove apenas os próprios agendamentos
- Não afeta outras empresas

---

## 🔐 Segurança e Isolamento

### Garantias de Segurança

```php
// TODAS as queries incluem filtro de empresa
$this->reportScheduleModel
    ->where('id_empresa', $this->id_empresa)  // ← SEMPRE presente
    ->findAll();

$this->dashboardConfigModel
    ->where('id_empresa', $this->id_empresa)  // ← SEMPRE presente
    ->where('id_login', $idLogin)
    ->first();

$db->table('pos_sales')
    ->where('id_empresa', $this->id_empresa)  // ← SEMPRE presente
    ->get();
```

### Não é Possível:
- ❌ Empresa A ver agendamentos da Empresa B
- ❌ Empresa A editar configurações da Empresa B
- ❌ Empresa A ver relatórios da Empresa B
- ❌ Acessar dados sem estar logado em uma empresa

---

## 📊 Exemplo Prático

### Cenário: 3 Empresas no Sistema

#### Empresa #1 - Loja ABC
```
Agendamentos:
- Vendas diárias (08:00) → abc@loja.com
- Estoque semanal (09:00) → abc@loja.com

Dashboard:
- Widgets: vendas, ticket, produtos
- Tema: Azul
```

#### Empresa #2 - Mercado XYZ
```
Agendamentos:
- Vendas diárias (07:00) → contato@xyz.com
- Fiscal mensal (10:00) → fiscal@xyz.com, contador@xyz.com

Dashboard:
- Widgets: vendas, pagamentos, turnos, gráfico
- Tema: Verde
```

#### Empresa #3 - Farmácia 123
```
Agendamentos:
- Estoque diário (06:00) → estoque@farmacia.com
- Vendas semanais (08:00) → gerente@farmacia.com

Dashboard:
- Widgets: todos
- Tema: Roxo
```

### Processamento do Cron

Quando o cron roda às **08:00**:

```
Processando agendamentos de relatórios...
Encontrados 2 agendamento(s) pendente(s).

-----------------------------------
Empresa #1 | Agendamento #5
Tipo: VENDAS | Formato: EXCEL
  ✓ Relatório gerado
  ✓ Email enviado para: abc@loja.com
  ✓ Próximo envio: 01/10/2025 08:00
✓ SUCESSO!

-----------------------------------
Empresa #3 | Agendamento #12
Tipo: VENDAS | Formato: EXCEL
  ✓ Relatório gerado
  ✓ Email enviado para: gerente@farmacia.com
  ✓ Próximo envio: 07/10/2025 08:00
✓ SUCESSO!

Processamento finalizado!
```

---

## 🛠️ Administração

### Para Contadores (tipo = 2)

Contadores podem ver todas as empresas que gerenciam, mas cada empresa mantém seus dados isolados.

### Para Empresas (tipo = 3)

Empresas veem apenas seus próprios dados:
- Agendamentos
- Configurações
- Relatórios
- Alertas

---

## 📈 Escalabilidade

O sistema suporta:
- ✅ **Ilimitadas empresas** no mesmo banco
- ✅ **Ilimitados agendamentos** por empresa
- ✅ **Processamento eficiente** via índices no banco
- ✅ **Logs detalhados** para auditoria

### Índices de Performance

```sql
-- Índices criados automaticamente pela migration
INDEX idx_empresa (id_empresa)
INDEX idx_empresa_status (id_empresa, is_active)
INDEX idx_next_run (next_run)
```

---

## 🔧 Troubleshooting

### Empresa não recebe emails

**Verificar:**
1. Agendamento está ativo? (`is_active = 1`)
2. Email está correto no cadastro da empresa
3. Cron job está rodando
4. Configuração de email do servidor (`app/Config/Email.php`)

**Teste manual:**
```bash
php spark reports:process
```

### Agendamentos não foram criados

**Verificar:**
1. Log do sistema: `writable/logs/log-YYYY-MM-DD.log`
2. Procurar por: `"Agendamentos automáticos criados para empresa"`
3. Se não encontrar, executar manualmente:

```php
$setupService = new \App\Libraries\EmpresaSetupService();
$setupService->setupNovaEmpresa(ID_EMPRESA, ID_CONTADOR, 'email@empresa.com', ID_LOGIN);
```

### Desativar agendamentos de uma empresa

```php
$setupService = new \App\Libraries\EmpresaSetupService();
$setupService->desativarAgendamentos(ID_EMPRESA);
```

### Reativar agendamentos

```php
$setupService = new \App\Libraries\EmpresaSetupService();
$setupService->reativarAgendamentos(ID_EMPRESA);
```

---

## 📞 Suporte Técnico

Para problemas com o sistema multi-tenant:

1. Verificar logs em `writable/logs/`
2. Testar comando manualmente: `php spark reports:process`
3. Validar isolamento no banco: `SELECT * FROM report_schedules WHERE id_empresa = X`

---

## ✅ Checklist de Implementação

- [x] Isolamento por `id_empresa` em todas as queries
- [x] Setup automático ao cadastrar empresa
- [x] Agendamentos padrão criados
- [x] Dashboard padrão criado
- [x] Cron job processa todas as empresas
- [x] Logs detalhados por empresa
- [x] Segurança garantida (não vaza dados entre empresas)
- [x] Escalável para centenas de empresas
- [x] Documentação completa

---

**Sistema 100% Multi-Tenant ✅**

Cada empresa é completamente independente, com seus próprios agendamentos, configurações e dados isolados!
