# Phase 9 cPanel Deployment Guide

Target domain: `cert.juvaoil.com`

This project is cPanel-friendly without a Node.js runtime in production. The frontend is built with Vite and uploaded as static files; the backend is plain PHP using MySQL/MariaDB. If the product is later ported to Laravel, map Laravel's `public/` folder to the subdomain document root, but the current app does not require Laravel.

## Recommended cPanel Stack

- PHP 8.2 or newer.
- MySQL 5.7+/MariaDB 10.4+.
- Apache with `mod_rewrite` enabled.
- HTTPS/AutoSSL enabled for `cert.juvaoil.com`.
- cPanel email mailbox or valid sender for password reset, invitation and OTP mail.

## Folder Layout

Set the subdomain document root to something like:

```text
/home/CPANEL_USER/cert.juvaoil.com/
```

Upload the deployment files this way:

```text
/home/CPANEL_USER/cert.juvaoil.com/index.html
/home/CPANEL_USER/cert.juvaoil.com/assets/...
/home/CPANEL_USER/cert.juvaoil.com/logo.png
/home/CPANEL_USER/cert.juvaoil.com/.htaccess
/home/CPANEL_USER/cert.juvaoil.com/api/...
```

Build locally first:

```powershell
cd juva-certify-manager
.\node_modules\.bin\vite.cmd build
```

Then upload:

- Everything inside `dist/` to the subdomain document root.
- The full `api/` folder beside `index.html`.
- `database/schema.sql` and migrations are not required in the public root after import; keep copies locally and in private backups.

## Production Config

Copy:

```text
api/config.example.php -> api/config.local.php
```

Edit `api/config.local.php` on cPanel:

```php
'app_url' => 'https://cert.juvaoil.com',
'allowed_origin' => 'https://cert.juvaoil.com',
'db_host' => 'localhost',
'db_name' => 'CPANELUSER_juva_certify',
'db_user' => 'CPANELUSER_juva_certify',
'db_password' => 'strong database password',
'security_salt' => 'long random secret',
'cron_key' => 'different long random secret',
'mail_transport' => 'mail',
'mail_from' => 'no-reply@cert.juvaoil.com',
'admin_notice_email' => 'juvaoil@gmail.com',
```

Keep `setup_key` only for first admin creation, then remove it.

## Database Setup

1. Create a MySQL database and database user in cPanel.
2. Grant the user all privileges on the database.
3. Import `database/schema.sql` with phpMyAdmin or MySQL CLI.
4. If updating an existing install, run migrations in order from Phase 4 to Phase 8.
5. Confirm `/api/health.php` returns a healthy database response.

## First Super Admin

POST once to:

```text
https://cert.juvaoil.com/api/setup/bootstrap-admin.php
```

Example payload:

```json
{
  "setup_key": "temporary setup key",
  "name": "JUVA Super Admin",
  "email": "juvaoil@gmail.com",
  "username": "superadmin",
  "password": "use a strong password"
}
```

After login works, remove `setup_key` from `api/config.local.php`.

## Storage

The app stores uploads and generated certificate archives under:

```text
api/storage/evidence/
api/storage/certificates/
api/storage/mail-log/
```

No Linux symlink is required for the current app. `api/storage/.htaccess` blocks scripts and sensitive files while allowing certificate PDFs, barcode SVGs and uploaded evidence to be served where the app exposes them.

Recommended permissions:

```text
Folders: 755 or 775
Files: 644
api/config.local.php: 600 or 640 where cPanel allows it
api/storage: writable by the PHP user
```

## Cron Job

Add this in cPanel Cron Jobs to run once daily:

```bash
/usr/local/bin/php -q /home/CPANEL_USER/cert.juvaoil.com/api/cron/daily.php --key=YOUR_CRON_KEY >/dev/null 2>&1
```

If your cPanel PHP path differs, use the PHP 8.2 binary shown by cPanel MultiPHP or ask hosting support for the CLI PHP path.

The cron job:

- Marks expired certificates as expired.
- Marks due/expired issued inspections as expired.
- Finds certificates expiring within `reminder_days`.
- Emails a summary to `admin_notice_email` when there is activity.
- Writes an audit event as `cron.daily`.

## Old Data Import

For the Phase 8 legacy import:

1. Import the old `localhost.sql` tables into the same database.
2. Run `database/migrations/phase8_legacy_migration.sql`.
3. Open Reports -> Legacy migration.
4. Search an old certificate number on the public verification screen.
5. Invite approved legacy staff accounts. Do not import old MD5 passwords.

## Final Deployment Checklist

- HTTPS/AutoSSL enabled for `cert.juvaoil.com`.
- PHP version set to 8.2+.
- `api/config.local.php` created with production DB, URL, mail, salt and cron key.
- `setup_key` removed after first Super Admin is created.
- Admin account login tested.
- Mail tested with invitation, OTP and password reset.
- File permissions checked for `api/storage` and `api/config.local.php`.
- QR verification tested at `/verify/{token}`.
- Old certificate number verification tested, including numbers with slashes.
- PDF generation tested from an approved inspection.
- Certificate PDF archive download/preview tested.
- Old data import verified through reports.
- Daily cron job created and first run checked.
- Database, uploaded files and certificate archive backups scheduled.