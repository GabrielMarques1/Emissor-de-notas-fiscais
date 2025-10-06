#!/bin/bash

# ========================================
# AUTOMAÇÃO COMPLETA PARA PRODUÇÃO
# Sistema SaaS Multi-Tenant - 7 Camadas de Segurança
# ========================================

echo "🚀 CONFIGURANDO AUTOMAÇÃO PARA PRODUÇÃO"
echo "========================================"

# Definir caminhos
PROJECT_PATH="/path/to/your/erp.local"
PHP_PATH="/usr/bin/php"
SPARK_PATH="$PROJECT_PATH/spark"

# Verificar se o projeto existe
if [ ! -f "$SPARK_PATH" ]; then
    echo "❌ ERRO: Spark não encontrado em $SPARK_PATH"
    echo "   Ajuste a variável PROJECT_PATH no script"
    exit 1
fi

echo "✅ Projeto encontrado: $PROJECT_PATH"

# Criar arquivo de cron jobs
CRON_FILE="/tmp/erp_cron_jobs"

cat > "$CRON_FILE" << 'EOF'
# ========================================
# AUTOMAÇÃO ERP MULTI-TENANT - PRODUÇÃO
# ========================================

# BACKUP INCREMENTAL DIÁRIO (02:00)
0 2 * * * /usr/bin/php /path/to/your/erp.local/spark backup:all-tenants --incremental --skip-errors >> /var/log/erp/backup_daily.log 2>&1

# BACKUP COMPLETO SEMANAL COM UPLOAD (03:00 Domingo)
0 3 * * 0 /usr/bin/php /path/to/your/erp.local/spark backup:all-tenants --full --upload --parallel >> /var/log/erp/backup_weekly.log 2>&1

# LIMPEZA AUTOMÁTICA DE BACKUPS (04:00)
0 4 * * * /usr/bin/php /path/to/your/erp.local/spark backup:cleanup >> /var/log/erp/backup_cleanup.log 2>&1

# TESTE DE RESTORE MENSAL (05:00 Primeiro Domingo)
0 5 1-7 * 0 /usr/bin/php /path/to/your/erp.local/spark backup:test-restore --dry-run >> /var/log/erp/backup_test.log 2>&1

# LIMPEZA DE CACHE EXPIRADO (A cada 6 horas)
0 */6 * * * /usr/bin/php /path/to/your/erp.local/spark cache:clean --expired-only >> /var/log/erp/cache_clean.log 2>&1

# MONITORAMENTO DE DISCO (A cada 2 horas)
0 */2 * * * /usr/bin/php /path/to/your/erp.local/spark system:check-disk >> /var/log/erp/disk_monitor.log 2>&1

# VERIFICAÇÃO DE SAÚDE DO SISTEMA (A cada hora)
0 * * * * /usr/bin/php /path/to/your/erp.local/spark system:health-check >> /var/log/erp/health_check.log 2>&1

# PROCESSAMENTO DE RELATÓRIOS AGENDADOS (05:30)
30 5 * * * /usr/bin/php /path/to/your/erp.local/spark reports:process >> /var/log/erp/reports.log 2>&1

# EXPIRAÇÃO DE TRANSAÇÕES PIX (A cada 30 minutos)
*/30 * * * * /usr/bin/php /path/to/your/erp.local/spark pix:expire >> /var/log/erp/pix_expire.log 2>&1

# EXPIRAÇÃO DE VENDAS SUSPENSAS (A cada hora)
0 * * * * /usr/bin/php /path/to/your/erp.local/spark sales:expire-suspended >> /var/log/erp/sales_expire.log 2>&1

# PROCESSAMENTO DE COBRANÇAS MENSAIS (01:00 Dia 1)
0 1 1 * * /usr/bin/php /path/to/your/erp.local/spark cobranca:processar >> /var/log/erp/cobranca.log 2>&1

# SINCRONIZAÇÃO COM NUVEM (06:00)
0 6 * * * /usr/bin/php /path/to/your/erp.local/spark sync:cloud >> /var/log/erp/sync_cloud.log 2>&1

EOF

# Substituir caminhos no arquivo de cron
sed -i "s|/path/to/your/erp.local|$PROJECT_PATH|g" "$CRON_FILE"
sed -i "s|/usr/bin/php|$PHP_PATH|g" "$CRON_FILE"

echo "📋 Arquivo de cron jobs criado:"
echo "================================"
cat "$CRON_FILE"
echo "================================"

# Criar diretórios de log
echo "📁 Criando diretórios de log..."
sudo mkdir -p /var/log/erp
sudo chown www-data:www-data /var/log/erp
sudo chmod 755 /var/log/erp

# Instalar cron jobs
echo "⚙️ Instalando cron jobs..."
crontab "$CRON_FILE"

# Verificar instalação
echo "✅ Cron jobs instalados:"
crontab -l | grep "erp.local"

# Criar script de monitoramento
MONITOR_SCRIPT="$PROJECT_PATH/monitor_production.sh"
cat > "$MONITOR_SCRIPT" << 'EOF'
#!/bin/bash

# Script de monitoramento para produção
echo "🔍 MONITORAMENTO ERP MULTI-TENANT"
echo "================================="

# Verificar logs de backup
echo "📦 ÚLTIMOS BACKUPS:"
tail -5 /var/log/erp/backup_daily.log

echo ""
echo "🧹 LIMPEZA DE CACHE:"
tail -3 /var/log/erp/cache_clean.log

echo ""
echo "💾 ESPAÇO EM DISCO:"
df -h | grep -E "(Filesystem|/var|/tmp)"

echo ""
echo "🏥 SAÚDE DO SISTEMA:"
tail -3 /var/log/erp/health_check.log

echo ""
echo "⚡ PROCESSOS ATIVOS:"
ps aux | grep -E "(php.*spark|backup|cache)" | grep -v grep

EOF

chmod +x "$MONITOR_SCRIPT"

echo ""
echo "🎉 AUTOMAÇÃO CONFIGURADA COM SUCESSO!"
echo "===================================="
echo "✅ Cron jobs instalados e ativos"
echo "✅ Logs configurados em /var/log/erp/"
echo "✅ Script de monitoramento: $MONITOR_SCRIPT"
echo ""
echo "📋 PRÓXIMOS PASSOS:"
echo "1. Ajustar PROJECT_PATH no início deste script"
echo "2. Verificar permissões de escrita nos logs"
echo "3. Testar comandos manualmente"
echo "4. Monitorar logs nas primeiras execuções"
echo ""
echo "🔧 COMANDOS ÚTEIS:"
echo "- Verificar cron: crontab -l"
echo "- Monitorar: $MONITOR_SCRIPT"
echo "- Logs: tail -f /var/log/erp/*.log"
echo ""
echo "🚀 SISTEMA PRONTO PARA PRODUÇÃO!"
