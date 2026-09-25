#!/usr/bin/env bash
# Nightly DB backup with retention.
# Cron: 0 3 * * * /var/www/html/cwa/bin/backup-db.sh >> /var/log/cwa-backup.log 2>&1
set -euo pipefail

ROOT="/var/www/html/cwa"
OUT_DIR="$ROOT/storage/backups"
RETAIN_DAYS=30

mkdir -p "$OUT_DIR"

read -r DB_HOST DB_PORT DB_NAME DB_USER DB_PASS < <(
php -r '
$c = require "'"$ROOT"'/config/config.php";
$db = $c["db"];
echo $db["host"], " ", $db["port"], " ", $db["database"], " ", $db["username"], " ", $db["password"], PHP_EOL;
'
)

TS="$(date +%Y%m%d-%H%M%S)"
FILE="$OUT_DIR/db-$DB_NAME-$TS.sql.gz"

echo "[$(date +%F' '%T)] Starting backup → $FILE"

MYSQL_PWD="$DB_PASS" mysqldump \
    --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
    --single-transaction --routines --triggers --events \
    --default-character-set=utf8mb4 \
    "$DB_NAME" | gzip -9 > "$FILE"

SIZE="$(du -h "$FILE" | cut -f1)"
echo "[$(date +%F' '%T)] Backup written ($SIZE)"

# Retention
find "$OUT_DIR" -maxdepth 1 -name "db-*.sql.gz" -type f -mtime +"$RETAIN_DAYS" -print -delete

# Files backup weekly (Sundays)
if [ "$(date +%u)" = "7" ]; then
    echo "[$(date +%F' '%T)] Weekly file archive starting"
    "$ROOT/bin/backup-files.sh" "$OUT_DIR"
fi

echo "[$(date +%F' '%T)] Done"