# 🕐 Guia Completo: Configurar Cron Jobs na Hostinger

## 📋 Índice
1. [Método 1: Cron Job Nativo (hPanel)](#método-1-cron-job-nativo)
2. [Método 2: Webcron (Sem acesso SSH)](#método-2-webcron-easycron)
3. [Método 3: Via SSH](#método-3-via-ssh-avançado)
4. [Configurar Token de Segurança](#configurar-token-de-segurança)
5. [Testar Manualmente](#testar-manualmente)
6. [Monitoramento e Logs](#monitoramento-e-logs)

---

## ✅ Método 1: Cron Job Nativo (hPanel)

### **Passo a Passo na Hostinger:**

#### **1. Login no hPanel**
```
1. Acesse: https://hpanel.hostinger.com
2. Faça login com suas credenciais
3. Selecione seu plano de hospedagem
```

#### **2. Acessar Cron Jobs**
```
1. No painel lateral, clique em "Avançado"
2. Clique em "Cron Jobs"
```

#### **3. Descobrir Caminho do PHP**

Antes de criar o cron, precisamos saber o caminho do PHP:

**Opção A: Via hPanel**
```
1. Vá em "Avançado" → "PHP Configuration"
2. Veja a versão do PHP ativa
3. Geralmente é: /usr/bin/php ou /usr/bin/php8.1
```

**Opção B: Criar arquivo teste**

Crie `/public_html/test-php-path.php`:
```php
<?php
echo "Caminho do PHP: " . PHP_BINARY;
?>
```

Acesse: `https://seu-dominio.com/test-php-path.php`

#### **4. Criar Novo Cron Job**

**Configuração Recomendada (A cada hora):**

```
┌─────────────────────────────────────────────────────┐
│ ADICIONAR NOVO CRON JOB                             │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Tipo de Cron:      Comum                           │
│                                                     │
│ ┌─────────────────────────────────────────────┐   │
│ │ CONFIGURAÇÃO DE TEMPO                       │   │
│ ├─────────────────────────────────────────────┤   │
│ │ Minuto:        0                            │   │
│ │ Hora:          *                            │   │
│ │ Dia do Mês:    *                            │   │
│ │ Mês:           *                            │   │
│ │ Dia da Semana: *                            │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ Comando:                                            │
│ ┌─────────────────────────────────────────────┐   │
│ │ /usr/bin/php /home/u123456789/              │   │
│ │ public_html/spark reports:process           │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ Email de Notificação (opcional):                   │
│ ┌─────────────────────────────────────────────┐   │
│ │ seu-email@exemplo.com                       │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ [ Criar Cron Job ]                                 │
└─────────────────────────────────────────────────────┘
```

**⚠️ IMPORTANTE:** Substitua `/home/u123456789/` pelo **SEU** caminho!

Para descobrir seu caminho, crie `/public_html/caminho.php`:
```php
<?php echo __DIR__; ?>
```

Acesse e veja o resultado (exemplo: `/home/u987654321/public_html`)

#### **5. Verificar Sintaxe do Comando**

**Comando Completo:**
```bash
/usr/bin/php /home/SEU_USUARIO/public_html/spark reports:process
```

**Componentes:**
- `/usr/bin/php` → Caminho do interpretador PHP
- `/home/SEU_USUARIO/public_html` → Caminho do projeto
- `spark` → CLI do CodeIgniter 4
- `reports:process` → Comando customizado

---

## 🌐 Método 2: Webcron (EasyCron)

**Use este método se:**
- ❌ Não tem acesso a cron jobs no painel
- ❌ Plano de hospedagem básico/compartilhado
- ✅ Quer solução 100% gratuita e confiável

### **Passo 1: Criar Conta no EasyCron**

```
1. Acesse: https://www.easycron.com/user/register
2. Cadastre-se (100% GRÁTIS)
3. Confirme seu email
```

### **Passo 2: Configurar Token de Segurança**

#### **A) Criar arquivo `.env` (se não existir)**

Na raiz do projeto (`/public_html/.env`):

```env
# ==========================================
# CONFIGURAÇÃO DE SEGURANÇA DO CRON
# ==========================================

# Token secreto para cron via HTTP
# GERE UM TOKEN ÚNICO E FORTE!
# Exemplo: https://www.uuidgenerator.net/
CRON_TOKEN = a7b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6

# Configurações de Email (necessário para envios)
email.fromEmail = noreply@seu-dominio.com
email.fromName = Seu ERP

# SMTP (exemplo com Gmail)
email.SMTPHost = smtp.gmail.com
email.SMTPUser = seu-email@gmail.com
email.SMTPPass = sua-senha-de-app
email.SMTPPort = 587
email.SMTPCrypto = tls
```

**⚠️ IMPORTANTE:** 
- Gere um token ÚNICO em: https://www.uuidgenerator.net/
- **NUNCA** compartilhe este token!
- Adicione `.env` no `.gitignore`

### **Passo 3: Criar Cron Job no EasyCron**

```
┌─────────────────────────────────────────────────────┐
│ CRIAR NOVO CRON JOB                                 │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Nome:                                               │
│ ┌─────────────────────────────────────────────┐   │
│ │ ERP - Processar Relatórios                  │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ URL:                                                │
│ ┌─────────────────────────────────────────────┐   │
│ │ https://seu-dominio.com/cron/               │   │
│ │ process-reports?token=SEU_TOKEN_AQUI        │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ Intervalo:                                          │
│ ┌─────────────────────────────────────────────┐   │
│ │ A cada 1 hora                               │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ Status Esperado:                                    │
│ ┌─────────────────────────────────────────────┐   │
│ │ 200 (Success)                               │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
│ Notificações:                                       │
│ ☑ Email em caso de falha                          │
│ ☐ Email a cada execução                           │
│                                                     │
│ [ Criar ]  [ Testar ]                              │
└─────────────────────────────────────────────────────┘
```

### **Passo 4: Testar**

Clique em **"Testar"** no EasyCron. Você deve ver:

```json
{
  "success": true,
  "message": "Processamento concluído",
  "total": 0,
  "processados": 0,
  "erros": 0,
  "tempo": "0.15s"
}
```

---

## 🔐 Configurar Token de Segurança

### **Por que usar token?**

❌ **SEM TOKEN:**
```
Qualquer pessoa pode acessar:
https://seu-site.com/cron/process-reports

→ Sobrecarga do servidor
→ Gastos desnecessários
→ Vulnerabilidade de segurança
```

✅ **COM TOKEN:**
```
Apenas quem tem o token pode executar:
https://seu-site.com/cron/process-reports?token=ABC123XYZ

→ Protegido contra abusos
→ Controle total de execução
→ Logs de acesso não autorizado
```

### **Gerar Token Seguro**

**Opção 1: UUID Generator**
```
1. Acesse: https://www.uuidgenerator.net/version4
2. Copie o UUID gerado
3. Exemplo: a7b2c3d4-e5f6-47h8-i9j0-k1l2m3n4o5p6
```

**Opção 2: PHP**
```php
<?php
// Execute localmente
echo bin2hex(random_bytes(32));
// Exemplo: 5f4dcc3b5aa765d61d8327deb882cf99c0e6e2e8f123a456b789c012d345e678
?>
```

**Opção 3: Comando Linux**
```bash
openssl rand -hex 32
```

---

## ⚙️ Método 3: Via SSH (Avançado)

**Requisitos:**
- ✅ Acesso SSH habilitado (planos Premium/Business na Hostinger)
- ✅ Conhecimento básico de terminal

### **Passo 1: Conectar via SSH**

```bash
ssh u123456789@seu-dominio.com
```

### **Passo 2: Editar Crontab**

```bash
crontab -e
```

### **Passo 3: Adicionar Linha**

```bash
# ERP - Processar relatórios a cada hora
0 * * * * cd /home/u123456789/public_html && /usr/bin/php spark reports:process >> /home/u123456789/logs/cron-reports.log 2>&1
```

**Explicação:**
- `0 * * * *` → A cada hora cheia (08:00, 09:00, 10:00...)
- `cd /home/.../public_html` → Vai para pasta do projeto
- `&&` → E então executa
- `/usr/bin/php spark reports:process` → Comando
- `>> /home/.../logs/cron-reports.log` → Salva log
- `2>&1` → Inclui erros no log

### **Passo 4: Salvar e Verificar**

```bash
# Salvar: Ctrl+X, Y, Enter

# Verificar se foi criado:
crontab -l

# Ver logs:
tail -f /home/u123456789/logs/cron-reports.log
```

---

## 🧪 Testar Manualmente

### **Teste 1: Via Navegador (Webcron)**

```
URL: https://seu-dominio.com/cron/process-reports?token=SEU_TOKEN

Resultado esperado:
{
  "success": true,
  "message": "Processamento concluído",
  "total": 3,
  "processados": 3,
  "erros": 0,
  "tempo": "2.45s"
}
```

### **Teste 2: Via SSH**

```bash
cd /home/u123456789/public_html
/usr/bin/php spark reports:process

# Resultado:
Processando agendamentos de relatórios...
Encontrados 3 agendamento(s) pendente(s).
-----------------------------------
Empresa #1 | Agendamento #5
Tipo: VENDAS | Formato: EXCEL
  ✓ Relatório gerado
  ✓ Email enviado para: empresa@exemplo.com
  ✓ Próximo envio: 01/10/2025 08:00
✓ SUCESSO!
```

### **Teste 3: Verificar Status**

```
URL: https://seu-dominio.com/cron/status?token=SEU_TOKEN

Resultado:
{
  "total": 15,
  "ativos": 12,
  "inativos": 3,
  "pendentes": 0,
  "proximos": [
    {
      "id_schedule": 5,
      "id_empresa": 1,
      "report_type": "vendas",
      "next_run": "2025-10-01 08:00:00"
    }
  ]
}
```

---

## 📊 Monitoramento e Logs

### **1. Logs do Sistema**

**Localização:** `/writable/logs/log-YYYY-MM-DD.log`

**Buscar por:**
```bash
grep "CRON" /home/u123456789/public_html/writable/logs/log-*.log
```

**Exemplos de logs:**
```
INFO - 2025-09-30 08:00:15 --> [CRON HTTP] Encontrados 3 agendamento(s)
INFO - 2025-09-30 08:00:18 --> [CRON HTTP] ✓ Empresa #1 - Agendamento #5
INFO - 2025-09-30 08:00:21 --> [CRON HTTP] ✓ Empresa #2 - Agendamento #8
INFO - 2025-09-30 08:00:24 --> [CRON HTTP] Finalizado - Processados: 3, Erros: 0
```

### **2. Logs do Cron (se via SSH)**

```bash
tail -f /home/u123456789/logs/cron-reports.log
```

### **3. Monitoramento via EasyCron**

```
Dashboard → Execution History

Veja:
- Horário de cada execução
- Status (sucesso/falha)
- Tempo de resposta
- Alertas de erro
```

---

## 🛠️ Solução de Problemas

### **Problema 1: Cron não executa**

**Verificar:**
```bash
# 1. Permissões
chmod +x /home/u123456789/public_html/spark

# 2. Caminho do PHP correto
which php

# 3. Teste manual
cd /home/u123456789/public_html
php spark reports:process
```

### **Problema 2: Erro 403 (Webcron)**

**Causa:** Token inválido ou ausente

**Solução:**
1. Verificar `.env` existe e está configurado
2. Verificar token na URL do webcron
3. Limpar cache: `php spark cache:clear`

### **Problema 3: Emails não enviam**

**Verificar:**
```php
// Testar configuração de email
php spark

# No terminal do Spark:
$email = \Config\Services::email();
$email->setTo('teste@exemplo.com');
$email->setSubject('Teste');
$email->setMessage('Teste de email');
$email->send();
```

---

## ✅ Checklist Final

Antes de colocar em produção:

- [ ] Token de segurança gerado e configurado em `.env`
- [ ] Cron job criado (hPanel, EasyCron ou SSH)
- [ ] Teste manual executado com sucesso
- [ ] Email de teste recebido
- [ ] Logs verificados
- [ ] Monitoramento configurado
- [ ] Backup do arquivo `.env` (em local seguro)

---

## 📞 Suporte Hostinger

Caso tenha problemas:

**Chat ao Vivo:** https://www.hostinger.com.br/contato
**Email:** suporte@hostinger.com
**Telefone:** 0800 591 9015

**Perguntas comuns:**
- "Como ativar cron jobs no meu plano?"
- "Qual o caminho do PHP no meu servidor?"
- "Como habilitar acesso SSH?"

---

## 🎉 Conclusão

Agora você tem **3 métodos** para configurar cron jobs:

1. ✅ **hPanel** - Mais simples, interface visual
2. ✅ **Webcron** - Para planos básicos sem cron
3. ✅ **SSH** - Mais controle e flexibilidade

**Recomendação:** Comece com **EasyCron** (Método 2) - é gratuito, simples e funciona em qualquer hospedagem!

---

**Sistema pronto para produção! 🚀**
