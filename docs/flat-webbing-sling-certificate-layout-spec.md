# Flat Webbing Sling Certificate Layout Specification

## Reference And Page

The authoritative reference is `BAR CODE CERTIFICATE SAMPLES/FLAT WEBBING SLING.jpg`. Output is one A4 portrait page, 595 x 842 PDF points, rendered at 1654 x 2339 pixels. The bordered certificate body uses approximately 6 percent side margins. Content overflow must fail readiness instead of overlapping or creating an unintended second page.

## Header

- LEEA Development Member logo: upper left, aspect ratio preserved.
- Rounded JUVA-OIL SERVICES (NIG) LIMITED banner: centered.
- JUVA logo: upper right, aspect ratio preserved.
- Centered certificate title and five-line statutory statement below the banner.
- Compact lifecycle status at upper right without disturbing the reference header.

## Certificate Body

1. Three equal metadata cells: thorough examination date, report date, certificate number.
2. Two equal cells: linked client name/address and certificate-specific inspection premises.
3. Equipment description area at left; SWL, manufacture date, and previous examination date at right.
4. Two-sided checklist with narrow YES/NO selection cells and black vector check marks.
5. Defect description, immediate danger, future danger, corrective date, repair, tests, and observations rows.
6. Full-width fit-for-purpose row with one selected YES/NO mark.
7. Three-column signatory row: inspector, authenticator, next thorough examination date.
8. Full-width JUVA company-information block loaded from system configuration.
9. Compact accreditation logos immediately below the body and a print-safe QR verification block in unused footer space.

## Typography And Lines

- Company banner: largest bold text.
- Certificate title: bold secondary heading.
- Regulatory copy: compact regular text.
- Table labels: bold where the reference emphasizes them; values use regular text.
- Borders are single dark strokes. Cell padding is small and consistent.
- Text is wrapped and reduced only within controlled minimum sizes. It must never cross a border.

## Canonical Field Inventory And Traceability

| Certificate Display Value | Form Field | Linked Record | Database Location | Mapper Key | Renderer Position | Hardcoded |
|---|---|---|---|---|---|---|
| Certificate number | None | Inspection/certificate | `inspections.reference`, `certificates.certificate_number` | `certificate_number` | Metadata row | NO |
| Thorough examination date | None | Inspection | `inspections.inspection_date` | `examination_date` | Metadata row | NO |
| Report date | `report_date` | None | `inspection_values` | `report_date` | Metadata row | NO |
| Next examination date | None | Inspection | `inspections.next_due_date` | `next_examination_date` | Signatory row/status | NO |
| Client name/address | None | Client | `clients.name`, `clients.address` | `client_name`, `client_address` | Client block | NO |
| Inspection premises | None | Inspection | `inspections.location` | `inspection_location` | Premises block | NO |
| Equipment type | `equipment_type` | Equipment fallback | `inspection_values` | `fields.equipment_type` | Equipment block | NO |
| Description | `equipment_description` | None | `inspection_values` | `fields.equipment_description` | Equipment block | NO |
| Length | `sling_length` | None | `inspection_values` | `fields.sling_length` | Equipment block | NO |
| Width | `sling_width` | None | `inspection_values` | `fields.sling_width` | Equipment block | NO |
| Number of plies | `number_of_plies` | None | `inspection_values` | `fields.number_of_plies` | Equipment block | NO |
| Manufacturer | `manufacturer` | None | `inspection_values` | `fields.manufacturer` | Equipment block | NO |
| Identification number | `identification_number` | Equipment fallback | `inspection_values` | `fields.identification_number` | Equipment block | NO |
| Asset number | `asset_number` | None | `inspection_values` | `fields.asset_number` | Equipment block | NO |
| Standard | `standard` | None | `inspection_values` | `fields.standard` | Equipment block | NO |
| SWL/WLL | `safe_working_load` | None | `inspection_values` | `fields.safe_working_load` | Equipment block | NO |
| Manufacture/last exam dates | Dedicated date fields | None | `inspection_values` | matching field keys | Equipment block | NO |
| Checklist answers | Six Yes/No fields | None | `inspection_values` | matching field keys | Checklist | NO |
| Defect/danger/corrective values | Dedicated fields | None | `inspection_values` | matching field keys | Defect rows | NO |
| Repairs/tests/observations | Dedicated fields | None | `inspection_values` | matching field keys | Result rows | NO |
| Fit for purpose | `fit_for_purpose` | None | `inspection_values` | `fields.fit_for_purpose` | Fitness row | NO |
| Inspector/authenticator | Optional snapshots/profile | User/approval | users and `inspection_values` | signatory keys | Signatory row | NO |
| Company identity | None | System settings | local production config | `company` | Company/footer blocks | NO |
| Public QR | None | Certificate token | `certificates.verification_token` | `verification_url` | Footer | NO |

## Validation Matrix

| Field Group | Draft | Submit | Approval | Issue | PDF |
|---|---|---|---|---|---|
| Core client/equipment/category | May be incomplete until draft creation | Required | Required | Required | Required |
| Examination and next due dates | Optional while drafting | Valid and due date later | Same | Same | Same readiness function |
| Flat sling equipment details | Optional while drafting | Required fields enforced | Same | Same | Same readiness function |
| Six checklist answers | Optional while drafting | Each must be Yes or No | Same | Same | Same readiness function |
| Defect assessment | Optional while drafting | Conditional rules enforced | Same | Same | Same readiness function |
| Fitness answer | Optional while drafting | Yes or No required | Same | Same | Same readiness function |
| Evidence and signatures | Optional | Optional unless template settings change | Configured requirements only | Configured requirements only | Missing optional assets leave clean empty areas |

The renderer has no independent hidden required-field list. Preview and issuance both call the Flat Webbing Sling readiness service.
