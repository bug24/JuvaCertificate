# Security Test Report

## SEC-001: Untrusted proxy headers

- Severity: High
- Evidence: client IP and HTTPS detection trusted forwarded headers from any source.
- Impact: spoofed audit identity, weakened throttling and cookie/transport decisions.
- Fix: forwarded headers are now accepted only from configured proxy CIDRs.
- Retest: PHP syntax and proxy unit behavior required before release.

## SEC-002: Public modern certificate enumeration

- Severity: High
- Evidence: verification accepted predictable modern certificate numbers.
- Impact: bulk discovery of client/equipment/certificate metadata.
- Fix: modern certificates require the 256-bit token; reference compatibility is limited to imported legacy records.
- Retest: token success, modern number generic not-found, legacy reference compatibility.

## SEC-003: Full verification tokens in logs

- Severity: High
- Evidence: `verification_logs.searched_reference` stored scanned tokens.
- Impact: read access to logs could become certificate-link access.
- Fix: new token searches are truncated before logging. Existing rows require a controlled redaction migration after backup.

## SEC-004: Secrets and temporary artifacts in working tree

- Severity: Critical if packaged or pushed
- Evidence: local configuration and temporary OTP/login/cookie files exist.
- Impact: database/admin/session compromise.
- Fix: expanded artifact exclusions; production package and Git-history scans remain mandatory.

## SEC-005: Dependency audit unavailable

- Severity: Medium
- Evidence: npm audit cannot use the pnpm lock; pnpm audit failed because advisory network access was unavailable.
- Fix: run `pnpm audit --prod` from a network-enabled trusted CI runner before release.

## Baseline results

All existing PHP certificate/workflow regression tests passed. TypeScript and Vite production build passed. Modern database identifier/PDF integrity checks passed. Production recommendation remains **NOT READY FOR PRODUCTION** until remaining route-permission, upload bomb/malware, issuance-idempotency, clean-package, restore and staging tests are completed.

