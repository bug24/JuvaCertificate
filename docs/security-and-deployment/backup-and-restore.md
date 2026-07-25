# Backup And Restore

Back up the database, `storage-private/`, production configuration, built/public assets and category certificate assets. Keep an encrypted off-account copy; a backup inside the same cPanel account is not disaster recovery.

Suggested retention: 7 daily, 5 weekly and 12 monthly copies. Alert on backup failure and test a restore quarterly.

## Restore drill

1. Provision an isolated host and restore application files.
2. Restore configuration with new environment-specific secrets.
3. Restore the database, then private storage from the same recovery point.
4. Apply owner and writable-directory permissions without using `777`.
5. Run integrity checks for certificate/token uniqueness, missing modern PDFs, evidence paths and revision relationships.
6. Test login, one modern PDF, one legacy record, QR verification, evidence authorization, reports and audit history.
7. Keep the restored system private until validation passes.

If database and storage recovery points differ, do not issue certificates until reconciliation is complete.

