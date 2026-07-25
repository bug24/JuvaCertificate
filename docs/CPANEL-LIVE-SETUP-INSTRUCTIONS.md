# JUVA Certify Manager cPanel Live Setup Instructions

Use this file when deploying `cert.juvaoil.com` from the GitHub repository.

## Why the page is blank after cloning

The repository root is the development source tree. Its root `index.html` points to `/src/main.tsx`, which is for Vite development only and will show a blank/failed page on normal cPanel hosting.

The tested production build is here:

```text
deployment/juva-certify-cpanel-fresh/public
```

Your subdomain must serve that folder, or you must copy the contents of that folder into the `cert.juvaoil.com` document root.

## Recommended cPanel folder layout

Replace `CPANEL_USER` with your real cPanel username.

```text
/home/CPANEL_USER/JuvaCertificate/                         cloned GitHub repo
/home/CPANEL_USER/juva-certify-storage/                    private runtime files
/home/CPANEL_USER/public_html/cert/                        cert.juvaoil.com document root
```

The public document root should contain these files/folders from the production package:

```text
api/
assets/
vendor/
.htaccess
index.html
logo.png
```

## Step 1: Fix the document root

### Option A: Point the subdomain to the production public folder

In cPanel, set the document root for `cert.juvaoil.com` to:

```text
/home/CPANEL_USER/JuvaCertificate/deployment/juva-certify-cpanel-fresh/public
```

This is the cleanest option if cPanel allows it.

### Option B: Copy production public files into the current subdomain folder

If the document root is fixed as `/home/CPANEL_USER/public_html/cert`, copy the contents of:

```text
/home/CPANEL_USER/JuvaCertificate/deployment/juva-certify-cpanel-fresh/public/*
```

into:

```text
/home/CPANEL_USER/public_html/cert/
```

Do not copy the repository root `index.html` into the live document root.

## Step 2: Create the database

In cPanel MySQL Databases:

1. Create a database, for example:

```text
CPANEL_USER_juva_certify
```

2. Create a database user, for example:

```text
CPANEL_USER_juva_app
```

3. Give that user privileges on the database.

4. Import one of these SQL files using phpMyAdmin:

Recommended combined import:

```text
deployment/juva-certify-cpanel-fresh/database/juva-certify-production-clean.sql
```

Alternative two-step import:

```text
deployment/juva-certify-cpanel-fresh/database/production-schema.sql
deployment/juva-certify-cpanel-fresh/database/production-seed.sql
```

The clean SQL has categories/forms/configuration only. It has no users, clients, equipment, certificates, passwords, tokens, or local test data.

## Step 3: Create private storage

Create this folder outside `public_html`:

```text
/home/CPANEL_USER/juva-certify-storage
```

Create these subfolders if cPanel does not create them automatically:

```text
/home/CPANEL_USER/juva-certify-storage/certificates
/home/CPANEL_USER/juva-certify-storage/evidence
/home/CPANEL_USER/juva-certify-storage/previews
/home/CPANEL_USER/juva-certify-storage/signatures
/home/CPANEL_USER/juva-certify-storage/branding
/home/CPANEL_USER/juva-certify-storage/mail-log
```

Make sure PHP can write to that folder. On most cPanel accounts, `755` for folders is enough if the files are owned by the cPanel user.

## Step 4: Create `api/config.local.php`

On the live server, create this file inside the live public API folder:

```text
/home/CPANEL_USER/public_html/cert/api/config.local.php
```

or, if your subdomain points directly to the repo production public folder:

```text
/home/CPANEL_USER/JuvaCertificate/deployment/juva-certify-cpanel-fresh/public/api/config.local.php
```

Start from this example:

```text
deployment/juva-certify-cpanel-fresh/private/config.production.example.php
```

Use real production values. Example structure:

```php
<?php

return [
    'app_env' => 'production',
    'app_url' => 'https://cert.juvaoil.com',
    'api_url' => 'https://cert.juvaoil.com/api',
    'allowed_origin' => 'https://cert.juvaoil.com',
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => 'CPANEL_USER_juva_certify',
    'db_user' => 'CPANEL_USER_juva_app',
    'db_password' => 'YOUR_DATABASE_PASSWORD',
    'session_name' => 'juva_certify_session',
    'session_hours' => 8,
    'remember_days' => 30,
    'security_salt' => 'GENERATE_A_LONG_RANDOM_64_PLUS_CHARACTER_SECRET',
    'trusted_proxies' => [],
    'max_json_bytes' => 1048576,
    'mail_transport' => 'smtp',
    'mail_reply_to' => 'juvaoil@gmail.com',
    'smtp_host' => 'mail.cert.juvaoil.com',
    'smtp_port' => 465,
    'smtp_encryption' => 'ssl',
    'smtp_username' => 'certificates@cert.juvaoil.com',
    'smtp_password' => 'YOUR_EMAIL_PASSWORD',
    'smtp_timeout' => 15,
    'certificate_notifications_enabled' => true,
    'mail_from' => 'no-reply@cert.juvaoil.com',
    'mail_from_name' => 'JUVA Certify Manager',
    'private_storage_path' => '/home/CPANEL_USER/juva-certify-storage',
    'reminder_days' => 30,
    'admin_notice_email' => 'juvaoil@gmail.com',
    'setup_key' => 'TEMPORARY_RANDOM_SETUP_KEY_REMOVE_AFTER_ADMIN_CREATED',
];
```

Important: `api/config.local.php` must never be committed to GitHub.

## Step 5: Confirm PHP requirements

Use PHP 8.2 or newer if available.

Enable/confirm these PHP extensions:

```text
pdo_mysql
fileinfo
gd
mbstring
openssl
json
```

Set production PHP options:

```text
display_errors = Off
log_errors = On
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 120
```

## Step 6: Create the first Super Admin

The clean production database has no users. You must create the first Super Admin.

Preferred method: cPanel Terminal.

Go to the app root:

```bash
cd /home/CPANEL_USER/JuvaCertificate/deployment/juva-certify-cpanel-fresh
```

Run the bootstrap with temporary environment values:

```bash
export JUVA_CONFIG_PATH=/home/CPANEL_USER/public_html/cert/api/config.local.php
export INITIAL_ADMIN_NAME='JUVA Super Admin'
export INITIAL_ADMIN_EMAIL='your-email@example.com'
export INITIAL_ADMIN_USERNAME='superadmin'
export INITIAL_ADMIN_PASSWORD='USE_A_STRONG_PASSWORD_HERE'
php maintenance/bootstrap-initial-admin.php.dist
```

If your live API config is inside the repo production public folder instead, set `JUVA_CONFIG_PATH` to that `config.local.php` path.

After successful login:

1. Remove `setup_key` from `api/config.local.php`.
2. Clear the temporary environment variables.
3. Do not place the maintenance folder inside public web access.

## Step 7: Test these URLs

Open:

```text
https://cert.juvaoil.com/
https://cert.juvaoil.com/api/health.php
```

Expected:

- `/` should show the JUVA login screen.
- `/api/health.php` should return JSON, not a white page.

Then login with the Super Admin you created.

## Step 8: Configure SMTP inside the app

After login, go to:

```text
Users & Roles / SMTP and certificate alerts
```

Send a test email. If it fails, confirm:

- the cPanel mailbox exists
- SMTP host is correct
- SMTP port/encryption matches cPanel email settings
- the email password in `config.local.php` is correct
- outbound SMTP is allowed by the host

## Step 9: Common blank-page causes

1. Wrong document root: serving the repo root instead of `deployment/juva-certify-cpanel-fresh/public`.
2. Missing `assets/` folder in the live document root.
3. Browser cache still loading old files.
4. `.htaccess` not copied.
5. PHP fatal error from missing `api/config.local.php`.
6. Database not imported yet.
7. PHP version/extensions missing.

## Step 10: Files that must not be public

Do not upload or expose:

```text
.git/
node_modules/
src/
tests/
backups/
storage-private/
tmp/
.env
.env.local
api/config.local.php in GitHub
old SQL dumps
source certificate samples
```

Only the contents of `deployment/juva-certify-cpanel-fresh/public` should be web-facing.

## Fast fix for your current situation

If you already cloned the repo and `cert.juvaoil.com` is blank, do this first:

1. In cPanel Subdomains, change the document root to:

```text
/home/CPANEL_USER/JuvaCertificate/deployment/juva-certify-cpanel-fresh/public
```

2. If cPanel will not allow that, copy everything inside `deployment/juva-certify-cpanel-fresh/public` into the current `cert.juvaoil.com` document root.

3. Create/import the database.

4. Create `api/config.local.php`.

5. Bootstrap the Super Admin.

6. Visit `https://cert.juvaoil.com/api/health.php` and then `https://cert.juvaoil.com/`.
