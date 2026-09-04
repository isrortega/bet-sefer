#!/usr/bin/env bash
set -euo pipefail

# Nightly PostgreSQL dump for Bet-Sefer. Keeps 7 backups on the host.
# Add to cron as (root or user with docker access):
#   30 3 * * * /home/iromero/dev/bet-sefer/backend/ops/backup.sh >> /var/log/betsefer-backup.log 2>&1

cd "$(dirname "$0")/.."

if [ -f .env ]; then
    set -a
    # shellcheck disable=SC1091
    . ./.env
    set +a
fi

DB_USER="${DB_USERNAME:-betsefer}"
DB_NAME="${DB_DATABASE:-betsefer}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/betsefer}"
STAMP="$(date +%Y%m%d-%H%M%S)"
FILE="${BACKUP_DIR}/betsefer-${STAMP}.sql.gz"

mkdir -p "${BACKUP_DIR}"

docker compose -f docker-compose.prod.yml exec -T postgres \
    pg_dump -U "${DB_USER}" "${DB_NAME}" | gzip > "${FILE}"

# Retain the last 7 dumps.
find "${BACKUP_DIR}" -name 'betsefer-*.sql.gz' -mtime +7 -delete

echo "backup written: ${FILE}"
