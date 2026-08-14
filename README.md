# Senbet School Management System

A comprehensive school management system featuring a Laravel backend, Vue.js frontend dashboard, and a Telegram Bot interface for real-time interaction.

## Features

- **Admin Dashboard** — Modern, responsive Vue.js frontend for managing users, feedback, and Senbet School memberships.
- **Multi-Role Users** — A single user can hold multiple roles simultaneously (Student, Teacher, Administrator, etc.).
- **Senbet School Membership** — Optional membership tracking with emergency contacts, education level, and document uploads.
- **Telegram Bot Integration** — Powered by Nutgram, allowing users to submit feedback directly via Telegram.
- **Role-Based Access Control** — Granular permissions system for managing different staff access levels.
- **Real-time Updates** — Integrated with Laravel Reverb for real-time WebSocket communication.
- **Background Processing** — Robust queue system for handling async tasks like notifications and data imports.

---

## Prerequisites

Ensure the following are installed on your machine:

| Requirement | Version |
| --- | --- |
| PHP | 8.2 or higher |
| Composer | Latest |
| Node.js & npm | 18+ recommended |
| PostgreSQL | 14+ recommended |
| Git | Any recent version |

---

## Step-by-Step Setup for New Users

### Step 1 — Clone the repository

```bash
git clone <your-repo-url>
cd Bot
```

### Step 2 — Install all dependencies

This single command installs PHP and Node dependencies, copies `.env.example` to `.env`, generates the app key, runs migrations, and builds the frontend:

```bash
composer setup
```

> ⚠️ **STOP HERE** — Do **not** run the app yet. You must configure your `.env` file first (Step 3), otherwise you will get database and authentication errors.

---

### Step 3 — Configure your `.env` file

Open the `.env` file in your editor and update the following values:

#### Database (PostgreSQL)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_postgres_username
DB_PASSWORD=your_postgres_password
```

> **Tip for Linux users using peer auth (no password):**
> If your PostgreSQL user matches your system user, you can use:
>
> ```env
> DB_HOST=/var/run/postgresql
> DB_PASSWORD=
> ```

#### Telegram Bot

Create a bot via [@BotFather](https://t.me/BotFather) on Telegram and copy the token:

```env
TELEGRAM_TOKEN="your_telegram_bot_token_here"
TELEGRAM_WEBHOOK_URL="https://yourdomain.com/api/telegram/webhook"
```

> For **local development** with polling (no webhook needed), you can leave `TELEGRAM_WEBHOOK_URL` blank.

#### Application URL

```env
APP_URL=http://localhost:8000
```

#### JWT Secret

```env
JWT_SECRET="your_long_random_secret_string_here"
```

---

### Step 4 — Create the PostgreSQL database

If you haven't created the database yet:

```bash
# Using postgres superuser
psql -U postgres -c "CREATE DATABASE your_database_name;"

# Or using your system user (Linux peer auth)
createdb your_database_name
```

---

### Step 5 — Run migrations and seed the database

```bash
php artisan migrate:fresh --seed
```

This will create all tables, seed permissions, roles, and default test users.

**Default login accounts after seeding:**

| Email | Password | Role |
| --- | --- | --- |
| `superadmin@lms.com` | `password123` | Super Admin |
| `admin@lms.com` | `password123` | Administrator |
| `teacher@lms.com` | `password123` | Teacher |
| `student@lms.com` | `password123` | Student |

---

### Step 6 — Set up Laravel Passport OAuth ⚠️ CRITICAL

This is the most common cause of **"client not found"**, **"personal access client not found"**, or **401 Unauthorized** errors.

#### 6a. Install Passport keys and clients

```bash
php artisan passport:install
```

This generates the encryption keys and creates the OAuth clients in your database. The output will show two clients.

#### 6b. Copy the client credentials to `.env`

Look for the **Personal Access Client** (usually the second entry) in the output and add its credentials to your `.env`:

```env
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=<uuid-from-output>
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=<secret-from-output>
```

#### 6c. Clear config cache

```bash
php artisan config:clear
```

> **Important:** Every time you run `migrate:fresh`, the OAuth clients are deleted from the database. You **must** run `php artisan passport:install` again after every fresh migration and update your `.env` with the new values.

---

### Step 7 — Link storage for file uploads

```bash
php artisan storage:link
```

This makes the `storage/app/public` folder publicly accessible at `/storage`.

---

### Step 8 — Build frontend assets

If `composer setup` already built assets, skip this. To rebuild manually:

```bash
npm run build
```

---

## Running the Application

### Development Mode (with hot reload)

Starts the Laravel server, Vite dev server, Reverb WebSocket, queue listener, and Telegram bot concurrently:

```bash
composer dev
```

Access the dashboard at: **<http://localhost:8000>**

### Production Mode

Runs migrations, optimizes config, and starts all services:

```bash
composer prod-start
```

---

## Common Errors & Fixes

### ❌ "Client not found" / "Personal access client not found"

**Cause:** Laravel Passport OAuth clients don't exist in the database (e.g., after a fresh migration).

**Fix:**

```bash
php artisan passport:install
```

Then update `PASSPORT_PERSONAL_ACCESS_CLIENT_ID` and `PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET` in your `.env`, then run:

```bash
php artisan config:clear
```

---

### ❌ "Unauthenticated" / "401" on all API requests

**Cause:** Passport is not configured, or the client IDs in `.env` don't match the database.

**Fix:**

```bash
php artisan passport:install
# Update .env with new client credentials, then:
php artisan config:clear
php artisan cache:clear
```

---

### ❌ "Class App\Constants\Type not found"

**Cause:** Composer autoload cache is stale.

**Fix:**

```bash
composer dump-autoload
```

---

### ❌ "SQLSTATE: Duplicate table" on migration

**Cause:** You ran `php artisan migrate` on an already-seeded database.

**Fix:**

```bash
# Drops all data and recreates from scratch:
php artisan migrate:fresh --seed
# OR to only run new migrations without dropping data:
php artisan migrate
```

---

### ❌ Session terminates on page refresh

**Cause:** The `SESSION_DRIVER` is incorrect.

**Fix:** Make sure your `.env` has:

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

---

### ❌ "Bad Gateway" Telegram bot error

**Cause:** The Telegram token is wrong or the Telegram API is temporarily unreachable. The bot automatically restarts.

**Fix:**

1. Verify your `TELEGRAM_TOKEN` in `.env` is correct.
2. For local development, the error is harmless — the bot retries every 5 seconds automatically.

---

### ❌ Broken images / uploaded files not accessible

**Cause:** The storage symlink is missing.

**Fix:**

```bash
php artisan storage:link
```

---

## Quick Full Reset (Start Fresh)

If you need to completely reset everything after cloning or after a database wipe:

```bash
# 1. Drop and recreate all tables + seed data
php artisan migrate:fresh --seed

# 2. Recreate OAuth clients and update .env with new credentials
php artisan passport:install

# 3. Link storage for file uploads
php artisan storage:link

# 4. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

> After `passport:install`, copy the **Personal Access Client** UUID and Secret into your `.env` file before starting the app.

---

## Environment Variable Reference

| Variable | Description | Example |
| --- | --- | --- |
| `APP_KEY` | Laravel app encryption key (auto-generated) | `base64:...` |
| `APP_URL` | Public URL of your app | `http://localhost:8000` |
| `DB_CONNECTION` | Database driver | `pgsql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_DATABASE` | Database name | `senbet_db` |
| `DB_USERNAME` | Database user | `postgres` |
| `DB_PASSWORD` | Database password | `secret` |
| `SESSION_DRIVER` | Session storage driver | `file` |
| `QUEUE_CONNECTION` | Queue driver | `sync` (dev) / `database` (prod) |
| `TELEGRAM_TOKEN` | Telegram Bot token from BotFather | `123456:ABC...` |
| `JWT_SECRET` | Secret key for JWT authentication | any long random string |
| `PASSPORT_PERSONAL_ACCESS_CLIENT_ID` | Passport OAuth client UUID | from `passport:install` output |
| `PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET` | Passport OAuth client secret | from `passport:install` output |

---

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

php artisan passport:client --personal --name="Senbet Personal Access Client"
