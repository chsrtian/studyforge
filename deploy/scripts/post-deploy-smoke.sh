#!/usr/bin/env bash
set -euo pipefail

APP_DIR=${APP_DIR:-/var/www/studyforge}
cd "$APP_DIR"

echo "[STEP] Running migrations"
php artisan migrate --force --ansi

echo "[STEP] Rebuilding caches"
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi

echo "[STEP] Checking queue status"
php artisan deploy:queue-status --ansi

echo "[STEP] Running smoke test"
php artisan deploy:smoke-test --ansi

echo "[PASS] Post-deploy smoke checks completed"
