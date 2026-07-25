# Secret Audit

## Findings

- **Critical deployment risk:** `api/config.local.php` contains a real local database password, security salt, cron key and setup key. It is gitignored but must never enter an artifact or Git history.
- Temporary login/OTP/cookie/request files exist in the working tree. They are now excluded by `.gitignore` and must be absent from the production artifact.
- `setup-local.ps1` and temporary scripts contain development credentials. They are local setup material only.
- The configured Git remote points to the unrelated `bug24/mypropertymath` repository. No JUVA commit or push is permitted to that remote.
- No private-key block was found in application source during the baseline scan.

## Required production handling

Create `api/config.local.php` directly on cPanel from placeholders. Use a unique least-privilege database password, 64+ random bytes for the security salt, production mail credentials, approved proxy CIDRs and no `setup_key` after bootstrap. Rotate any credential that has entered chat, screenshots, logs, archives or Git history.

