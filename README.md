# JUVA Certify Manager

Inspection and certification management system for JUVA Oil Services Nigeria Limited.

## Architecture

- React + Vite frontend compiled to static HTML/CSS/JavaScript.
- PHP API using PDO, prepared statements and secure sessions. PHP 8.2+ is recommended for production cPanel hosting.
- MySQL/MariaDB database.
- No Node.js process is required on the production cPanel server.

## Local frontend

```powershell
pnpm install
pnpm run dev
```

The current slice includes a secured login shell, OTP-ready privileged access, operational dashboard, client/equipment/certificate registers, dynamic categories/form preview, public verification preview and Users & Roles administration.

## Phase 2 security

- Passwords use `password_hash()` / `password_verify()` only. Legacy MD5 passwords must not be imported.
- Super Admin and Operations Admin accounts require email OTP after password validation.
- Normal sessions expire after 8 hours; Remember Me uses rotating hashed tokens for 30 days.
- Authenticated mutations require CSRF tokens.
- Login, OTP, invitation, password reset, status and session revocation events are audit logged.
- User provisioning is invitation-only after the first bootstrap Super Admin.


## Phase 3 core records

- Client register: list, search-ready API, create and update endpoints.
- Equipment register: linked to clients, asset identifiers, SWL/capacity, location and status.
- Categories/forms: dynamic certification categories with automatic starter form templates and fields.
- Inspections: draft inspection creation linked to client, equipment, category, inspector and active form template.
- Certificates: secured register endpoint with client-user isolation support.
- Dashboard/audit: summary counts, recent inspections and record/security audit feed.

## Phase 4 dynamic certification setup

- Category setup now includes certificate prefix, identifier label, validity duration, template name and theme color.
- Admins can add dynamic form fields with type, required flag, options and sort order.
- Supported field types: text, textarea, number, date, select, checkbox, pass/fail, photo and signature.
- Fields can be removed, and form templates can be published into a new active version.
- Existing Phase 3 databases should run `database/migrations/phase4_dynamic_setup.sql` before using the new setup screen.

## Phase 5 inspection workflow

- Inspections now use a wizard-style workflow: select client, equipment and category; enter a manual reference/certificate ID; run a duplicate check; fill dynamic form fields; upload evidence/signature files; and save as draft or submit for review.
- Supported inspection statuses: Draft, Submitted, Returned for Correction, Approved, Certificate Issued, Revoked and Expired.
- Inspectors and reviewers can add comments and correction notes against each inspection.
- Evidence upload accepts JPG, PNG, WEBP and PDF files up to 8MB and stores hashes with attachment metadata.
- Existing databases should run `database/migrations/phase5_inspection_workflow.sql` before using comments/correction notes.

## Phase 6 certificate generation and verification

- Approved inspections can generate certificate PDF files and barcode SVG files.
- Verification URLs use `/verify/{certificate-token}` and resolve through the public SPA route.
- Public verification returns Valid, Expired, Revoked or Not Found and displays certificate number, client, equipment, inspection type, issue date, expiry date and inspector.
- Generated files are archived under `api/storage/certificates/{certificate-number}/` with revisioned filenames.
- Re-issuing an existing certificate creates a new revision file and keeps older revision files intact.
- Existing databases should run `database/migrations/phase6_certificate_generation.sql` before generating certificates.
## Phase 7 reports, dashboard and audit

- Dashboard now shows live cards for total certificates issued, pending inspections, expiring soon, expired, revoked, active clients, registered equipment and verification scans.
- Reports include certificates issued, expiring certificates, expired certificates, revoked certificates, inspection history, client compliance, equipment history, inspector activity and verification logs.
- The audit trail displays user, action, module, IP address, date/time and details for security and operational activity.
- New audit events store the raw IP address plus the existing salted IP hash; older records may only have the hash.
- Existing databases should run `database/migrations/phase7_reports_audit.sql` before using the expanded audit trail.
## Phase 8 old data migration

- Legacy clients, equipment, inspections, certificates, disabled users and audit trail records can be imported from the old `localhost.sql` tables.
- Old certificate numbers are preserved where possible and remain searchable on public verification.
- Imported certificates receive new secure verification tokens and are marked as legacy records.
- Legacy inspection details that do not map cleanly into dynamic forms are preserved in `legacy_inspection_details` as archive JSON.
- Legacy MD5 passwords are not imported; imported staff accounts are disabled until activated by invitation.
- Existing databases should import the old SQL dump first, then run `database/migrations/phase8_legacy_migration.sql`. See `docs/legacy-data-migration.md`.
## Phase 9 cPanel deployment

- Production target: `https://cert.juvaoil.com` on cPanel with PHP 8.2+, MySQL/MariaDB, Apache rewrite support and HTTPS/AutoSSL.
- Upload the built `dist/` files to the subdomain document root and place `api/` beside `index.html`.
- Configure production secrets in `api/config.local.php`; this app does not use a Laravel `.env` file unless it is later ported to Laravel.
- `api/storage/` is the cPanel-safe storage location for uploaded evidence, certificate PDFs, barcode SVG files and local mail logs.
- Use the daily cron runner at `api/cron/daily.php` for expiry updates and reminder summaries.
- Full deployment instructions are in `docs/cpanel-deployment.md`; backup and restore guidance is in `docs/backup-restore.md`.

For local API testing, set `mail_transport` to `log` in `config.local.php`; messages will be written under `api/storage/mail-log/` instead of being sent.

## Company details

- Office: 52 Rumuolumeni Road, Port Harcourt, Rivers State.
- Operational Yard: 127 Trans Amadi, Port Harcourt, opposite Schlumberger Nigeria Limited.
- Phone: +234 806 516 4945, +234 706 961 2375.
- Email: juvaoil@gmail.com.




