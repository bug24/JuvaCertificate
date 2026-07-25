# JUVA Clean Database Import Guide

## Option A: Fresh Install (recommended)

1. Create an empty cPanel MySQL/MariaDB database and a least-privilege database user.
2. Import `production-schema.sql`.
3. Import `production-seed.sql`.
4. Configure untracked `api/config.local.php` with production database, HTTPS URL, mail and private-storage values.
5. From cPanel Terminal, set `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, `INITIAL_ADMIN_USERNAME`, and a strong `INITIAL_ADMIN_PASSWORD` temporarily.
6. Run `php deployment/cpanel/bootstrap-initial-admin.php` once.
7. Confirm login, clear all `INITIAL_ADMIN_*` variables, and remove the bootstrap file from the server.
8. Remove `setup_key` from `config.local.php` so the web bootstrap endpoint is disabled.

## Option B: Clean Combined Import

1. Create an empty cPanel MySQL/MariaDB database.
2. Import `juva-certify-production-clean.sql` through phpMyAdmin or the MySQL CLI.
3. Continue with steps 4-8 from Option A.

The combined SQL contains schema plus configuration only. It contains no users, clients, equipment, inspections, certificates, tokens, logs, notifications, passwords, or local paths.

## Numbering

The clean database contains no `certificate_sequences` rows. Create approved production clients and category pairings normally. If JUVA must continue an historical sequence, insert an approved opening `last_number` only after business sign-off; never infer it from deleted test records.

## Storage

Do not upload local `storage/` or `storage-private/` contents. Create empty private runtime directories outside the public document root and grant write access only to the PHP/cPanel account. Upload only approved shared renderer assets packaged with the application.

## Compatibility

Exports were produced from MySQL 8 with UTF-8 MB4 and without `DEFINER` clauses or database-name `USE` statements. Test on the target MariaDB version before production traffic.
