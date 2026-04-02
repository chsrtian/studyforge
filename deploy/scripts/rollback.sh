#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 2 ]; then
  echo "Usage: ./deploy/scripts/rollback.sh <previous_git_ref> <db_backup_sql_gz_path>"
  exit 1
fi

PREVIOUS_REF="$1"
DB_BACKUP_FILE="$2"
APP_DIR=${APP_DIR:-/var/www/studyforge}
DB_NAME=${DB_NAME:?DB_NAME is required}
DB_USER=${DB_USER:?DB_USER is required}
DB_PASSWORD=${DB_PASSWORD:?DB_PASSWORD is required}

cd "$APP_DIR"

echo "[STEP] Checkout previous release"
git fetch --all --tags
git checkout "$PREVIOUS_REF"

echo "[STEP] Restore dependencies"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

echo "[STEP] Restore database backup"
gzip -dc "$DB_BACKUP_FILE" | MYSQL_PWD="$DB_PASSWORD" mysql -u "$DB_USER" "$DB_NAME"

echo "[STEP] Rebuild caches"
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi

echo "[STEP] Restart queue worker"
sudo systemctl restart studyforge-queue-worker

echo "[PASS] Rollback completed"
