# Production Security Scorecard

Assessment date: 2026-07-25

| Area | Score |
|---|---:|
| Application Security | 78/100 |
| Authentication | 82/100 |
| Authorization | 75/100 |
| Database Security | 81/100 |
| File Security | 78/100 |
| Certificate Integrity | 88/100 |
| Public Verification Security | 84/100 |
| DDoS And Abuse Protection | 58/100 |
| Backup And Recovery | 55/100 |
| cPanel Deployment Readiness | 72/100 |

## Recommendation

**READY AFTER REQUIRED FIXES**

## Launch blockers

1. Run `pnpm audit --prod` in trusted network-enabled CI and remediate any Critical/High advisory.
2. Correct the unrelated Git remote before any commit or push.
3. Rotate all local/test credentials that have appeared in chat, logs, screenshots or archives; create production-only secrets.
4. Redact historical full verification tokens already stored in `verification_logs` using a backed-up, reviewed migration.
5. Validate the clean artifact on cPanel staging with PHP 8.2+, AutoSSL, real mail, private storage permissions and the target MariaDB/MySQL version.
6. Complete role-by-role direct-URL/IDOR tests for every API route.
7. Perform and document a database plus private-storage restore drill.
8. Configure Cloudflare/upstream WAF and rate controls; application throttles are not volumetric DDoS protection.
9. Validate cron locking, expiry/reminder processing, backup monitoring and log rotation on the actual hosting account.
10. Run staging load tests and define realistic cPanel concurrency limits.

No commit, push or live deployment is authorized by this report.

