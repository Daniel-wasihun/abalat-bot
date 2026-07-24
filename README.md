# ⛪ EOTC Sunday School Telegram Feedback & Broadcast System

A complete, production-ready Telegram Bot and Web Dashboard built for the **Ethiopian Orthodox Tewahedo Church (EOTC) Sunday School**. This system allows the Sunday School administration to collect categorized feedback from members in three languages (Amharic, Oromifa, English), manage incoming inquiries via a beautiful admin dashboard, and broadcast messages back to specific groups of subscribers.

## ✨ Key Features

- **Multi-lingual Telegram Bot**: Supports Amharic (default), Oromifa, and English. Users can switch their preferred language on the fly.
- **EOTC Specific Categories**: Feedback is natively categorized into church-specific areas:
  - Spiritual Education (ትምህርተ ሃይማኖትና መንፈሳዊ ትምህርት)
  - Choir & Hymns (መዝሙርና ማኅሌት አገልግሎት)
  - Liturgy & Service (ሥርዓተ አምልኮና ቅዳሴ)
  - General Inquiry (አጠቃላይ ጥያቄና አስተያየት)
- **Comprehensive Admin Dashboard**: A responsive, modern Vue 3 interface powered by Tailwind CSS v4.
- **Broadcast Wizard**: Send rich broadcast messages (with photos or documents) to targeted segments or manually selected users.
- **Data Persistence**: Uses Google Cloud Firestore for a scalable production database with an automatic fallback to local JSON files if the network or credentials are unavailable.
- **Exporting**: Generate detailed PDF and CSV reports for administrative review.
- **Asynchronous Processing**: Uses Laravel Queues to respect Telegram's strict rate limits (30 msgs/sec) during mass broadcasts.

## 🛠 Tech Stack

- **Backend**: Laravel 11, PHP 8.3
- **Frontend**: Vue 3 (Composition API), Vite, Tailwind CSS v4
- **Database**: Google Cloud Firestore (via `google/cloud-firestore`) & Local JSON Storage
- **Authentication**: Custom JWT implementation for the admin panel

---

## 💻 Development Setup

Follow these steps to set up the project on your local machine for development.

### 1. Prerequisites
- **PHP** >= 8.2
- **Composer** (PHP Package Manager)
- **Node.js** >= 18 & **npm**
- **Telegram Bot Token** (Get it from [@BotFather](https://t.me/BotFather))

### 2. Installation

Clone the repository and install dependencies:
```bash
git clone <repository-url>
cd Bot

# Install PHP dependencies
composer install

# Install JS dependencies
npm install
```

### 3. Environment Configuration

Copy the `.env.example` file to `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

Update your `.env` file with your specific variables. Pay special attention to:
```env
# Telegram Bot
TELEGRAM_BOT_TOKEN="your-bot-token"
TELEGRAM_WEBHOOK_URL="https://your-ngrok-url.ngrok.app/api/telegram/webhook"

# Firebase (Optional for local dev, will fallback to local JSON if omitted)
FIREBASE_PROJECT_ID="abalat-guday"
FIREBASE_CREDENTIALS_PATH="storage/app/firebase-credentials.json"

# Admin Auth Secret
JWT_SECRET="generate-a-long-random-string-here"
```

### 4. Running the Development Servers

You will need multiple terminal tabs to run the backend, frontend, and queue worker.

**Tab 1: Start the Laravel Backend server**
```bash
php artisan serve
```

**Tab 2: Start the Vite Frontend server**
```bash
npm run dev
```

**Tab 3: Start the Laravel Queue Worker (for broadcasting messages)**
```bash
php artisan queue:work
```

### 5. Setting up the Telegram Webhook (Local)
To test the Telegram bot locally, you must expose your local server to the internet using a tool like [ngrok](https://ngrok.com/).
```bash
ngrok http 8000
```
Copy the secure `https` URL from ngrok, paste it into `TELEGRAM_WEBHOOK_URL` in your `.env`, and register the webhook:
```bash
php artisan telegram:set-webhook
```

### 6. Accessing the Dashboard
Go to `http://localhost:8000` in your browser. 
If the database is fresh, the system will automatically seed a Super Admin user upon your first login attempt.
- **Default Email:** `admin@example.com`
- **Default Password:** `password123`

*(Change this password immediately after logging in!)*

---

## 🚀 Production Deployment

Deploying to production requires optimizing the application and ensuring strict security measures.

### 1. Server Requirements
Ensure your production server (e.g., Ubuntu VPS, AWS EC2, DigitalOcean Droplet) has PHP, Composer, Node.js, and a web server like Nginx or Apache installed.

### 2. Prepare the Environment
Pull the code to your production server and install dependencies without development packages:
```bash
composer install --optimize-autoloader --no-dev
npm install
```

### 3. Build Frontend Assets
Compile the Vue application and Tailwind CSS for production:
```bash
npm run build
```

### 4. Configure Production `.env`
Ensure your `.env` is set for production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.com

# Ensure this points to your actual domain
TELEGRAM_WEBHOOK_URL="https://your-production-domain.com/api/telegram/webhook"
```
Place your actual Firebase JSON credentials at `storage/app/firebase-credentials.json` to enable cloud persistence.

### 5. Optimize Laravel
Cache the configuration, routes, and views to maximize performance:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Register the Production Webhook
Tell Telegram where to send live updates:
```bash
php artisan telegram:set-webhook
```

### 7. Run the Queue Worker as a Daemon
In production, you should **not** run `php artisan queue:work` manually. Instead, use a process monitor like **Supervisor** to keep the queue worker running permanently in the background.

Create a Supervisor configuration file (`/etc/supervisor/conf.d/bot-worker.conf`):
```ini
[program:bot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/Bot/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/Bot/storage/logs/worker.log
stopwaitsecs=3600
```
Update supervisor to read the new config:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bot-worker:*
```

### 8. Web Server Configuration (Nginx Example)
Point your web server to the `public` directory.
```nginx
server {
    listen 80;
    server_name your-production-domain.com;
    root /path/to/your/Bot/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 🔒 Security Best Practices
- **Restrict Storage Permissions**: Ensure the `storage` and `bootstrap/cache` directories are writable by the web server (e.g., `chmod -R 775 storage bootstrap/cache` and `chown -R www-data:www-data storage bootstrap/cache`).
- **Protect Credentials**: Never commit your `.env` or `storage/app/firebase-credentials.json` files to source control. They are already in `.gitignore`.
- **Change Default Admin**: Update the default `admin@example.com` password immediately after your first login on production.
- **SSL Certificate**: Telegram requires webhooks to run over HTTPS. Ensure your production domain is secured with an SSL certificate (e.g., Let's Encrypt).
