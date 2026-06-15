# FocusFlow — Production Deployment Guide

This document outlines the requirements and steps for deploying the FocusFlow SaaS application in a production environment.

## 🚀 Recommended Hosting Platforms

FocusFlow utilizes several long-running processes (WebSockets via Reverb, and queues via Laravel Horizon). Consequently, standard shared hosting is insufficient. We recommend:

1. **Laravel Forge + DigitalOcean/AWS** (Recommended): Automated server provisioning, daemon management, and secure deployment keys.
2. **Fly.io** or **Render**: Docker-based deployments that natively support running multiple containers (web server, worker, Reverb).

---

## ⚙️ Required Production `.env` Settings

Ensure these values are configured in your production environment:

```env
# Application Context
APP_NAME=FocusFlow
APP_ENV=production
APP_DEBUG=false
APP_URL=https://focusflow.yourdomain.com
APP_KEY=base64:YOUR_GENERATE_APP_KEY

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=focusflow_production
DB_USERNAME=focusflow_db_user
DB_PASSWORD=YOUR_SECURE_PASSWORD

# Redis & Cache (Required for WebSockets and Queue lockups)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Laravel Reverb (WebSockets)
REVERB_APP_ID=YOUR_PRODUCTION_REVERB_ID
REVERB_APP_KEY=YOUR_PRODUCTION_REVERB_KEY
REVERB_APP_SECRET=YOUR_PRODUCTION_REVERB_SECRET
REVERB_HOST=focusflow.yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Stripe / Cashier Configuration
STRIPE_KEY=pk_live_your_live_key
STRIPE_SECRET=sk_live_your_live_secret
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
CASHIER_CURRENCY=USD

# Email Delivery
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@yourdomain.com
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
```

---

## 📦 Build & Deployment Sequence

Run the following commands on your production server during a deployment:

### 1. Composer & npm Installation
```bash
# Install PHP production dependencies
composer install --no-dev --optimize-autoloader

# Install front-end dependencies and compile assets
pnpm install
npm run build
```

### 2. Cache Optimization
```bash
# Cache configuration, routes, and views for speed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Database Migration
```bash
# Run database migrations with the force flag to confirm execution in production
php artisan migrate --force
```

---

## 🤖 Long-Running Daemons (Supervisor Setup)

FocusFlow relies on active services to handle background jobs and real-time WebSocket communication. You **must** configure these as daemons managed by a tool like **Supervisor**.

### 1. Queue Worker (Laravel Horizon)
Do not use standard `queue:work`. Use **Horizon** to manage supervisor pools.

**Supervisor Config (`/etc/supervisor/conf.d/horizon.conf`):**
```ini
[program:horizon]
process_name=%(program_name)s
command=php /home/forge/focusflow.yourdomain.com/artisan horizon
autostart=true
autorestart=true
user=forge
redirect_stderr=true
stdout_logfile=/home/forge/focusflow.yourdomain.com/storage/logs/horizon.log
stopwaitsecs=3600
```

### 2. WebSocket Server (Laravel Reverb)
Reverb needs to run continuously on the configured host/port to broadcast updates.

**Supervisor Config (`/etc/supervisor/conf.d/reverb.conf`):**
```ini
[program:reverb]
process_name=%(program_name)s
command=php /home/forge/focusflow.yourdomain.com/artisan reverb:start --host=0.0.0.0 --port=8081
autostart=true
autorestart=true
user=forge
redirect_stderr=true
stdout_logfile=/home/forge/focusflow.yourdomain.com/storage/logs/reverb.log
```

---

## 🛡️ Post-Deployment Health Check

After running migrations and starting the daemons:
1. Hit `https://focusflow.yourdomain.com/up` to verify the main application is running (returns `OK` status).
2. Visit the billing page as an admin to check the Stripe integration handshake.
3. Open the Kanban board in two tabs to ensure Reverb WebSocket channels authenticate and sync successfully.
