# JUVA Certify Manager cPanel Package

This directory contains deployment guidance, not secrets or runtime data.

Recommended layout:

```text
/home/CPANEL_USER/juva-certify-app/       application/API source
/home/CPANEL_USER/juva-certify-storage/   evidence, signatures, PDFs, QR, previews
/home/CPANEL_USER/public_html/cert/        built frontend and approved API entry files
```

Set the subdomain document root to the prepared public root. Create `api/config.local.php` manually from `config.production.example.php`, then delete `setup_key` after bootstrap. Do not upload tests, `tmp`, `storage-private`, local mail logs, SQL dumps, screenshots, source certificate samples, `.git`, Node modules or local configuration.

Run migrations only after backup and duplicate/integrity preflight. Use PHP 8.2+, enable `fileinfo`, GD and PDO MySQL, configure `upload_max_filesize`/`post_max_size`, and keep `display_errors=Off`.


SMTP certificate alerts: create the cPanel mailbox, copy the SMTP host/port/encryption/username/password into the private config, enable certificate_notifications_enabled, then use Users & Roles > SMTP and certificate alerts to send a test message. Keep vendor/ in the deployed public root; Apache blocks direct access while PHP uses its autoloader internally.

## Fresh production database

Use `database/production-schema.sql` followed by `database/production-seed.sql`, or import the combined `database/juva-certify-production-clean.sql`. Both paths contain configuration only and no business records, users, tokens, logs or secrets. Verify the SHA-256 file before import.

Create the initial Super Admin from cPanel Terminal with `maintenance/bootstrap-initial-admin.php.dist` and temporary `INITIAL_ADMIN_*` environment values. Clear those values and remove the utility immediately after confirming login. Do not deploy the maintenance directory beneath the public document root.

The `maintenance/sanitize-production-database.php.dist` utility is CLI-only and defaults to dry-run. Never run execute mode against the only database; restore a backup into a copy first. See `database/database-import-guide.md` and `database/database-post-import-checklist.md`.

Runtime storage is intentionally empty. Create private certificate/evidence/preview directories outside the public document root instead of uploading local development storage.
