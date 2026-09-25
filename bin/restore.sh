#!/usr/bin/env bash
# Restore a DB dump. Usage: bin/restore.sh storage/backups/db-...sql.gz
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DUMP="$1"
[ -f "$DUMP" ] || { echo "File not found: $DUMP"; exit 1; }

read -r DB_HOST DB_PORT DB_NAME DB_USER DB_PASS < <(
php -r '
$c = require "'"$ROOT"'/config/config.php";
$db = $c["db"];
echo $db["host"], " ", $db["port"], " ", $db["database"], " ", $db["username"], " ", $db["password"], PHP_EOL;
'
)

read -rp "This will overwrite the current database '$DB_NAME'. Continue? [y/N] " yn
[[ "$yn" == "y" || "$yn" == "Y" ]] || exit 1

gunzip -c "$DUMP" | MYSQL_PWD="$DB_PASS" mysql \
    --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
    --default-character-set=utf8mb4 "$DB_NAME"

echo "Restored $DUMP into $DB_NAME"