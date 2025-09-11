#!/usr/bin/env bash

# Faz backup de múltiplos bancos MySQL.
# Suporta:
#  - Lista explícita em DB_NAMES="db1 db2 db3"
#  - Descoberta por prefixo: DB_PREFIX="erp_" (faz backup de todos que começam com prefixo)
#  - Compressão gzip
#  - Retenção por dias
#  - Upload opcional via rclone
#
# Variáveis de ambiente:
#   DB_HOST (default: localhost)
#   DB_USER (default: root)
#   DB_PASS (default: vazio)
#   DB_NAMES (lista separada por espaço) OU DB_PREFIX
#   BACKUP_DIR (default: /var/backups/banco)
#   RETENTION_DAYS (default: 7)
#   RCLONE_REMOTE (opcional: ex. "gdrive:erp-backups")
#   RCLONE_FLAGS (opcional: ex. "--transfers=2 --checkers=4")

set -euo pipefail

DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAMES="${DB_NAMES:-}"
DB_PREFIX="${DB_PREFIX:-}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/banco}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"
RCLONE_REMOTE="${RCLONE_REMOTE:-}"
RCLONE_FLAGS="${RCLONE_FLAGS:-}"

mkdir -p "$BACKUP_DIR"

# Descobrir bancos por prefixo, se não informado DB_NAMES
if [[ -z "$DB_NAMES" && -n "$DB_PREFIX" ]]; then
  MAPFILE -t DBS < <(mysql \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    -N -e "SHOW DATABASES LIKE '${DB_PREFIX}%';")
else
  # Converte string em array
  read -r -a DBS <<< "$DB_NAMES"
fi

if [[ ${#DBS[@]} -eq 0 ]]; then
  echo "[ERRO] Nenhum banco encontrado para backup (DB_NAMES/DB_PREFIX)." >&2
  exit 1
fi

echo "Iniciando backup de ${#DBS[@]} banco(s)..."
TIMESTAMP="$(date +"%Y-%m-%d_%H-%M")"

for DB in "${DBS[@]}"; do
  [[ -z "$DB" ]] && continue
  OUT_SQL="$BACKUP_DIR/backup_${DB}_${TIMESTAMP}.sql"
  OUT_GZ="$OUT_SQL.gz"
  echo "- Dump: $DB -> $OUT_GZ"
  mysqldump \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --routines --events --triggers \
    --single-transaction \
    "$DB" | gzip -9 > "$OUT_GZ"
done

if [[ -n "$RETENTION_DAYS" && "$RETENTION_DAYS" =~ ^[0-9]+$ && "$RETENTION_DAYS" -gt 0 ]]; then
  echo "Aplicando retenção: ${RETENTION_DAYS} dias"
  find "$BACKUP_DIR" -type f -name 'backup_*.sql.gz' -mtime +"$RETENTION_DAYS" -delete || true
else
  echo "Retenção desativada (nenhum arquivo será removido)."
fi

if [[ -n "$RCLONE_REMOTE" ]]; then
  echo "Enviando para remote via rclone: $RCLONE_REMOTE"
  rclone copy "$BACKUP_DIR" "$RCLONE_REMOTE" $RCLONE_FLAGS --min-age 1m || true
fi

echo "Backup finalizado com sucesso."


