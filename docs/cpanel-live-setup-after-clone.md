# JUVA Certify Manager: cPanel Live Setup After Clone

Use this file when the GitHub repository has been cloned directly on cPanel for `cert.juvaoil.com`.

## 1. Why the page is blank white

If `cert.juvaoil.com` is pointed at the repository root, cPanel will load the development `index.html`, which contains:

```html
<script type="module" src="/src/main.tsx"></script>
```

That file is for the local Vite dev server only. A normal cPanel Apache/PHP host cannot compile TypeScript/React in the browser, so the app can show a blank white page.

The tested production frontend is already built here:

```text
deployment/juva-certify-cpanel-fresh/public/
```

That folder contains the real production `index.html`, compiled `/assets/*.js`, compiled `/assets/*.css`, the PHP API, vendor runtime, logo and certificate assets.

## 2. Correct cPanel document root

In cPanel, set the subdomain document root for `cert.juvaoil.com` to this folder inside the cloned repository:

```text
/path/to/juva-certify-manager/deployment/juva-certify-cpanel-fresh/public
```

Example only:

```text
/home/YOUR_CPANEL_USER/repositories/JuvaCertificate/deployment/juva-certify-cpanel-fresh/public
```

Do not point the subdomain to the repository root.

If cPanel will not allow a document root inside the repository, copy the contents of this folder:

```text
deployment/juva-certify-cpanel-fresh/public/
```

into the actual subdomain document root, for example:

```text
/home/YOUR_CPANEL_USER/public_html/cert/
```

The document root must contain these items directly:

```text
.htaccess
index.html
logo.png
assets/
api/
vendor/
```

Quick check:

```text
https://cert.juvaoil.com/assets/index-BjgSTFev.js
```

If that URL is 404, the document root is wrong or the production public files were not copied correctly.

## 3. Public verification page

The public verification page is part of the React frontend and is available here:

```text
https://cert.juvaoil.com/verify
```

That page is public. People can type or paste:

```text
certificate number
inspection reference
QR verification token
```

The QR code on every issued certificate should point directly to:

```text
https://cert.juvaoil.com/verify/{verification_token}
```

Example shape:

```text
https://cert.juvaoil.com/verify/64-character-secure-token
```

The public page calls this backend endpoint:

```text
https://cert.juvaoil.com/api/certificates/verify.php?ref=REFERENCE_OR_TOKEN
```

Expected behavior:

- Valid certificate: shows valid certificate details.
- Expired certificate: shows expired status.
- Revoked certificate: shows revoked status.
- Unknown certificate: shows not found.

Important: The public verification page needs the database and `api/config.local.php` to be working. Without the database/config, the frontend may load but verification will fail.

## 4. Database setup is required

Yes, you must create and import the production database before login/API features will work.

In cPanel MySQL Databases:

1. Create a database, for example `CPANELUSER_juva_certify`.
2. Create a database user, for example `CPANELUSER_juva_app`.
3. Assign the user to the database with the required privileges.
4. Import this file using phpMyAdmin:

```text
deployment/juva-certify-cpanel-fresh/database/juva-certify-production-clean.sql
```

Alternative import path:

```text
deployment/juva-certify-cpanel-fresh/database/production-schema.sql
deployment/juva-certify-cpanel-fresh/database/production-seed.sql
```

The combined clean SQL is easier and recommended for first deployment.

Important: the clean database contains categories, roles and templates only. It has no users, no clients, no equipment, no certificates and no passwords.

## 5. Create production config

Create this file manually on cPanel:

```text
/path/to/document-root/api/config.local.php
```

Use this example as the template:

```text
deployment/juva-certify-cpanel-fresh/private/config.production.example.php
```

Set at minimum:

```php
<?php

return [
    'app_env' => 'production',
    'app_url' => 'https://cert.juvaoil.com',
    'api_url' => 'https://cert.juvaoil.com/api',
    'allowed_origin' => 'https://cert.juvaoil.com',

    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => 'CPANELUSER_juva_certify',
    'db_user' => 'CPANELUSER_juva_app',
    'db_password' => 'YOUR_DATABASE_PASSWORD',

    'session_name' => 'juva_certify_session',
    'session_hours' => 8,
    'remember_days' => 30,
    'security_salt' => 'PUT_A_LONG_RANDOM_64_PLUS_CHARACTER_STRING_HERE',
    'trusted_proxies' => [],
    'max_json_bytes' => 1048576,

    'mail_transport' => 'smtp',
    'mail_reply_to' => 'juvaoil@gmail.com',
    'smtp_host' => 'mail.cert.juvaoil.com',
    'smtp_port' => 465,
    'smtp_encryption' => 'ssl',
    'smtp_username' => 'certificates@cert.juvaoil.com',
    'smtp_password' => 'YOUR_SMTP_PASSWORD',
    'smtp_timeout' => 15,
    'certificate_notifications_enabled' => true,
    'mail_from' => 'no-reply@cert.juvaoil.com',
    'mail_from_name' => 'JUVA Certify Manager',

    'private_storage_path' => '/home/CPANELUSER/juva-certify-storage',
    'reminder_days' => 30,
    'admin_notice_email' => 'juvaoil@gmail.com',

    // Temporary only. Remove after creating the first Super Admin.
    'setup_key' => 'TEMPORARY_RANDOM_SETUP_KEY'
];
```

Do not commit real passwords to GitHub.

## 6. Create private storage

Create a private writable folder outside the public document root:

```text
/home/CPANELUSER/juva-certify-storage
```

The app will use it for certificate PDFs, QR files, previews, evidence and signatures.

Make sure PHP can write to it. On most cPanel servers, `0755` or `0750` works when owned by the same cPanel user.

## 7. Create the first Super Admin

The clean production database has no login user. Create the first admin after database and config are ready.

Preferred: use cPanel Terminal with the maintenance script outside the public web root:

```text
deployment/juva-certify-cpanel-fresh/maintenance/bootstrap-initial-admin.php.dist
```

Set temporary environment variables for:

```text
INITIAL_ADMIN_NAME
INITIAL_ADMIN_EMAIL
INITIAL_ADMIN_USERNAME
INITIAL_ADMIN_PASSWORD
JUVA_CONFIG_PATH
```

Then run it once with PHP from cPanel Terminal. Remove the temporary values afterwards.

Alternative: use the web bootstrap endpoint only while `setup_key` exists:

```text
https://cert.juvaoil.com/api/setup/bootstrap-admin.php
```

Post these fields:

```json
{
  "setup_key": "TEMPORARY_RANDOM_SETUP_KEY",
  "name": "JUVA Super Admin",
  "email": "juvaoil@gmail.com",
  "username": "superadmin",
  "password": "A-STRONG-NEW-PASSWORD"
}
```

Immediately after the admin is created and login works, remove `setup_key` from `api/config.local.php`.

## 8. Required PHP settings/extensions

Use PHP 8.2+ if available.

Enable/check:

```text
pdo_mysql
fileinfo
gd
mbstring
openssl
json
session
```

Recommended production PHP settings:

```text
display_errors = Off
log_errors = On
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M or higher
max_execution_time = 120
```

## 9. Quick health checks

After setup, check these URLs:

```text
https://cert.juvaoil.com/
https://cert.juvaoil.com/api/health.php
https://cert.juvaoil.com/verify
https://cert.juvaoil.com/verify/test
```

Expected results:

- `/` should show the login page, not a blank white page.
- `/api/health.php` should return a JSON/API response, not a 500 error.
- `/verify` should show the public verification search page.
- `/verify/test` should show the public verification page with a not-found result.

## 10. If the page is still blank

Open browser developer tools and check Console/Network.

Common causes:

1. Document root still points to repo root instead of `deployment/juva-certify-cpanel-fresh/public`.
2. `/assets/index-BjgSTFev.js` returns 404.
3. `.htaccess` rewrite rules are disabled or ignored.
4. HTTPS redirect or CSP is being overridden by server-level settings.
5. File permissions prevent Apache from reading the deployed files.

## 11. Final production checklist

- [ ] Subdomain document root points to `deployment/juva-certify-cpanel-fresh/public` or copied equivalent.
- [ ] MySQL database created.
- [ ] `juva-certify-production-clean.sql` imported.
- [ ] `api/config.local.php` created with production DB credentials.
- [ ] Private storage folder created outside public web root.
- [ ] First Super Admin created.
- [ ] `setup_key` removed after bootstrap.
- [ ] HTTPS enabled.
- [ ] `/api/health.php` works.
- [ ] Login page loads.
- [ ] `/verify` public page loads.
- [ ] Public certificate ID search works after issuing a certificate.
- [ ] QR opens `/verify/{token}` and shows the exact certificate.
- [ ] SMTP tested from the admin settings screen.
- [ ] A certificate preview/generation test is performed before real use.
