# Architecture Inventory

Audit baseline: 2026-07-25

- Backend: custom PHP API, no Laravel or Composer runtime. PHP 7.3-compatible syntax; PHP 8.2+ recommended for production.
- Frontend: React 19, TypeScript 5.7, Vite 6; `pnpm-lock.yaml` is authoritative. Production output is `dist/`.
- Database: MySQL 8.0.17 locally, `utf8mb4_unicode_ci`; MariaDB-compatible SQL must be checked on the chosen cPanel host.
- Authentication: PHP sessions, `password_hash`/`password_verify`, CSRF header, OTP for privileged roles, hashed remember/reset/invitation tokens.
- Authorization: endpoint-level permission helpers and client-scoped record fetches.
- API: file-based JSON PHP endpoints under `api/`; standard `{data,error,validation}` responses.
- Storage: `storage-private/` outside public delivery; guarded PHP download handlers. `api/storage/mail-log` is local-only.
- PDF: project-owned PHP renderers and GD/image helpers; no remote renderer service.
- QR: vendored local QR implementation; secure 64-hex verification token URLs.
- Mail: PHP `mail()` in production or file log locally.
- Cron: `api/cron/daily.php`; production should invoke a CLI wrapper with locking, not expose an HTTP key where avoidable.
- Entry points: built `public/index.html`, SPA fallback in `public/.htaccess`, PHP endpoints under `/api`.
- Writable paths: private evidence, signatures, branding, previews, certificates, logs and backup staging only.
- Logging: database business audit and verification/auth-attempt tables; PHP hosting error log is operational logging.
- Current local versions: PHP 7.3.10, MySQL 8.0.17.

## Deployment boundary

Set `cert.juvaoil.com` document root to the deployment public directory. Only built frontend files, approved static certificate assets and the `/api` entry directory may be web-accessible. Configuration, source, tests, migrations, private storage, generated PDFs and logs remain outside the document root.

