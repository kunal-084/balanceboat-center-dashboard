# BalanceBoat Center OS — Local Installation Guide

A step-by-step guide to running BalanceBoat Center OS on your local machine (Windows, macOS, or Linux).

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Extract the Project](#2-extract-the-project)
3. [Install PHP Dependencies](#3-install-php-dependencies)
4. [Configure the Environment](#4-configure-the-environment)
5. [Create the Database](#5-create-the-database)
6. [Run Migrations & Seed Data](#6-run-migrations--seed-data)
7. [Install Frontend Dependencies](#7-install-frontend-dependencies)
8. [Storage & File System Setup](#8-storage--file-system-setup)
9. [Start the Application](#9-start-the-application)
10. [Start the Queue Worker](#10-start-the-queue-worker)
11. [Optional Integrations](#11-optional-integrations)
12. [Default Login Credentials](#12-default-login-credentials)
13. [Troubleshooting](#13-troubleshooting)

---

## 1. Prerequisites

Install all of the following before proceeding. The version numbers are the **minimum required**.

### Required

| Tool | Minimum Version | How to Check |
|------|----------------|--------------|
| PHP | 8.2 | `php -v` |
| Composer | 2.x | `composer --version` |
| Node.js | 18.x | `node -v` |
| npm | 9.x | `npm -v` |
| MySQL / MariaDB | 8.0 / 10.6 | `mysql --version` |

### Required PHP Extensions

Your PHP installation must have these extensions enabled. Check with `php -m`.

```
bcmath, ctype, curl, dom, fileinfo, gd (or imagick), json,
mbstring, openssl, pdo, pdo_mysql, tokenizer, xml, zip
```

**How to enable extensions (if missing):**

- **Ubuntu/Debian:** `sudo apt install php8.2-{bcmath,curl,dom,gd,mbstring,mysql,xml,zip}`
- **macOS (Homebrew):** `brew install php` (most extensions included by default)
- **Windows:** Edit `php.ini`, uncomment the extension lines, restart your server

### Optional but Recommended

| Tool | Purpose |
|------|---------|
| Redis | Faster caching and queues (app falls back to `file`/`database` without it) |
| Git | Version control |

---

## 2. Extract the Project

Unzip the downloaded archive and place it anywhere on your machine.

```bash
unzip balanceboat-os.zip
cd balanceboat-os
```

Your folder structure should look like this:

```
balanceboat-os/
├── app/
├── config/
├── database/
├── devops/
├── docs/
├── resources/
├── routes/
├── tests/
├── composer.json
├── package.json
└── ...
```

---

## 3. Install PHP Dependencies

From inside the `balanceboat-os/` folder, run:

```bash
composer install
```

This downloads all Laravel packages into the `vendor/` folder. It will take 1–3 minutes on the first run.

> **Note:** If Composer asks about trust for plugins, type `y` and press Enter.

---

## 4. Configure the Environment

### 4a. Create the `.env` file

Copy the example environment file:

```bash
cp .env.example .env
```

> **Windows:** `copy .env.example .env`

If `.env.example` doesn't exist in the zip, create `.env` manually by copying the template below.

### 4b. Generate the Application Key

```bash
php artisan key:generate
```

This sets `APP_KEY` in your `.env`. **Never share or commit this key.**

### 4c. Edit the `.env` file

Open `.env` in any text editor and update these values for localhost:

```env
APP_NAME="BalanceBoat Center OS"
APP_ENV=local
APP_KEY=                        # Already set by key:generate above
APP_DEBUG=true
APP_URL=http://localhost:8000

# ── Database ─────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=balanceboat_dev     # Name of the DB you will create in Step 5
DB_USERNAME=root                # Your local MySQL username
DB_PASSWORD=                    # Your local MySQL password (blank if none)

# ── Cache, Queue, Session ────────────────────────────
# For local development, use 'file' or 'database' if you don't have Redis
CACHE_STORE=file
QUEUE_CONNECTION=sync           # 'sync' runs jobs immediately (no worker needed)
SESSION_DRIVER=file
SESSION_LIFETIME=120

# ── Mail ─────────────────────────────────────────────
# Mailtrap (free): sign up at mailtrap.io and paste your credentials here
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=hello@balanceboat.local
MAIL_FROM_NAME="${APP_NAME}"

# ── File Storage ──────────────────────────────────────
# For local dev, use the local disk (no AWS needed)
FILESYSTEM_DISK=local

# ── Third-party APIs (optional for localhost) ─────────
# Leave blank — the app has rule-based fallbacks for all of these
OPENAI_API_KEY=
STRIPE_KEY=
STRIPE_SECRET=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_ACCESS_TOKEN=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

> **Tip:** The application works fully without Stripe, OpenAI, or WhatsApp keys on localhost.
> The AI assistant will fall back to rule-based responses, and payment flows will use a test mode.

---

## 5. Create the Database

Open your MySQL client (Workbench, TablePlus, DBeaver, or the command line) and run:

```sql
CREATE DATABASE balanceboat_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Via command line:**

```bash
mysql -u root -p -e "CREATE DATABASE balanceboat_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

> Make sure the database name matches `DB_DATABASE` in your `.env`.

---

## 6. Run Migrations & Seed Data

This creates all the tables and populates the database with sample data.

```bash
php artisan migrate --seed
```

**What gets created:**

- All 30+ database tables
- 7 roles: `super-admin`, `balanceboat-admin`, `center-owner`, `center-manager`, `staff`, `teacher`, `accountant`
- Subscription plans (Free, Starter, Professional, Enterprise)
- Sample email and WhatsApp templates
- Sample retreat categories (Yoga, Meditation, Ayurveda, etc.)
- Default system settings

> **If you see errors about existing tables** (e.g. running on the SQL file from your original schema):
> ```bash
> php artisan migrate:fresh --seed
> ```
> ⚠️ `migrate:fresh` drops all tables first — use only on a fresh dev database.

---

## 7. Install Frontend Dependencies

Install Node packages and compile CSS/JS assets:

```bash
npm install
npm run dev
```

`npm run dev` starts the Vite development server. **Keep this terminal window open** while using the app — it enables hot reload for CSS/JS changes.

To build assets once without the watcher (alternative):

```bash
npm run build
```

---

## 8. Storage & File System Setup

Run the following to link the public storage folder so uploaded files are accessible via the browser:

```bash
php artisan storage:link
```

Then create the required cache/storage directories:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 9. Start the Application

Open a **new terminal** (keep Vite running in the previous one) and run:

```bash
php artisan serve
```

The app is now running at **http://localhost:8000**

Open it in your browser. You should see the BalanceBoat login screen.

---

## 12. Default Login Credentials

After seeding, use these credentials to log in:

| Role | Email | Password |
|------|-------|----------|
| Super Admin (BalanceBoat) | `superadmin@balanceboat.com` | `password` |
| Center Owner | `owner@demo-center.com` | `password` |
| Center Manager | `manager@demo-center.com` | `password` |
| Staff | `staff@demo-center.com` | `password` |
| Teacher | `teacher@demo-center.com` | `password` |

> **Change these passwords immediately** if you expose the app to any network beyond your own machine.

---

## 10. Start the Queue Worker

> **Skip this step** if you set `QUEUE_CONNECTION=sync` in your `.env` (recommended for local dev).
> With `sync`, all jobs run immediately inline without needing a worker.

If you want to use Redis queues locally (more realistic to production):

```env
# In .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
```

Then start the worker in a separate terminal:

```bash
php artisan queue:work --queue=default,notifications,heavy,automations
```

---

## 11. Optional Integrations

You can add these at any time by editing `.env` and restarting `php artisan serve`.

### Email — Mailtrap (free, recommended for local)

Mailtrap catches all outgoing emails so they don't reach real users.

1. Sign up at [https://mailtrap.io](https://mailtrap.io) (free plan)
2. Go to **Email Testing → Inboxes → SMTP Settings**
3. Copy the credentials into `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=<your_mailtrap_username>
MAIL_PASSWORD=<your_mailtrap_password>
```

### OpenAI (AI Assistant feature)

```env
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o
```

### Stripe (Payments)

Use Stripe test keys — no real charges will be made.

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

To test webhooks locally, install [Stripe CLI](https://stripe.com/docs/stripe-cli) and run:

```bash
stripe listen --forward-to localhost:8000/webhook/stripe
```

### Google OAuth (Social Login)

1. Go to [https://console.cloud.google.com](https://console.cloud.google.com)
2. Create a project → Enable Google+ API → Create OAuth credentials
3. Set Authorized redirect URI to: `http://localhost:8000/auth/google/callback`
4. Add to `.env`:

```env
GOOGLE_CLIENT_ID=....apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=...
```

### WhatsApp Business API

```env
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_VERIFY_TOKEN=any_random_string_you_choose
```

---

## Quick Command Reference

| Task | Command |
|------|---------|
| Start app | `php artisan serve` |
| Start Vite (CSS/JS) | `npm run dev` |
| Start queue worker | `php artisan queue:work` |
| Run migrations | `php artisan migrate` |
| Reset & reseed database | `php artisan migrate:fresh --seed` |
| Clear all caches | `php artisan optimize:clear` |
| View routes | `php artisan route:list` |
| Run tests | `php artisan test` |
| Create admin user | `php artisan tinker` then see below |

**Create a user via Tinker:**

```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'first_name' => 'Admin',
    'last_name'  => 'User',
    'email'      => 'me@example.com',
    'password'   => bcrypt('secret123'),
]);
$user->assignRole('super-admin');
```

---

## 13. Troubleshooting

### `php artisan` commands fail with "could not find driver"

Your PHP is missing the MySQL extension.

```bash
# Ubuntu / Debian
sudo apt install php8.2-mysql

# macOS (Homebrew)
brew install php   # reinstall with mysql driver
```

### `Class not found` or `Target class [...] does not exist`

Run:

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Assets not loading (CSS looks broken, no styles)

Make sure Vite is running:

```bash
npm run dev
```

Or if you want no watcher, build once:

```bash
npm run build
```

### `SQLSTATE[HY000] [2002] Connection refused`

MySQL is not running. Start it:

```bash
# Ubuntu / Debian
sudo service mysql start

# macOS (Homebrew)
brew services start mysql

# Windows
net start MySQL80
```

### `The stream or file .../laravel.log could not be opened`

Storage folder needs write permission:

```bash
chmod -R 775 storage bootstrap/cache
```

### `No application encryption key has been specified`

```bash
php artisan key:generate
```

### `migrate` fails with "Table already exists"

You likely have an existing database from the SQL dump. Use:

```bash
php artisan migrate:fresh --seed
```

Or manually drop and recreate the database before migrating.

### Uploaded images not showing

Make sure you've run:

```bash
php artisan storage:link
```

And that `FILESYSTEM_DISK=local` is set in `.env` for localhost.

---

## Full Local Setup Checklist

```
[ ] PHP 8.2+ installed with required extensions
[ ] Composer 2.x installed
[ ] Node.js 18+ and npm installed
[ ] MySQL/MariaDB running
[ ] Project extracted to a folder
[ ] composer install  ✓
[ ] .env file created from .env.example
[ ] APP_KEY generated (php artisan key:generate)
[ ] DB_DATABASE, DB_USERNAME, DB_PASSWORD set in .env
[ ] Database created in MySQL
[ ] php artisan migrate --seed  ✓
[ ] npm install && npm run dev  ✓ (keep terminal open)
[ ] php artisan storage:link  ✓
[ ] php artisan serve  ✓
[ ] App opens at http://localhost:8000  ✓
[ ] Login with superadmin@balanceboat.com / password  ✓
```
