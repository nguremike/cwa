#!/usr/bin/env bash
# Archives the entire project tree except vendor and storage/backups.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT_DIR="${1:-$ROOT/storage/backups}"
mkdir -p "$OUT_DIR"

TS="$(date +%Y%m%d-%H%M%S)"
FILE="$OUT_DIR/files-$TS.tar.gz"

tar --exclude='./storage/backups' --exclude='./vendor' \
    -czf "$FILE" -C "$ROOT" .

echo "Wrote $FILE"