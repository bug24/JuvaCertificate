# Chain Block Form And PDF Traceability

| Certificate value | Canonical source | Mapper key | PDF location | Hardcoded |
|---|---|---|---|---|
| Examination date | `inspections.inspection_date` | `examination_date` | Metadata row | No |
| Report date | `report_date`, fallback issue date | `report_date` | Metadata row | No |
| Certificate number | `inspections.reference` | `certificate_number` | Metadata row | No |
| Next examination | `inspections.next_due_date` | `next_examination_date` | Signatory row/status | No |
| Employer | linked `clients` record | `client_name`, `client_address` | Employer block | No |
| Premises | `premises_address` | field map | Premises block | No |
| Equipment identity | linked `equipment` plus equipment fields | field map | Equipment block | No |
| SWL/manufacture/last exam | canonical equipment fields | field map | Equipment side columns | No |
| Six examination answers | one `Yes`/`No` field each | field map | Checklist tick cells | No |
| Defects and danger answers | defect fields | field map | Defect rows | No |
| Repair/tests/observations | corrective-action fields | field map | Action rows | No |
| Fitness | `fit_for_purpose` | field map | Fitness tick row | No |
| Inspector | linked user, optional issuance snapshot | inspector keys | Signatory row | No |
| Authenticator | authentication fields | field map | Signatory row | No |
| JUVA contact data | application company settings | `company` | Address block | No |
| QR | certificate verification token | `verification_url` | Footer right | No |

## Validation Stages

Drafts may be incomplete. Submission requires core equipment and checklist values. Approval retains correction comments. Issuance and preview share `chain_block_readiness()` and require all PDF-critical fields, canonical dates, signatories, and fitness. Missing-field responses contain exact labels and `inspection_id`.

Historical template version 1 remains archived. Active version 2 contains 32 unique fields and excludes duplicate client, certificate number, current examination date, next-due and expiry inputs.
