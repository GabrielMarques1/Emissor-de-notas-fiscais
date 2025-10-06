#!/bin/bash

# =============================================================================
# CONFIGURAÇÃO DE CRON JOBS PARA SISTEMA DE BACKUP MULTI-TENANT
# =============================================================================
# 
# Este script configura automaticamente os cron jobs necessários para o
# sistema de backup funcionar de forma totalmente automatizada
#
# Funcionalidades:
# - Backup incremental diário
# - Backup completo semanal
# - Limpeza automática de backups antigos
# - Teste de restore mensal
# - Monitoramento de espaço em disco
#
# =============================================================================

# Configurações
PROJECT_PATH="/var/www/html/erp.local"  # Ajustar para o caminho correto
PHP_PATH="/usr/bin/php"                 # Ajustar se necessário
LOG_PATH="/var/log/backup-cron"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== CONFIGURAÇÃO DE CRON JOBS - SISTEMA DE BACKUP ===${NC}"
echo ""

# Verificar se o projeto existe
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${RED}❌ Erro: Diretório do projeto não encontrado: $PROJECT_PATH${NC}"
    echo "Por favor, ajuste a variável PROJECT_PATH no script"
    exit 1
fi

# Verificar se PHP existe
if [ ! -f "$PHP_PATH" ]; then
    echo -e "${RED}❌ Erro: PHP não encontrado em: $PHP_PATH${NC}"
    echo "Por favor, ajuste a variável PHP_PATH no script"
    exit 1
fi

# Criar diretório de logs se não existir
if [ ! -d "$LOG_PATH" ]; then
    echo -e "${YELLOW}📁 Criando diretório de logs: $LOG_PATH${NC}"
    sudo mkdir -p "$LOG_PATH"
    sudo chown $(whoami):$(whoami) "$LOG_PATH"
fi

echo -e "${GREEN}✅ Verificações iniciais concluídas${NC}"
echo ""

# Backup do crontab atual
echo -e "${BLUE}💾 Fazendo backup do crontab atual...${NC}"
crontab -l > "$LOG_PATH/crontab_backup_$(date +%Y%m%d_%H%M%S).txt" 2>/dev/null || echo "# Nenhum crontab existente" > "$LOG_PATH/crontab_backup_$(date +%Y%m%d_%H%M%S).txt"

# Criar arquivo temporário com os novos cron jobs
TEMP_CRON=$(mktemp)

# Manter crontab existente (se houver)
crontab -l 2>/dev/null >> "$TEMP_CRON" || true

# Adicionar separador
echo "" >> "$TEMP_CRON"
echo "# =============================================================================" >> "$TEMP_CRON"
echo "# SISTEMA DE BACKUP MULTI-TENANT - CRON JOBS AUTOMÁTICOS" >> "$TEMP_CRON"
echo "# Configurado em: $(date)" >> "$TEMP_CRON"
echo "# =============================================================================" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

# 1. BACKUP INCREMENTAL DIÁRIO (2h da manhã)
echo "# Backup incremental diário de todos os tenants (2h)" >> "$TEMP_CRON"
echo "0 2 * * * cd $PROJECT_PATH && $PHP_PATH spark backup:all-tenants --incremental >> $LOG_PATH/backup-incremental.log 2>&1" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

# 2. BACKUP COMPLETO SEMANAL (Domingo, 3h da manhã)
echo "# Backup completo semanal com upload remoto (Domingo, 3h)" >> "$TEMP_CRON"
echo "0 3 * * 0 cd $PROJECT_PATH && $PHP_PATH spark backup:all-tenants --full --upload >> $LOG_PATH/backup-full.log 2>&1" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

# 3. LIMPEZA DE BACKUPS ANTIGOS (4h da manhã)
echo "# Limpeza automática de backups antigos (4h)" >> "$TEMP_CRON"
echo "0 4 * * * cd $PROJECT_PATH && $PHP_PATH spark backup:cleanup >> $LOG_PATH/backup-cleanup.log 2>&1" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

# 4. TESTE DE RESTORE MENSAL (Primeiro domingo do mês, 5h)
echo "# Teste automático de restore mensal (Primeiro domingo, 5h)" >> "$TEMP_CRON"
echo "0 5 1-7 * 0 cd $PROJECT_PATH && $PHP_PATH spark backup:test-restore --all --notify >> $LOG_PATH/test-restore.log 2>&1" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

# 5. MONITORAMENTO DE ESPAÇO EM DISCO (A cada 6 horas)
echo "# Verificação de espaço em disco (a cada 6 horas)" >> "$TEMP_CRON"
echo "0 */6 * * * cd $PROJECT_PATH && $PHP_PATH spark backup:check-disk-space >> $LOG_PATH/disk-check.log 2>&1" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

# 6. ROTAÇÃO DE LOGS (Diário, 1h da manhã)
echo "# Rotação de logs do sistema de backup (1h)" >> "$TEMP_CRON"
echo "0 1 * * * find $LOG_PATH -name '*.log' -mtime +30 -delete" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

# 7. VERIFICAÇÃO DE SAÚDE DO SISTEMA (A cada 2 horas)
echo "# Verificação de saúde do sistema de backup (a cada 2 horas)" >> "$TEMP_CRON"
echo "0 */2 * * * cd $PROJECT_PATH && $PHP_PATH spark backup:health-check >> $LOG_PATH/health-check.log 2>&1" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

# 8. BACKUP DE EMERGÊNCIA (Se último backup > 48h)
echo "# Backup de emergência se último backup > 48h (a cada 4 horas)" >> "$TEMP_CRON"
echo "0 */4 * * * cd $PROJECT_PATH && $PHP_PATH spark backup:emergency-check >> $LOG_PATH/emergency-backup.log 2>&1" >> "$TEMP_CRON"
echo "" >> "$TEMP_CRON"

echo "# Fim da configuração do sistema de backup" >> "$TEMP_CRON"
echo "# =============================================================================" >> "$TEMP_CRON"

# Mostrar preview dos cron jobs que serão adicionados
echo -e "${BLUE}📋 CRON JOBS QUE SERÃO CONFIGURADOS:${NC}"
echo ""
echo -e "${GREEN}🔄 Backup Incremental Diário:${NC}"
echo "   • Horário: 02:00 (todos os dias)"
echo "   • Comando: backup:all-tenants --incremental"
echo "   • Log: $LOG_PATH/backup-incremental.log"
echo ""

echo -e "${GREEN}💾 Backup Completo Semanal:${NC}"
echo "   • Horário: 03:00 (domingos)"
echo "   • Comando: backup:all-tenants --full --upload"
echo "   • Log: $LOG_PATH/backup-full.log"
echo ""

echo -e "${GREEN}🧹 Limpeza Automática:${NC}"
echo "   • Horário: 04:00 (todos os dias)"
echo "   • Comando: backup:cleanup"
echo "   • Log: $LOG_PATH/backup-cleanup.log"
echo ""

echo -e "${GREEN}🧪 Teste de Restore Mensal:${NC}"
echo "   • Horário: 05:00 (primeiro domingo do mês)"
echo "   • Comando: backup:test-restore --all --notify"
echo "   • Log: $LOG_PATH/test-restore.log"
echo ""

echo -e "${GREEN}📊 Monitoramento de Disco:${NC}"
echo "   • Horário: A cada 6 horas"
echo "   • Comando: backup:check-disk-space"
echo "   • Log: $LOG_PATH/disk-check.log"
echo ""

echo -e "${GREEN}🔍 Verificação de Saúde:${NC}"
echo "   • Horário: A cada 2 horas"
echo "   • Comando: backup:health-check"
echo "   • Log: $LOG_PATH/health-check.log"
echo ""

echo -e "${GREEN}🚨 Backup de Emergência:${NC}"
echo "   • Horário: A cada 4 horas"
echo "   • Comando: backup:emergency-check"
echo "   • Log: $LOG_PATH/emergency-backup.log"
echo ""

# Confirmação
echo -e "${YELLOW}⚠️  ATENÇÃO: Esta operação irá modificar seu crontab atual!${NC}"
echo "Um backup do crontab atual foi salvo em: $LOG_PATH/"
echo ""
read -p "Deseja continuar com a instalação dos cron jobs? (s/N): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Ss]$ ]]; then
    # Instalar o novo crontab
    echo -e "${BLUE}⚙️  Instalando cron jobs...${NC}"
    crontab "$TEMP_CRON"
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Cron jobs instalados com sucesso!${NC}"
        echo ""
        
        # Verificar se cron service está rodando
        if systemctl is-active --quiet cron 2>/dev/null || systemctl is-active --quiet crond 2>/dev/null; then
            echo -e "${GREEN}✅ Serviço cron está ativo${NC}"
        else
            echo -e "${YELLOW}⚠️  Serviço cron pode não estar ativo. Verifique com:${NC}"
            echo "   sudo systemctl status cron"
            echo "   ou"
            echo "   sudo systemctl status crond"
        fi
        
        echo ""
        echo -e "${BLUE}📋 PRÓXIMOS PASSOS:${NC}"
        echo "1. Verificar logs em: $LOG_PATH/"
        echo "2. Testar um backup manual: cd $PROJECT_PATH && php spark backup:tenant 1 10 --full"
        echo "3. Verificar crontab: crontab -l"
        echo "4. Monitorar logs: tail -f $LOG_PATH/backup-incremental.log"
        echo ""
        
        echo -e "${GREEN}🎉 CONFIGURAÇÃO CONCLUÍDA COM SUCESSO!${NC}"
        echo "O sistema de backup está agora totalmente automatizado."
        
    else
        echo -e "${RED}❌ Erro ao instalar cron jobs${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}❌ Operação cancelada pelo usuário${NC}"
    echo "Nenhuma alteração foi feita no crontab"
fi

# Limpar arquivo temporário
rm -f "$TEMP_CRON"

echo ""
echo -e "${BLUE}=== CONFIGURAÇÃO FINALIZADA ===${NC}"
