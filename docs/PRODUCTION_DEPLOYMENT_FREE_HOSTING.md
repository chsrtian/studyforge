# StudyForge Production Deployment (Free Hosting)

## Primary Recommendation: Oracle Cloud Always Free VM

### 1. Server Bootstrap
1. Create Ubuntu 22.04 VM (Always Free shape).
2. Install runtime packages:
   - `sudo apt update`
   - `sudo apt install -y nginx mysql-server php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip unzip git nodejs npm composer`
3. Clone app to `/var/www/studyforge`.

### 2. Application Setup
1. `cd /var/www/studyforge`
2. `cp .env.production.example .env`
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci && npm run build`
5. `php artisan key:generate`
6. `php artisan migrate --force`
7. `php artisan storage:link`
8. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

### 3. Queue Worker (Required)
1. Copy `deploy/systemd/studyforge-queue-worker.service` to `/etc/systemd/system/`.
2. `sudo systemctl daemon-reload`
3. `sudo systemctl enable studyforge-queue-worker`
4. `sudo systemctl start studyforge-queue-worker`
5. Verify: `sudo systemctl status studyforge-queue-worker`

### 4. Scheduler (Required)
1. Add cron entry for web user:
   - `* * * * * cd /var/www/studyforge && php artisan schedule:run >> /dev/null 2>&1`

### 5. Nginx
1. Copy `deploy/nginx/studyforge.conf` to `/etc/nginx/sites-available/studyforge`.
2. Enable site:
   - `sudo ln -s /etc/nginx/sites-available/studyforge /etc/nginx/sites-enabled/studyforge`
3. `sudo nginx -t && sudo systemctl reload nginx`

### 6. SSL (Let's Encrypt)
1. `sudo apt install -y certbot python3-certbot-nginx`
2. `sudo certbot --nginx -d your-domain.example`
3. Enable auto-renew:
   - `sudo systemctl enable certbot.timer`

### 7. Production Validation Checklist
1. APP settings: `APP_ENV=production`, `APP_DEBUG=false`.
2. Security: `SESSION_SECURE_COOKIE=true`, HTTPS only.
3. Queue: worker active and restarting automatically.
4. Logs: writable `storage/logs` and no credential leakage.
5. AI: `AI_MOCK_MODE=false` and provider keys valid.

## Fallback Platforms (Limitations)

### Render Free
- Limitation: services sleep; worker reliability is poor for long queues.
- Use only for demos. If used, keep queue small and accept delayed generation.

### Railway
- Limitation: free credits expire and sustained workers are not guaranteed.
- Suitable for short-term staging, not stable production.

### Koyeb Free
- Limitation: cold starts and background worker constraints.
- Works for low-traffic web frontends but not consistent AI queue workloads.

## Operational Commands
- Worker logs: `tail -f storage/logs/queue-worker.log`
- Failed jobs: `php artisan queue:failed`
- Retry failed: `php artisan queue:retry all`
- Health check: `php artisan about`
