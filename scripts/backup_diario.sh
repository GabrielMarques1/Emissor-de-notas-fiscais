#!/usr/bin/env bash

# Uso:
#   DB_HOST="seu-host" \
#   DB_USER="seu-usuario" \
#   DB_PASS="sua-senha" \
#   DB_NAME="seu_banco" \
#   ./backup_diario.sh

set -euo pipefail

DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/banco}"

if [[ -z "$DB_NAME" ]]; then
  echo "[ERRO] Variável DB_NAME não informada." >&2
  exit 1
fi

mkdir -p "$BACKUP_DIR"

TIMESTAMP="$(date +"%Y-%m-%d_%H-%M")"
BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"

echo "Iniciando backup do banco '$DB_NAME' em $TIMESTAMP..."

mysqldump \
  --host="$DB_HOST" \
  --user="$DB_USER" \
  --password="$DB_PASS" \
  --routines --events --triggers \
  --single-transaction \
  "$DB_NAME" > "$BACKUP_FILE"

echo "Backup concluído com sucesso: $BACKUP_FILE"


