# Database Audit

Baseline checks on 2026-07-25:

- Encoding: `utf8mb4` / `utf8mb4_unicode_ci`
- Duplicate certificate numbers: 0
- Duplicate verification tokens: 0
- Non-legacy issued certificates without PDFs: 0
- Issued certificates without tokens: 0
- Certificates with expiry before issue: 0
- Legacy certificates without local PDFs: 852 (848 expired, 4 revoked)

The legacy PDF gap must be preserved and disclosed; it must not be “fixed” by fabricating archives. Modern certificate identifiers and tokens have unique constraints. Production migration requires a database backup, duplicate preflight, row-count comparison, migration transaction where supported, and post-migration integrity queries.

Recommended application account privileges on `CPANEL_USER_juva_certify.*`: `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES` during controlled migration. Remove `CREATE, ALTER, INDEX, REFERENCES` after migration if the hosting workflow permits. Never grant global privileges, `FILE`, `SUPER`, `CREATE USER`, or `GRANT OPTION`.

