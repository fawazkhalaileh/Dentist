# Dental Clinic Platform

Public website, patient portal and clinic practice-management dashboard in one Laravel 13 application.
See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the design, data model, roles and phase plan.

**Status: Phase 1 (foundation).** Pages marked "Not built yet" are routed placeholders with their access rules in place.

## Requirements

- PHP 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `intl`, `fileinfo`, `gd`, `zip`, `curl`, `sodium`, `exif`, `bcmath`
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
   npm install
   npm run build
   ```

3. Run it with `composer run dev`, then open http://127.0.0.1:8000.

## Demo accounts (local only)

`php artisan migrate:fresh --seed` creates synthetic users in the `local` environment.
The emails and shared password are listed at the top of `database/seeders/DemoSeeder.php`.

Staff must enable two-factor authentication (Settings → Security) before the clinic dashboard opens.
To skip this locally, set `CLINIC_REQUIRE_STAFF_MFA=false` in `.env`. Never do this in production.

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
| Role → permission mapping | `app/Enums/Role.php` (synced by `RolesAndPermissionsSeeder`) |

## Production notes (Phase 6 checklist)

- HTTPS only: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
- Back up the database **and** `APP_KEY`. Clinical text fields are encrypted with it.
- Clinical uploads stay on a private disk (`CLINIC_CLINICAL_DISK`) and are served only through authorised routes.
- Never seed real patient data. `DemoSeeder` only runs in the `local` environment.
