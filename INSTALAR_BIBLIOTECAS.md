# 📚 Instalar Bibliotecas de Exportação

## ⚠️ IMPORTANTE

As bibliotecas **PHPSpreadsheet** e **TCPDF** não foram instaladas automaticamente.

---

## 🔧 Como Instalar

### **Método 1: Via Composer (Recomendado)**

```bash
# No terminal, na pasta do projeto:
cd C:\xampp\htdocs\erp.local

# Instalar PHPSpreadsheet (Excel)
composer require phpoffice/phpspreadsheet

# Instalar TCPDF (PDF)
composer require tecnickcom/tcpdf
```

---

### **Método 2: Comando Único**

```bash
composer require phpoffice/phpspreadsheet tecnickcom/tcpdf
```

---

## ✅ Verificar Instalação

Após instalar, execute:

```bash
# Verificar PHPSpreadsheet
php -r "echo class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') ? 'OK' : 'ERRO';"

# Verificar TCPDF
php -r "echo class_exists('TCPDF') ? 'OK' : 'ERRO';"
```

**Resultado esperado:** `OK` para ambos

---

## 🚨 Se Continuar com Erro

### **Limpar cache do Composer:**

```bash
composer clear-cache
composer dump-autoload
```

### **Reinstalar:**

```bash
composer remove phpoffice/phpspreadsheet tecnickcom/tcpdf
composer require phpoffice/phpspreadsheet tecnickcom/tcpdf
```

---

## 📊 O Que Cada Biblioteca Faz

| Biblioteca | Função | Tamanho |
|------------|--------|---------|
| **PHPSpreadsheet** | Exportar Excel (.xlsx) | ~15MB |
| **TCPDF** | Exportar PDF | ~5MB |

---

## 🎯 Após Instalação

As funcionalidades estarão disponíveis:

- ✅ **Exportar Excel** - `http://erp.local/relatorios-empresa/vendas` → Botão "Exportar Excel"
- ✅ **Exportar PDF** - `http://erp.local/relatorios-empresa/vendas` → Botão "Exportar PDF"

---

## 🔍 Solução de Problemas

### Erro: "Class not found"

**Causa:** Bibliotecas não instaladas

**Solução:** Execute os comandos acima

### Erro: "Composer not found"

**Causa:** Composer não está no PATH

**Solução:**
```bash
# Windows:
php C:\ProgramData\ComposerSetup\bin\composer.phar require phpoffice/phpspreadsheet tecnickcom/tcpdf

# Ou use o caminho completo do composer.exe
```

### Erro: "Memory exhausted"

**Causa:** Memória insuficiente do PHP

**Solução:**

Edite `php.ini`:
```ini
memory_limit = 512M
```

Ou no comando:
```bash
php -d memory_limit=512M C:\ProgramData\ComposerSetup\bin\composer.phar require phpoffice/phpspreadsheet tecnickcom/tcpdf
```

---

## ✅ Status Atual

Após correções, o sistema irá:

- ❌ **Antes:** Erro fatal ao exportar
- ✅ **Agora:** Mensagem amigável se biblioteca não instalada
- ✅ **Depois de instalar:** Exportação funcional

---

**Execute os comandos acima para habilitar as exportações!** 🚀
