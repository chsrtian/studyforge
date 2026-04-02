#!/usr/bin/env bash
set -euo pipefail

APP_DIR=${APP_DIR:-/var/www/studyforge}
cd "$APP_DIR"

echo "[STEP] Deploy preflight starting"
php artisan deploy:preflight --ansi

echo "[PASS] Deploy preflight finished successfully"
