# cPanel Git Deployment

Repository path:
`/home1/juvaoil/cert.juvaoil.com`

Live document root:
`/home1/juvaoil/cert.juvaoil.com/deployment/juva-certify-cpanel-fresh/public`

## cPanel sequence

1. Create or confirm the repository path above in Git Version Control.
2. Confirm the checked-out branch is `main`.
3. Click **Update from Remote** to fetch and check out the latest commit.
4. Confirm the displayed HEAD commit is the intended release.
5. Click **Deploy HEAD Commit** only after cPanel reports a valid `.cpanel.yml` and no uncommitted changes.
6. Do not manually copy files into the repository after deployment. Manual changes are overwritten by the next Git update.
7. Visit `/api/health.php` and confirm database, private storage, sessions, and deployment dependencies pass.
8. A Super Admin can inspect the authenticated deployment marker at `/api/admin/deployment-status.php`.

`.cpanel.yml` deliberately performs no copy operation because the repository already contains the live tree under `deployment/juva-certify-cpanel-fresh/public`. Copying that directory back into itself would create duplicate or partial trees.

## Files outside Git

Preserve these outside the repository and never overwrite them during deployment:

- `/home1/juvaoil/cert.juvaoil.com/api/config.local.php`
- `/home1/juvaoil/juva-certify-storage`
- private uploads and evidence
- logs
- sessions
- generated certificate PDFs and QR assets
- production database

The application must retain access to those paths after every pull/deploy.

## Required tracked runtime

The deployment tree must contain:

- `public/api/`
- `public/api/lib/certificate_engine.php`
- `public/api/certificates/templates/`
- `public/fonts/`
- `public/assets/`
- `public/.htaccess`
- the built frontend `public/index.html` and assets
