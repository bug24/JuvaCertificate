# JUVA cPanel Git Deployment

Use one deployment model: the Git checkout is the live working tree.

- Repository path: `/home1/juvaoil/cert.juvaoil.com`
- Document root: `/home1/juvaoil/cert.juvaoil.com/deployment/juva-certify-cpanel-fresh/public`
- cPanel Git Control: click **Update from Remote**, then **Deploy HEAD Commit**. `.cpanel.yml` is required by cPanel but intentionally performs no copy.
- Application config: `/home1/juvaoil/cert.juvaoil.com/deployment/juva-certify-cpanel-fresh/public/api/config.local.php`
- Private storage: `/home1/juvaoil/juva-certify-storage`

After deployment, confirm the commit with cPanel Git Control or Terminal:

```bash
cd /home1/juvaoil/cert.juvaoil.com
git rev-parse HEAD
```

Check health at `/api/health.php`. Super Admins can check the file manifest at `/api/admin/deployment-status.php`.

Keep `api/config.local.php`, private storage, uploads, generated PDFs, QR files, logs, sessions and backups outside Git. Never add SMTP credentials or production database credentials to the repository.