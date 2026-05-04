# CCTV Early Warning Monitor

Web-based CCTV monitoring dashboard powered by Laravel + Vue 3 + Inertia.js + Chart.js. Connects to Hikvision DVRs via ISAPI to track device health, channel status, storage, and alerts.

## Features

- **Live Dashboard** — KPI row (total cameras, online, offline, active alerts), camera activity chart (24h), status breakdown donut with category list
- **Device Issues Table** — filterable table showing devices with problems (camera offline, video loss, storage fault, active alerts), click category tabs to filter, hover badges for details
- **Device Management** — add, edit, delete DVRs; view channels and storage per device; health history with response time + temperature chart
- **Alert Center** — filter by status (active/acknowledged/resolved) and severity; acknowledge alerts; alert details with timeline
- **Dark/Light Theme** — toggle via navbar button, preference persisted in localStorage
- **Hikvision ISAPI Integration** — polls channel status, SMART health, storage status via XML ISAPI endpoints
- **Notifications** — Telegram bot + email on new alerts

---

## Tech Stack

| Layer | Tech |
|-------|------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3, Inertia.js, Tailwind CSS v3 |
| Charts | Chart.js |
| Database | MySQL |
| Queue | Laravel Queue + Redis/Database |
| Auth | Laravel Breeze (email + password) |
| API | Hikvision ISAPI XML |

---

## How to Use

### 1. Add a Device (DVR)

1. Go to **Devices** → **Add Device**
2. Fill in:
   - **Name** — friendly label (e.g. "NVR-MAIN", "Back Gate DVR")
   - **IP Address** — Hikvision DVR IP (e.g. `192.168.1.64`)
   - **Port** — ISAPI port (default `80` or `8080`)
   - **Username** / **Password** — DVR credentials
3. Click **Save**. System immediately begins polling.

### 2. Monitor Dashboard

- **KPI Row** — top of Dashboard shows live counts
- **Camera Activity Chart** — dashed line = motion events, solid cyan = online cameras
- **Status Breakdown** — donut chart + category list (Camera Offline, Video Loss, Communication Exception, Recording Exception, No Recording Schedule Config, Arming Exception)
- **Device Issues Table** — devices with problems. Click any category tab to filter. Hover "N ch" or "fault" badges for details.

### 3. Acknowledge an Alert

1. Go to **Alerts**
2. Find active alert → click **Acknowledge** button (cyan)
3. Alert status changes to "Acknowledged", badge turns amber

### 4. Configure Polling Intervals

Admin only — go to **Config** page:
- Channel status check interval (default 1 min)
- Device health check interval (default 2 min)
- Storage health check interval (default 5 min)

### 5. Set Up Notifications

Edit `.env`:
```env
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
ALERT_EMAIL_RECIPIENTS=ops@example.com,admin@example.com
```

---

## How to Edit / Manage

### Add or Edit a Device
**Devices** → **Add Device** or click device name → **Edit**. IP, port, and credentials are editable. Leave password blank to keep current.

### Change Polling Intervals
**Config** page (admin) or edit `.env`:
```env
POLLING_CHANNEL_INTERVAL=1
POLLING_DEVICE_INTERVAL=2
POLLING_STORAGE_INTERVAL=5
```

### Manage Users
Admin: **Config** page → User Management section.
Regular users: login at `/profile` to update name, email, password.

### View Device Health History
Click any device name → **Show** page → **Health History** tab. Chart shows response time (left axis) + temperature (right axis) for last 120 readings.

### Toggle Dark/Light Theme
Click the **sun/moon** icon in the top navbar. Preference saved in localStorage.

---

## Installation (Development)

```bash
# Install PHP + JS dependencies
composer install
npm install

# Configure .env — set DB_* and optional TELEGRAM_* vars
cp .env.example .env

# Create MySQL database
# CREATE DATABASE cctv_monitor;

# Run migrations + seed admin account
php artisan migrate
php artisan db:seed

# Build frontend
npm run build

# Start all services (web + queue + scheduler)
composer run dev
```

**Default admin login:**
- URL: `http://localhost:8000`
- Email: `admin@example.com`
- Password: `password`

### Services (separate terminals for development)

```bash
php artisan serve                           # Web server (port 8000)
php artisan queue:work --tries=3           # Queue worker
php artisan schedule:work                  # Scheduler
npm run dev                                # Vite hot-reload (optional)
```

---

## Production Deployment

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run queue worker as daemon (Supervisor recommended)
php artisan queue:work --sleep=3 --tries=3

# Scheduler — add to crontab:
# * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Project Structure

```
app/
├── Http/Controllers/
│   ├── DashboardController.php       # Dashboard data + stats
│   ├── DeviceController.php          # CRUD + health history
│   ├── AlertController.php           # Alert list + acknowledge
│   └── ConfigurationController.php   # Polling intervals + notif settings
├── Jobs/
│   ├── PollDeviceHealthJob.php      # Device online/offline + response time
│   ├── PollChannelStatusJob.php      # Channel video loss detection
│   └── PollStorageStatusJob.php      # SMART health + temperature
├── Models/
│   ├── Device.php
│   ├── DeviceChannel.php             # status: ok / no_video / disabled
│   ├── DeviceStorage.php              # health_status: healthy / fault / unknown
│   ├── DeviceHealthLog.php            # Health history with temperature
│   └── Alert.php                      # Active, acknowledged, resolved alerts
└── Services/
    └── HikvisionISAPIService.php     # ISAPI XML polling + parsing

resources/js/
├── Pages/
│   ├── Dashboard.vue                  # KPI + charts + device issues table
│   ├── Devices/{Index,Create,Edit,Show}.vue
│   ├── Alerts/Index.vue
│   └── Configuration/Index.vue
├── Layouts/
│   ├── AuthenticatedLayout.vue        # Dark navbar + theme toggle
│   └── GuestLayout.vue
└── Composables/
    └── useColorMode.js               # Dark/light toggle + localStorage
```
