# CCU workflow correction audit

## Duplicate-field audit

| Business meaning | Previous fields | Canonical source | Action |
|---|---|---|---|
| Date of examination | `inspections.inspection_date`, `date_of_examination` | `inspections.inspection_date` | Dynamic duplicate removed; historical values retained through inspection record |
| Next due / expiry | `inspections.next_due_date`, `next_due_date`, `inspection_due_date` | `inspections.next_due_date` | Dynamic duplicates migrated where needed and removed |
| Issue date | User-entered dates plus `certificates.issued_at` | `certificates.issued_at` | System controlled only |
| Client name | Selected client plus required dynamic field | `clients.name` with optional certificate override | Override renamed and made optional |
| Client address | Client record plus required dynamic field | `clients.address` with optional certificate override | Override renamed and made optional |
| Inspector name | Selected inspector plus required dynamic field | `users.name` with optional certificate override | Override renamed and made optional |
| Certificate number | Inspection reference and certificate number | Scoped server sequence copied at issuance | Read-only/system generated |
| Unit ID / asset | Equipment asset plus CCU unit identifier | Equipment selection plus CCU unit ID | Both retained because they identify different business objects |

Active CCU template: category 1, template 27, version 3, 44 fields, one repeatable shackle section. No active field key is duplicated.

## Canonical dates

- Date of examination: `inspections.inspection_date`, user entered.
- Next due date: `inspections.next_due_date`, user entered or category-calculated.
- Issue date: `certificates.issued_at`, set at issuance.
- Created/approved/revoked timestamps: system controlled.
- Effective status: `certificate_effective_status()` from certificate status, expiry, and revocation.

## Required stages

| Stage | Behaviour |
|---|---|
| Draft | Incomplete values accepted and persisted |
| Submit | Active template required fields and repeatable columns enforced by `validate_inspection_values()` / `validate_repeatable_items()` |
| Approval | Reviewer can return to correction; submitted data remains immutable until returned |
| Issue | `ccu_certificate_readiness()` reuses active template requirements and provides exact labels/sections |
| PDF | No independent hidden required-field list |

## Root cause

The generation failure came from three validators disagreeing: duplicate due-date fields, UI asterisks from `form_fields.is_required`, and a second hardcoded list inside the CCU renderer. The active schema and canonical inspection dates are now authoritative.

## Revision findings

Calling Generate/Open for an already-issued unchanged certificate returns the current artifact with `unchanged=true`; it no longer increments the revision. New revisions require the explicit `create-revision.php` endpoint and a reason. Renewals remain new inspections and certificates.

## End-to-end evidence

- Existing non-AVEON CCU: inspection 1043, certificate `JUVA/JGS/CCUVIS/002`.
- Renewal source: certificate 1032 / inspection 1045.
- Editable cloned draft: inspection 1046, reserved reference `JUVA/JGS/CCUVIS/004`.
- Updated dates persisted: examination `2027-07-12`, next due `2028-07-11`.
- Issued renewal: certificate 1033, revision 1.
- Verification token: random 64-character hexadecimal token.
- Missing-field test draft: inspection 1047 / `JUVA/JGS/CCUVIS/005`.
- Exact readiness errors: `Customer Order Number`; `Shackle row 1: Shackle Description`.
- Returned correction test: inspection 1044 with review comment preserved.
