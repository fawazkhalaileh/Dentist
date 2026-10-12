# Dental Clinic Platform

Public website, patient portal and clinic practice-management dashboard in one Laravel 13 application.
See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the design, data model, roles and phase plan.

**Status: Phase 2 (public website and CMS) done.** The public site works in English and Arabic, and content staff edit it under `/clinic/content`.
Patient, clinical and scheduling pages marked "Not built yet" are routed placeholders with their access rules in place (Phases 3 to 5).

## Requirements

- PHP 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `intl`, `fileinfo`, `gd` (with WebP support), `zip`, `curl`, `sodium`, `exif`, `bcmath`
- Composer 2
- MySQL 8.4 (8.0+ works)
- Node.js 20+

## Setup

1. Create the databases and an app user (as MySQL root):

   ```sql
   CREATE DATABASE dentist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE dentist_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'dentist'@'localhost' IDENTIFIED BY 'choose-a-password';
   GRANT ALL PRIVILEGES ON dentist.* TO 'dentist'@'localhost';
   GRANT ALL PRIVILEGES ON dentist_testing.* TO 'dentist'@'localhost';
   ```

2. Install and configure:

   ```bash
   composer install
   cp .env.example .env        # then set DB_PASSWORD
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link    # serves uploaded website images
   npm install
   npm run build
   ```

3. Run it with `composer run dev`, then open http://127.0.0.1:8000.

## Demo accounts (local only)

`php artisan migrate:fresh --seed` creates synthetic users in the `local` environment.
The emails and shared password are listed at the top of `database/seeders/DemoSeeder.php`.

Staff must enable two-factor authentication (Settings → Security) before the clinic dashboard opens.
To skip this locally, set `CLINIC_REQUIRE_STAFF_MFA=false` in `.env`. Never do this in production.

The demo seeder also adds placeholder website content in both languages (services, a blog post, pages, FAQs).
It is marked as demo text: replace it through the CMS before launch.

## Editing the website

Sign in as an admin or receptionist and open **Website** in the clinic sidebar (`/clinic/content`):
services, blog, pages (about, privacy, terms), gallery, testimonials, FAQs, media library, contact details and website messages.
Each item has a tab per language. Content stays hidden until it is published, and it only shows in the languages it has been written in.
Gallery items and testimonials need the patient's recorded consent before they can be published.
See the CMS section of [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md#website-content-cms) for the rules.

## Checks

```bash
composer test
```

This runs Pint (code style), PHPStan/Larastan (level 7) and the PHPUnit suite against the `dentist_testing` MySQL database.

## Configuration

| Setting | Where |
|---|---|
| Clinic name, locales, patient number format, staff MFA, clinical file disk | `config/clinic.php` / `.env` (`CLINIC_*`) |
| Colours, status colours, brand tokens | `resources/css/theme.css` |
| Role → permission mapping | `app/Enums/Role.php` (synced automatically by `php artisan migrate`) |
| Clinic phone, email, address, opening hours, map link | CMS → Contact details (stored in the `settings` table) |
| Interface translations | `lang/ar.json`, `lang/ar/validation.php` (draft: needs native-speaker review) |

## Deploying

Every deploy runs `php artisan migrate --force`, which also creates or updates the roles and permissions, so production never needs a seeder.
A deploy pulls `main`, installs Composer and npm dependencies, builds assets, migrates and rebuilds the config, route and view caches.
Back up the database first (`mysqldump dentist | gzip > ~/backups/dentist-$(date +%F-%H%M).sql.gz`; credentials come from `~/.my.cnf`).

One-time server setup:
- `php artisan storage:link` (serves uploaded website images).
- `storage/` and `bootstrap/cache/` owned by the deploy user with group `www-data`, group-writable, with default ACLs for both users. Log files are created `0664` (`config/logging.php`), so deploys and Apache can both write them.
- PHP for Apache: `upload_max_filesize` of at least 10M and `post_max_size` of at least 12M (website images may be up to 8 MB), and `memory_limit` of 256M for resizing them.
- Mail: set `MAIL_*` in `.env` to a real SMTP service. With the default `log` mailer, password-reset and verification emails are only written to the log.

Production never runs seeders, so it starts with no staff accounts. Create the first administrator on the server:

```bash
php artisan clinic:create-admin you@example.com --name="Your Name"   # asks for the password
```

The administrator sets up two-factor authentication at first sign-in. Staff management (adding dentists and receptionists) arrives in Phase 3.

## Production notes (Phase 6 checklist)

- `APP_ENV=production` and `APP_DEBUG=false` (debug mode shows stack traces to visitors).
- HTTPS only, then `SESSION_SECURE_COOKIE=true`.
- Back up the database **and** `APP_KEY`. Clinical text fields are encrypted with it.
- Clinical uploads stay on a private disk (`CLINIC_CLINICAL_DISK`) and are served only through authorised routes.
- Never seed real patient data. `DemoSeeder` only runs in the `local` environment.
- Website messages (contact form) are encrypted with `APP_KEY` and may contain health information.
- Security headers (CSP, HSTS, X-Frame-Options, Referrer-Policy) are not set yet.
