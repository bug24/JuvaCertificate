# cPanel Deployment Runbook

1. Correct the Git remote or deploy a reviewed artifact; never push JUVA code to `mypropertymath`.
2. Back up the current database, files, DNS and configuration.
3. Enable maintenance mode or schedule an outage.
4. Create `cert.juvaoil.com`, AutoSSL/origin certificate and HTTPS redirect.
5. Place application/private storage outside the document root; expose only built frontend/static assets and approved API entry files.
6. Create a least-privilege database user and import schema/migrations only after duplicate and row-count preflight.
7. Create production `config.local.php` manually. Remove `setup_key` after the first administrator exists.
8. Set directory ownership; source `0644`, directories `0755`, private writable directories `0750` where supported. Never use `777`.
9. Configure verified mail sender, SPF, DKIM and DMARC.
10. Configure CLI cron with a lock for expiry/reminders, preview cleanup, backup verification and log retention.
11. Configure Cloudflare Full Strict, WAF, bot controls, cache bypass and rate limits.
12. Run health, login/OTP, client/equipment, inspection draft/submit/approve/issue, PDF, QR, verification, revision, renewal, revocation and report smoke tests.
13. Go live only after the security report has no unapproved Critical or High finding.

## Rollback

Restore the previous application artifact and configuration, reverse only documented reversible migrations or restore the pre-deployment database, restore matching private storage, revert DNS/proxy changes, clear caches and repeat smoke tests.

