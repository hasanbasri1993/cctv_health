# CCTV Early Warning — Startup Guide

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL running

---

## First-Time Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure database in .env
# DB_DATABASE=cctv_monitor
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Create MySQL database first:
# CREATE DATABASE cctv_monitor;

# 3. Run migrations + seed admin user
php artisan migrate
php artisan db:seed

# 4. Build frontend assets
npm run build
```

---

## Start (Development)

Run each in a separate terminal:

**Terminal 1 — Web server**
```bash
php artisan serve
```

**Terminal 2 — Queue worker** (polling jobs + notifications)
```bash
php artisan queue:work --tries=3
```

**Terminal 3 — Scheduler** (triggers polls every 1/2/5 min)
```bash
php artisan schedule:work
```

**Terminal 4 — Frontend hot-reload** (optional)
```bash
npm run dev
```

### Or single command (all-in-one)
```bash
composer run dev
```
Starts web server + queue + logs + Vite concurrently.

---

## Login

| Field    | Value                  |
|----------|------------------------|
| URL      | http://localhost:8000  |
| Email    | admin@example.com      |
| Password | password               |
| Role     | admin                  |

---

## Configure Notifications (Optional)

Edit `.env`:

```env
# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id

# Email recipients (comma-separated)
ALERT_EMAIL_RECIPIENTS=ops@example.com,admin@example.com
```

---

## Polling Intervals

Configurable in `.env` or via **Config** page in the UI (admin only):

| Setting                       | Default | Description                        |
|-------------------------------|---------|------------------------------------|
| POLLING_CHANNEL_INTERVAL      | 1 min   | Channel status check               |
| POLLING_DEVICE_INTERVAL       | 2 min   | Device health check                |
| POLLING_STORAGE_INTERVAL      | 5 min   | Storage health check               |
| NOTIFICATION_REMINDER_INTERVAL| 60 min  | Resend notification if unresolved  |

---

## Production

```bash
# Build and cache
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run queue worker as a service (Supervisor recommended)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# Run scheduler via cron — add to crontab:
# * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```
