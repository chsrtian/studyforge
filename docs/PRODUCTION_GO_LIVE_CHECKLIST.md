# StudyForge Controlled Deployment Commands

## 1) One-Time Server Setup
`sudo apt update`
Expected: `Reading package lists... Done`

`sudo apt install -y nginx mysql-server php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip unzip git nodejs npm composer certbot python3-certbot-nginx`
Expected: `Setting up ...` and command exits `0`

`free -h`
Expected: `Mem:` total is at least `0.9Gi`

`df -h /`
Expected: `Avail` is at least `5G`

`nproc`
Expected: output is `1` or greater

## 2) Preferred Release Layout (Versioned Deployments)
`sudo mkdir -p /var/www/studyforge/{releases,shared}`
Expected: command exits `0`

`sudo mkdir -p /var/www/studyforge/shared/storage /var/www/studyforge/shared/bootstrap-cache`
Expected: command exits `0`

`sudo chown -R $USER:www-data /var/www/studyforge`
Expected: command exits `0`

`sudo chmod -R 775 /var/www/studyforge/shared/storage /var/www/studyforge/shared/bootstrap-cache`
Expected: command exits `0`

`RELEASE_ID=$(date +%Y%m%d%H%M%S) && RELEASE_PATH=/var/www/studyforge/releases/$RELEASE_ID && echo $RELEASE_PATH`
Expected: outputs a timestamped release path

`git clone <YOUR_REPO_URL> "$RELEASE_PATH"`
Expected: `Cloning into ...`

`cp "$RELEASE_PATH/.env.production.example" /var/www/studyforge/shared/.env`
Expected: command exits `0`

`rm -rf "$RELEASE_PATH/storage" "$RELEASE_PATH/bootstrap/cache"`
Expected: command exits `0`

`ln -s /var/www/studyforge/shared/storage "$RELEASE_PATH/storage"`
Expected: command exits `0`

`mkdir -p "$RELEASE_PATH/bootstrap" && ln -s /var/www/studyforge/shared/bootstrap-cache "$RELEASE_PATH/bootstrap/cache"`
Expected: command exits `0`

`ln -s /var/www/studyforge/shared/.env "$RELEASE_PATH/.env"`
Expected: command exits `0`

`echo "$RELEASE_PATH" > /tmp/studyforge_release_path`
Expected: command exits `0`

`readlink -f /var/www/studyforge/current > /tmp/studyforge_prev_release_path || true`
Expected: previous current release path is saved (or blank on first deploy)

## 3) Configure .env
Set values in `/var/www/studyforge/shared/.env`:
`APP_ENV=production`
`APP_DEBUG=false`
`SESSION_SECURE_COOKIE=true`
`AI_MOCK_MODE=false`
`QUEUE_HEALTH_TOKEN=<RANDOM_TOKEN>`
Expected: file saved

`RELEASE_PATH=$(cat /tmp/studyforge_release_path) && echo "$RELEASE_PATH"`
Expected: command exits `0`

`test -f /var/www/studyforge/shared/.env`
Expected: command exits `0`

`if ! grep -Eq '^APP_KEY=.+$' /var/www/studyforge/shared/.env; then cd "$RELEASE_PATH" && php artisan key:generate --force --ansi; fi`
Expected: if missing, `Application key set successfully.`; if present, no key regeneration

`grep -Eq '^APP_KEY=.+$' /var/www/studyforge/shared/.env`
Expected: command exits `0`

## 4) Node and Environment Parity Check
`export EXPECTED_NODE="<LOCAL_NODE_VERSION>"`
Expected: command exits `0`

`export EXPECTED_NPM="<LOCAL_NPM_VERSION>"`
Expected: command exits `0`

`node -v && npm -v && php -v | head -n 1 && composer --version`
Expected: versions are displayed

`test "$(node -v)" = "$EXPECTED_NODE"`
Expected: command exits `0`

`test "$(npm -v)" = "$EXPECTED_NPM"`
Expected: command exits `0`

## 5) Database Initialization (Before Migrations)
`sudo mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS studyforge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`
Expected: command exits `0`

`sudo mysql -u root -p -e "CREATE USER IF NOT EXISTS 'studyforge_user'@'localhost' IDENTIFIED BY '<STRONG_PASSWORD>';"`
Expected: command exits `0`

`sudo mysql -u root -p -e "GRANT ALL PRIVILEGES ON studyforge.* TO 'studyforge_user'@'localhost'; FLUSH PRIVILEGES;"`
Expected: command exits `0`

`sudo mysql -u root -p -e "SHOW DATABASES LIKE 'studyforge'; SELECT User,Host FROM mysql.user WHERE User='studyforge_user';"`
Expected: shows `studyforge` and `studyforge_user@localhost`

Update `/var/www/studyforge/shared/.env`:
`DB_DATABASE=studyforge`
`DB_USERNAME=studyforge_user`
`DB_PASSWORD=<STRONG_PASSWORD>`
Expected: file saved

## 6) Build Release
`if [ -L /var/www/studyforge/current ]; then cd /var/www/studyforge/current && php artisan down --retry=60 --ansi; fi`
Expected: `Application is now in maintenance mode.` (or skipped on first deploy)

`cd "$RELEASE_PATH"`
Expected: command exits `0`

`composer install --no-dev --optimize-autoloader --no-interaction`
Expected: `Generating optimized autoload files`

`npm ci && npm run build`
Expected: Vite build completes with command exit `0`

## 7) File Permissions for Laravel Runtime
`sudo chown -R www-data:www-data /var/www/studyforge/shared/storage /var/www/studyforge/shared/bootstrap-cache`
Expected: command exits `0`

`sudo find /var/www/studyforge/shared/storage /var/www/studyforge/shared/bootstrap-cache -type d -exec chmod 775 {} \;`
Expected: command exits `0`

`sudo find /var/www/studyforge/shared/storage /var/www/studyforge/shared/bootstrap-cache -type f -exec chmod 664 {} \;`
Expected: command exits `0`

`sudo -u www-data test -w /var/www/studyforge/shared/storage && sudo -u www-data test -w /var/www/studyforge/shared/bootstrap-cache`
Expected: command exits `0`

## 8) Run Pre-Deploy Gate
`APP_DIR="$RELEASE_PATH" bash "$RELEASE_PATH/deploy/scripts/pre-deploy.sh"`
Expected:
`[STEP] Deploy preflight starting`
`[PASS] Environment validation passed.`
`[PASS] Preflight completed successfully.`
`[PASS] Deploy preflight finished successfully`

## 9) Configure Services
`sudo cp deploy/systemd/studyforge-queue-worker.service /etc/systemd/system/studyforge-queue-worker.service`
Expected: command exits `0`

`sudo systemctl daemon-reload && sudo systemctl enable studyforge-queue-worker`
Expected: `Created symlink ... studyforge-queue-worker.service`

`sudo systemctl start studyforge-queue-worker`
Expected: command exits `0`

`sudo systemctl status studyforge-queue-worker --no-pager`
Expected: `Active: active (running)`

`sudo cp deploy/nginx/studyforge.conf /etc/nginx/sites-available/studyforge`
Expected: command exits `0`

`sudo ln -sf /etc/nginx/sites-available/studyforge /etc/nginx/sites-enabled/studyforge`
Expected: command exits `0`

`sudo nginx -t && sudo systemctl reload nginx`
Expected: `syntax is ok` and `test is successful`

`sudo certbot --nginx -d <YOUR_DOMAIN>`
Expected: `Congratulations! Your certificate and chain have been saved`

`sudo systemctl enable certbot.timer`
Expected: command exits `0`

## 10) Enable Scheduler
`(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/studyforge/current && php artisan schedule:run >> /dev/null 2>&1") | crontab -`
Expected: command exits `0`

## 11) Run Post-Deploy Smoke Checks
`ln -sfn "$RELEASE_PATH" /var/www/studyforge/current`
Expected: command exits `0`

`cd /var/www/studyforge/current`
Expected: command exits `0`

`php artisan config:clear --ansi && php artisan route:clear --ansi && php artisan view:clear --ansi`
Expected: clear commands complete with command exit `0`

`APP_DIR=/var/www/studyforge/current bash /var/www/studyforge/current/deploy/scripts/post-deploy-smoke.sh`
Expected:
`[STEP] Running migrations`
`[STEP] Checking queue status`
`[PASS] Queue health is acceptable.`
`[STEP] Running smoke test`
`[PASS] Text session creation: OK`
`[PASS] PDF session creation: OK`
`[PASS] Queue execution: OK`
`[PASS] Database writes: OK`
`[PASS] Post-deploy smoke checks completed`

`php artisan queue:restart --ansi`
Expected: `Broadcasting queue restart signal.`

`php artisan up --ansi`
Expected: `Application is now live.`

## 12) Queue Logging Visibility
`sudo journalctl -u studyforge-queue-worker --no-pager -n 100`
Expected: latest worker events are printed

`sudo journalctl -u studyforge-queue-worker -f --no-pager`
Expected: live worker logs stream continuously

## 13) Health Verification Commands
`curl "https://<YOUR_DOMAIN>/health/queue?token=<QUEUE_HEALTH_TOKEN>"`
Expected: JSON with `"success":true`

`php artisan deploy:queue-status --ansi`
Expected: `[PASS] Queue health is acceptable.`

`php artisan queue:failed`
Expected: `No failed jobs.`

## 14) Rollback (Exact Commands)
### 14.1 Before every deploy create backup
`cd /var/www/studyforge/current && git rev-parse HEAD > storage/app/predeploy_commit.txt`
Expected: command exits `0`

`mysqldump -u <DB_USER> -p<DB_PASSWORD> <DB_NAME> | gzip > /var/backups/studyforge_predeploy_$(date +%F_%H%M%S).sql.gz`
Expected: command exits `0`

`ls -1dt /var/www/studyforge/releases/* | head -n 5`
Expected: shows recent releases newest first

### 14.2 Rollback execution
`cd /var/www/studyforge`
Expected: command exits `0`

`PREV_REF=$(cat storage/app/predeploy_commit.txt)`
Expected: outputs commit hash

`PREV_RELEASE=$(ls -1dt /var/www/studyforge/releases/* | sed -n '2p') && echo "$PREV_RELEASE"`
Expected: outputs previous release path

`ln -sfn "$PREV_RELEASE" /var/www/studyforge/current`
Expected: command exits `0`

`readlink -f /var/www/studyforge/current`
Expected: prints previous release path

`bash deploy/scripts/rollback.sh "$PREV_REF" "/var/backups/<BACKUP_FILE>.sql.gz"`
Expected:
`[STEP] Checkout previous release`
`[STEP] Restore database backup`
`[STEP] Restart queue worker`
`[PASS] Rollback completed`

### 14.3 Rollback verification
`php artisan deploy:queue-status --ansi`
Expected: `[PASS] Queue health is acceptable.`

`php artisan test --testsuite=Feature --filter=Phase10ExecutionTest`
Expected: tests pass
