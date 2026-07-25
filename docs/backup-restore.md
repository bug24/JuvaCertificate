# Backup And Restore Guide

Backups are critical because issued certificates and verification history are legal/compliance records.

## What To Back Up

1. Database
   - All application tables.
   - Legacy migration tables.
   - Audit and verification logs.

2. Uploaded files
   - `api/storage/evidence/`
   - Inspector photos, client evidence, signatures and PDFs uploaded during inspections.

3. Certificate archive
   - `api/storage/certificates/`
   - Generated certificate PDFs.
   - Barcode/QR SVG files.
   - Revision history files.

4. Configuration
   - `api/config.local.php`
   - Keep this encrypted or stored in a private password vault because it contains credentials.

## cPanel Backup Options

Use at least one of these:

- cPanel Backup Wizard for full account backup.
- phpMyAdmin export for database-only backups.
- File Manager compressed archive of `api/storage/`.
- Hosting-provider automated backups if available.

## Suggested Schedule

- Database: daily.
- Uploaded evidence and certificate archive: daily or every 12 hours when inspection volume is high.
- Full account backup: weekly.
- Off-server copy: weekly minimum.

## Manual Database Backup

From cPanel phpMyAdmin:

1. Select the production database.
2. Export.
3. Choose SQL format.
4. Save with date in filename, for example `juva_certify_2026-06-27.sql`.

From CLI where available:

```bash
mysqldump -u CPANELUSER_juva_certify -p CPANELUSER_juva_certify > juva_certify_$(date +%F).sql
```

## Manual File Backup

Archive these folders:

```text
api/storage/evidence/
api/storage/certificates/
```

Keep the folder structure unchanged so stored database paths remain valid after restore.

## Restore Order

1. Restore code files to the subdomain document root.
2. Restore `api/config.local.php`.
3. Restore database SQL.
4. Restore `api/storage/evidence/`.
5. Restore `api/storage/certificates/`.
6. Check file permissions.
7. Visit `/api/health.php`.
8. Test login, public verification, PDF preview/download and report pages.

## Backup Verification

At least once a month, restore a backup to a staging subdomain or local machine and confirm:

- Admin login works.
- A recent certificate verifies publicly.
- A legacy certificate verifies publicly.
- A generated PDF opens.
- Evidence files open from an inspection record.
- Reports and audit trail load.