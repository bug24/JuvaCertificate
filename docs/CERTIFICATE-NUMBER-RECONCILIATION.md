# Certificate number reconciliation

This procedure corrects legacy numeric client segments without changing certificate identity.

## Scope and invariants

The canonical number is `JUVA/{CLIENT_SHORT_CODE}/{CATEGORY_SHORT_CODE}/{SEQUENCE}`. The tool derives the client from `inspections.client_id`, reads `clients.short_code`, validates the relational category and sequence, and updates only:

- `inspections.reference`
- `certificates.certificate_number`

It does not change primary keys, client/category ownership, sequence numbers, revisions, verification tokens, PDF/QR paths, hashes, revision rows, verification logs, audit history, or archived files. Existing archived PDFs therefore remain immutable and may visibly contain the historical number. Dynamically rendered downloads use the corrected stored canonical number. Any deliberate archived-artifact replacement must remain a separate revision/recovery decision.

## Mandatory production procedure

1. Back up the production database and record the backup identifier.
2. Confirm the deployed application and private tool are from the reviewed commit.
3. Confirm every affected client has the intended configured `clients.short_code`. It must be 2-12 uppercase letters/digits, contain at least one letter, and contain no slash.
4. Run the tool without `--apply` using the production config:

   `php private/tools/reconcile-certificate-numbers.php --config=/absolute/private/config.php`

5. Save and review the JSON report. Confirm `ready_to_apply` is true, blockers are zero, every old/new pair has the correct relational client, and category and sequence segments are identical.
6. Resolve any blocker in the underlying configuration or data, then repeat the dry-run. Never guess a client code or edit the report.
7. Select an accountable existing user ID for the audit records.
8. During an approved maintenance window, run exactly:

   `php private/tools/reconcile-certificate-numbers.php --apply --actor-user-id=USER_ID --confirm=RECONCILE-CERTIFICATE-NUMBERS --config=/absolute/private/config.php`

9. Review the apply JSON. It must report `idempotent: true` and zero post-apply actions.
10. Run the dry-run command again. It must report zero actions and zero blockers.
11. Verify representative public QR URLs by their existing 64-character verification tokens. They must resolve the same certificate IDs and revisions while showing the corrected number.
12. Verify representative dynamic certificate downloads. Do not overwrite archived PDFs solely to change visible historical text.

## Automatic blockers

Apply is rejected when a client short code is empty, numeric-only, contains invalid characters, or lacks a letter; a reference is malformed; relational category or sequence differs from the stored number; inspection and certificate authoritative values disagree; a nonnumeric client segment is unexpectedly divergent; or a target number collides with another row. Apply also requires an actor ID and the exact confirmation phrase. Updates run in one transaction and use old values in their `WHERE` clauses to detect concurrent changes.
