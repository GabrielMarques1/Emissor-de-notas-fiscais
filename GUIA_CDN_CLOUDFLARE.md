# 🚀 GUIA DE CONFIGURAÇÃO CDN - CLOUDFLARE

**Sistema:** xFiscal ERP - PDV Multi-Tenant SaaS  
**Data:** 02/10/2025  
**Objetivo:** Configurar CDN para máxima performance

---

## 📊 BENEFÍCIOS DO CDN

| Métrica | Sem CDN | Com CDN | Melhoria |
|---------|---------|---------|----------|
| **Tempo de Carregamento** | 2.5s | 0.8s | -68% ⚡ |
| **Largura de Banda** | 100% | 20% | -80% 💰 |
| **Disponibilidade** | 99% | 99.99% | +0.99% |
| **DDoS Protection** | ❌ | ✅ | Incluso |
| **SSL Grátis** | ❌ | ✅ | Incluso |

---

## 🔧 PASSO 1: CRIAR CONTA CLOUDFLARE

### 1.1 Cadastro
1. Acesse https://dash.cloudflare.com/sign-up
2. Crie conta com e-mail empresarial
3. Confirme e-mail

### 1.2 Adicionar Domínio
1. Clique em "Add a Site"
2. Digite seu domínio: `seudominio.com.br`
3. Selecione plano **Free** (suficiente para começar)
4. Aguarde scan de DNS

### 1.3 Atualizar Nameservers
Cloudflare fornecerá 2 nameservers, ex:
```
ns1.cloudflare.com
ns2.cloudflare.com
```

**Atualize no seu registro.br ou GoDaddy:**
1. Acesse painel do registrador
2. Vá em "DNS" ou "Nameservers"
3. Substitua pelos nameservers do Cloudflare
4. Aguarde propagação (até 24h, geralmente 2-4h)

---

## 🚀 PASSO 2: CONFIGURAR CDN

### 2.1 SSL/TLS
1. Vá em **SSL/TLS** → **Overview**
2. Selecione: **Full (strict)**
3. Ative **Always Use HTTPS**
4. Ative **Automatic HTTPS Rewrites**

### 2.2 Speed (Performance)
1. Vá em **Speed** → **Optimization**
2. Ative:
   - ✅ Auto Minify (CSS, JS, HTML)
   - ✅ Brotli
   - ✅ Early Hints
   - ✅ Rocket Loader (testar - pode quebrar alguns JS)

### 2.3 Caching
1. Vá em **Caching** → **Configuration**
2. **Caching Level:** Standard
3. **Browser Cache TTL:** 1 year

**Criar Page Rules:**

#### Rule 1: Assets Estáticos
```
URL: *seudominio.com.br/theme/*
Cache Level: Cache Everything
Edge Cache TTL: 1 month
Browser Cache TTL: 1 year
```

#### Rule 2: Assets PDV
```
URL: *seudominio.com.br/pdv-assets/*
Cache Level: Cache Everything
Edge Cache TTL: 1 month
Browser Cache TTL: 1 year
```

#### Rule 3: Imagens
```
URL: *seudominio.com.br/assets/img/*
Cache Level: Cache Everything
Edge Cache TTL: 1 month
Browser Cache TTL: 30 days
Polish: Lossless
```

#### Rule 4: Bypass API
```
URL: *seudominio.com.br/api/*
Cache Level: Bypass
```

### 2.4 Firewall
1. Vá em **Security** → **WAF**
2. Ative **Managed Rules**
3. Crie regra para bloquear países (opcional):
   ```
   (ip.geoip.country ne "BR") and (http.request.uri.path contains "/login")
   Action: Block
   ```

### 2.5 Scrape Shield
1. Ative **Email Address Obfuscation**
2. Ative **Server-side Excludes**
3. Ative **Hotlink Protection**

---

## 🔧 PASSO 3: CONFIGURAR APLICAÇÃO

### 3.1 Atualizar `app/Config/Assets.php`

```php
<?php

namespace Config;

class Assets extends BaseConfig
{
    // Ativar CDN
    public bool $cdnEnabled = true;
    
    // URL do Cloudflare (ou deixar vazio para usar domínio principal)
    public string $cdnUrl = 'https://seudominio.com.br';
    
    // Versão dos assets
    public string $version = 'v1.0.1'; // Atualizar a cada deploy
}
```

### 3.2 Carregar Helper de Assets

**`app/Config/Autoload.php`:**
```php
public $helpers = ['asset'];
```

### 3.3 Atualizar Views

**Antes:**
```php
<link href="<?= base_url('theme/dist/css/adminlte.min.css') ?>" rel="stylesheet">
<script src="<?= base_url('theme/plugins/jquery/jquery.min.js') ?>"></script>
```

**Depois:**
```php
<link href="<?= asset('theme/dist/css/adminlte.min.css') ?>" rel="stylesheet">
<script src="<?= asset('theme/plugins/jquery/jquery.min.js') ?>"></script>
```

### 3.4 Adicionar Preload e DNS Prefetch

**`app/Views/templates/header.php`:**
```php
<!DOCTYPE html>
<html>
<head>
    <!-- DNS Prefetch -->
    <?= dns_prefetch_tags() ?>
    
    <!-- Preload Recursos Críticos -->
    <?= preload_tags() ?>
    
    <!-- CSS -->
    <link href="<?= asset('theme/dist/css/adminlte.min.css') ?>" rel="stylesheet">
</head>
```

---

## 🧪 PASSO 4: TESTAR

### 4.1 Verificar DNS
```bash
# Windows
nslookup seudominio.com.br

# Linux/Mac
dig seudominio.com.br
```

Deve mostrar IPs do Cloudflare (não seu servidor real).

### 4.2 Verificar Headers
```bash
curl -I https://seudominio.com.br/theme/dist/css/adminlte.min.css
```

**Headers esperados:**
```
HTTP/2 200
server: cloudflare
cf-cache-status: HIT
cache-control: public, max-age=31536000, immutable
cf-ray: ...
```

### 4.3 Verificar Performance
1. Acesse https://gtmetrix.com
2. Digite seu domínio
3. Verifique:
   - ✅ PageSpeed Score: 90+
   - ✅ Total Page Size: < 1MB
   - ✅ Fully Loaded Time: < 2s
   - ✅ Requests: < 50

### 4.4 Verificar SSL
1. Acesse https://www.ssllabs.com/ssltest/
2. Digite seu domínio
3. Aguarde análise
4. Resultado esperado: **A+**

---

## 🔄 PASSO 5: PURGAR CACHE

### 5.1 Via Painel Cloudflare
1. Vá em **Caching** → **Purge Cache**
2. Opções:
   - **Purge Everything**: Limpa todo cache (use com cautela)
   - **Custom Purge**: Limpa URLs específicas
   - **Purge by Tag**: Limpa por tags

### 5.2 Via API (Automatizar Deploy)

**Criar Token de API:**
1. Vá em **My Profile** → **API Tokens**
2. Crie token com permissão: `Zone.Cache Purge`

**Script de Deploy:**
```bash
#!/bin/bash
# deploy.sh

# Fazer deploy
git pull
composer install
php spark migrate

# Purgar cache do Cloudflare
ZONE_ID="seu_zone_id"
API_TOKEN="seu_token"

curl -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'

echo "Deploy completo e cache limpo!"
```

### 5.3 Via PHP (Programático)

**`app/Libraries/CloudflareCache.php`:**
```php
<?php

namespace App\Libraries;

class CloudflareCache
{
    protected string $zoneId;
    protected string $apiToken;
    
    public function __construct()
    {
        $this->zoneId = getenv('CLOUDFLARE_ZONE_ID');
        $this->apiToken = getenv('CLOUDFLARE_API_TOKEN');
    }
    
    public function purgeAll(): bool
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/purge_cache",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['purge_everything' => true]),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiToken}",
                "Content-Type: application/json"
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        return $result['success'] ?? false;
    }
    
    public function purgeFiles(array $files): bool
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/purge_cache",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['files' => $files]),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiToken}",
                "Content-Type: application/json"
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        return $result['success'] ?? false;
    }
}
```

**Uso:**
```php
$cf = new \App\Libraries\CloudflareCache();

// Limpar tudo
$cf->purgeAll();

// Limpar arquivos específicos
$cf->purgeFiles([
    'https://seudominio.com.br/theme/dist/css/adminlte.min.css',
    'https://seudominio.com.br/pdv-assets/js/pdv.js'
]);
```

---

## 📊 MONITORAMENTO

### Via Painel Cloudflare
1. **Analytics** → **Traffic**
   - Requests totais
   - Bandwidth saved
   - Threats blocked
   
2. **Caching** → **Analytics**
   - Cache hit rate (meta: > 80%)
   - Cached bandwidth

### Alertas
1. Vá em **Notifications**
2. Ative:
   - ✅ DDoS Attack Alert
   - ✅ SSL/TLS Certificate Expiration
   - ✅ Cache Purge

---

## 🔧 OTIMIZAÇÕES AVANÇADAS

### 1. Argo Smart Routing (Pago)
- Reduz latência em 30%
- Custo: $5/mês + $0.10/GB

### 2. Workers (Serverless)
```javascript
// worker.js - Adicionar headers de segurança
addEventListener('fetch', event => {
    event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
    const response = await fetch(request)
    const newHeaders = new Headers(response.headers)
    
    newHeaders.set('X-Frame-Options', 'SAMEORIGIN')
    newHeaders.set('X-Content-Type-Options', 'nosniff')
    
    return new Response(response.body, {
        status: response.status,
        statusText: response.statusText,
        headers: newHeaders
    })
}
```

### 3. Image Optimization
- Ative **Polish** (Lossless ou Lossy)
- Ative **Mirage** (lazy loading automático)
- Custo: incluído no Pro ($20/mês)

### 4. Load Balancing (Multi-Servidor)
- Distribuir tráfego entre servidores
- Failover automático
- Custo: $5/mês/servidor

---

## ✅ CHECKLIST FINAL

### Configuração Cloudflare
- [ ] Conta criada e domínio adicionado
- [ ] Nameservers atualizados (propagados)
- [ ] SSL/TLS configurado (Full Strict)
- [ ] Always Use HTTPS ativado
- [ ] Auto Minify ativado
- [ ] Brotli ativado
- [ ] Page Rules criadas (assets, API bypass)
- [ ] WAF ativado

### Aplicação
- [ ] `app/Config/Assets.php` configurado
- [ ] Helper `asset()` carregado
- [ ] Views atualizadas para usar `asset()`
- [ ] Preload tags adicionados
- [ ] DNS Prefetch configurado
- [ ] `.htaccess` otimizado

### Testes
- [ ] DNS resolvendo para Cloudflare
- [ ] HTTPS funcionando (A+ no SSL Labs)
- [ ] Cache HIT em assets
- [ ] PageSpeed > 90
- [ ] Fully Loaded < 2s

### Monitoramento
- [ ] Analytics configurado
- [ ] Alertas ativados
- [ ] Cache purge testado

---

## 🎯 RESULTADOS ESPERADOS

| Métrica | Meta |
|---------|------|
| **PageSpeed Score** | 90+ |
| **Fully Loaded Time** | < 2s |
| **Total Page Size** | < 1MB |
| **Requests** | < 50 |
| **Cache Hit Rate** | > 80% |
| **SSL Rating** | A+ |
| **Availability** | 99.99% |

---

## 📚 RECURSOS

- [Cloudflare Docs](https://developers.cloudflare.com)
- [Page Rules Guide](https://support.cloudflare.com/hc/en-us/articles/218411427)
- [API Docs](https://api.cloudflare.com)
- [Community Forum](https://community.cloudflare.com)

---

**✅ CDN CLOUDFLARE CONFIGURADO COM SUCESSO!** 🚀

