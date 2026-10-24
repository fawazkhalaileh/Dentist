# Deploying to a new server

Everything needed to run the clinic platform (public website, clinic system and patient portal) on a fresh server, and to move the live data across from the current one. The current production server is set up exactly this way.

## What the server needs

| Component | Version | Notes |
|---|---|---|
| OS | Ubuntu 24.04 LTS | Any Linux works. Commands below are for Ubuntu. |
| Web server | Apache 2.4 with `mod_php`, `mod_rewrite` and `mod_headers` | Nginx with PHP-FPM also works. Point it at `public/`. |
| PHP | 8.4 (8.3 minimum) | Extensions: `pdo_mysql`, `mbstring`, `intl`, `gd` (with WebP), `exif`, `fileinfo`, `zip`, `curl`, `xml`, `bcmath`, `sodium`, `opcache` |
| Database | MySQL 8.0 or 8.4 | One database plus one app user. `utf8mb4`. |
| Composer | 2.x | Installs PHP packages. |
| Node.js and npm | Node 20+ (22 in production) | Only to build CSS and JS during deploys. Fonts come from npm packages, so builds need no internet beyond npm. |
| Git | any | Pulls code from GitHub. |
| Size | 2 CPU / 4 GB RAM / 30 GB disk is plenty | Disk grows with uploaded documents, photos and backups. |

**Not needed:** no Redis, no queue worker, no scheduler or cron, no Node process at runtime. Sessions, cache and queue all use the MySQL database. Nothing is queued today, so no worker is required.

**Outside services:**
- **Email (SMTP)**, for example Brevo, Zoho or Google Workspace. Not set up yet. Until it is, password-reset emails are only written to the log (`MAIL_MAILER=log`).
- **A domain name with HTTPS**, strongly recommended before real use. Logins and the patient portal should never go over plain HTTP.
- **GitHub access** to `fawazkhalaileh/Dentist`, for example a read-only deploy key on the server.

## 1. Install the system packages

```bash
sudo apt update
sudo apt install -y apache2 mysql-server git unzip acl \
  php8.4 libapache2-mod-php8.4 php8.4-mysql php8.4-mbstring php8.4-intl php8.4-gd \
  php8.4-xml php8.4-zip php8.4-curl php8.4-bcmath php8.4-opcache
# Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer && rm composer-setup.php
# Node.js 22
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
sudo a2enmod rewrite headers
```

If Ubuntu doesn't offer `php8.4`, add the `ondrej/php` PPA first (`sudo add-apt-repository ppa:ondrej/php`).

## 2. Database

```sql
-- as MySQL root (sudo mysql)
CREATE DATABASE dentist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dentist'@'localhost' IDENTIFIED BY 'choose-a-long-password';
GRANT ALL PRIVILEGES ON dentist.* TO 'dentist'@'localhost';
```

For password-free backups as the deploy user, create `~/.my.cnf` (mode `600`):

```ini
[client]
user=dentist
password=choose-a-long-password

[mysqldump]
user=dentist
password=choose-a-long-password
no-tablespaces
```

## 3. Get the code and set permissions

The deploy user (currently `fawaz`) owns the code; Apache (`www-data`) must be able to write `storage/` and `bootstrap/cache/`.

```bash
sudo mkdir -p /var/www/dentist && sudo chown $USER:www-data /var/www/dentist
git clone git@github.com:fawazkhalaileh/Dentist.git /var/www/dentist
cd /var/www/dentist
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R g+rwX storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} +
sudo setfacl -R -m u:www-data:rwX -m u:$USER:rwX storage bootstrap/cache
sudo setfacl -R -d -m u:www-data:rwX -m u:$USER:rwX storage bootstrap/cache
```

## 4. Configure `.env`

```bash
cp .env.example .env
```

Production values (everything else can stay as in `.env.example`):

| Setting | Production value | Why |
|---|---|---|
| `APP_NAME` | clinic name | Fallback name, before it is set in the clinic details. |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | Debug mode would show error details to visitors. |
| `APP_KEY` | **copy from the old server** (new install: `php artisan key:generate`) | Clinical notes, contact messages and sessions are encrypted with it. **A different key makes existing encrypted data unreadable.** |
| `APP_URL` | `https://your-domain` (currently `http://20.216.17.199`) | Links, sitemap, robots.txt, llms.txt and structured data use it. |
| `DB_*` | `mysql`, `127.0.0.1`, `3306`, `dentist`, `dentist`, password | |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | `database` | No Redis needed. |
| `SESSION_ENCRYPT` | `true` | |
| `SESSION_SECURE_COOKIE` | `true` once HTTPS works (`false` on plain HTTP, or logins fail) | |
| `CLINIC_TIMEZONE` | `Asia/Amman` | Appointment times. |
| `CLINIC_TWO_FACTOR` | `false` (current choice: password-only sign-in) | Set to `true` to require authenticator codes again. Existing setups are kept. |
| `CLINIC_REQUIRE_STAFF_MFA` | `true` | Only applies while `CLINIC_TWO_FACTOR=true`. |
| `MAIL_*` | your SMTP provider | Leave `MAIL_MAILER=log` until you have one. |
| `FILESYSTEM_DISK` | `local` | Clinical documents stay private (`storage/app/private`). Website images use the `public` disk. |

Keep `.env` at mode `640`, owned by the deploy user with group `www-data`.

## 5. PHP and Apache settings

`/etc/php/8.4/apache2/conf.d/99-dentist.ini`. Without it, uploads over 2 MB fail:

```ini
upload_max_filesize = 12M
post_max_size = 16M
memory_limit = 256M
max_execution_time = 60
```

`/etc/apache2/sites-available/dentist.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain
    DocumentRoot /var/www/dentist/public
    <Directory /var/www/dentist/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
sudo a2ensite dentist && sudo a2dissite 000-default
sudo apache2ctl configtest && sudo systemctl reload apache2
```

`public/.htaccess` (in the repo) handles routing and browser caching of images and build files; it needs `mod_rewrite` and `mod_headers`.

## 6. First install

```bash
cd /var/www/dentist
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force          # also creates roles and permissions; never run demo seeders here
php artisan storage:link             # serves uploaded website images
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

On a brand-new installation, with no data being moved across:

```bash
php artisan clinic:create-admin you@example.com --name="Your Name"   # asks for a password
php artisan db:seed --class=ProcedureSeeder --force                  # optional: 41 starter procedures
```

## 7. Moving the live data from the old server

Three things must come across together. Missing any one of them loses data.

1. **The database:** on the old server, `mysqldump dentist | gzip > dentist.sql.gz`. On the new one, `gunzip -c dentist.sql.gz | mysql dentist`.
2. **Uploaded files:** copy `storage/app/` completely. It holds patient documents (`storage/app/private`) and website images (`storage/app/public`). For example: `rsync -a old:/var/www/dentist/storage/app/ /var/www/dentist/storage/app/`. Then re-run the permission commands from step 3.
3. **`APP_KEY`:** copy the exact value from the old `.env`.

Then run `php artisan migrate --force` (applies anything newer), `php artisan storage:link`, rebuild the caches, sign in and open a patient with documents and notes to confirm they load.

## 8. Deploying updates

The deploy script on the server (`~/deploy.sh`):

```bash
#!/bin/bash
set -e
cd /var/www/dentist
php artisan down || true
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
echo "Deployed $(git log -1 --oneline)"
```

Always back up first, then deploy, then check `/en`, `/ar` and `/login` return 200:

```bash
mkdir -p ~/backups && mysqldump dentist | gzip > ~/backups/dentist-$(date +%F-%H%M).sql.gz
~/deploy.sh
```

## 9. Backups

- The database (above) before every deploy, and ideally daily.
- `storage/app/`: patient documents and website images.
- `.env`, or at least `APP_KEY`, kept somewhere safe off the server.
- Store copies **off the server** (for example Azure Blob Storage). Today, backups live only on the production server in `~/backups`.

## 10. HTTPS (when you have a domain)

1. Point the domain's DNS A record to the server's IP.
2. `sudo apt install -y certbot python3-certbot-apache && sudo certbot --apache -d your-domain` (free certificate that renews automatically).
3. In `.env`: `APP_URL=https://your-domain` and `SESSION_SECURE_COOKIE=true`; then rebuild the caches (`php artisan config:cache`).
4. Consider switching two-factor sign-in back on (`CLINIC_TWO_FACTOR=true`).

## Checklist for a new server

- [ ] Packages and PHP extensions installed; `mod_rewrite` and `mod_headers` enabled
- [ ] MySQL database and user created; `~/.my.cnf` for backups
- [ ] Code cloned; `storage/` and `bootstrap/cache/` writable by `www-data`
- [ ] `.env` filled in, with `APP_KEY` copied from the old server
- [ ] `99-dentist.ini` upload limits; Apache site enabled
- [ ] Database and `storage/app/` copied across; migrations run; `storage:link`
- [ ] `/en`, `/ar` and `/login` return 200; a patient's documents and notes open
- [ ] Backups scheduled and copied off the server
- [ ] Domain plus HTTPS; `SESSION_SECURE_COOKIE=true`
- [ ] SMTP configured, so password resets arrive
