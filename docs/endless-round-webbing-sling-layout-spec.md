# Endless Round Webbing Sling Certificate Layout Specification

## Reference And Scope

The authoritative reference is `BAR CODE CERTIFICATE SAMPLES/ENDLESS ROUND  WEBBING SLING.jpg` (2481 x 3508 pixels). This specification applies only to the `Endless Round Webbing Sling` category. Client, equipment, certificate, date, result, inspector, and authenticator values are dynamic.

## Page Geometry

- Output: one A4 portrait page, rendered at 1654 x 2339 pixels before PDF embedding.
- Main certificate table: approximately 88% page width, centred, with 7-8% side margins.
- Header occupies the upper 17% of the page. The bordered table occupies approximately 63%. The accreditation/QR footer occupies the remaining safe area.
- Borders: dark neutral 1.5-2 pixel rules at render scale. No doubled or overlapping rules.
- Text must wrap within cells. Overflow must fail readiness or be fitted within documented minimum sizes; it must not create page two.

## Header

1. LEEA Development Member logo at upper left, aspect ratio preserved.
2. JUVA Oil logo at upper right, aspect ratio preserved.
3. Rounded, bordered centre banner with `JUVA-OIL SERVICES (NIG) LIMITED`.
4. Centred title: `CERTIFICATE OF THOROUGH EXAMINATION`.
5. Five centred regulatory lines covering LEEA technical requirements, LOLER 1998, Supply of Machinery (Safety) Regulations, PUWER 1998, and Nigeria Factories Act CAP F1 LFN 2004.
6. Compact formal status box in the upper-right safe area: VALID, EXPIRED, or REVOKED.

## Main Table

1. Three-column certificate row: examination date, report date, certificate number.
2. Two-column client row: employer name/address (58%) and examination premises (42%).
3. Equipment row: description block (58%), SWL (11%), manufacture date (15%), last examination date (16%).
4. Two-column checklist block. Each answer uses one stored logical value and dedicated YES/check/NO/check cells.
5. Full-width defect-description row.
6. Immediate-danger row.
7. Potential-danger and corrective-date row.
8. Repair/renewal/alteration row.
9. Tests-carried-out row.
10. Observations row.
11. Fit-for-purpose row with dedicated YES/check/NO/check cells.
12. Three-column signatory row: inspector, authenticator, next examination date.
13. Full-width JUVA company-information row.

## Checklist And Check Marks

- Left column: first examination at new site; installed correctly if applicable.
- Right column: within 6 months; within 12 months; under examination scheme; after exceptional circumstances.
- Checks are two dark vector strokes with a slight handwritten angle.
- Only the selected logical answer receives a mark. The unselected check cell remains empty.
- Marks are centred with a minimum 5-pixel inset from borders.

## Typography

- Company banner: 30-34 px bold.
- Certificate title: 25-28 px bold.
- Regulatory lines: 13-15 px.
- Table labels: 14-17 px; critical values 16-20 px bold.
- Signatory and company details: 13-16 px.
- Minimum fitted body size: 11 px at render scale.

## Footer

- Compact accreditation logos beneath the main table: BSI, LEEA, and ASNT/available JUVA accreditation assets, aspect ratios preserved.
- Compact QR block at bottom right with a four-module quiet zone.
- QR caption: `SCAN TO VERIFY AUTHENTICITY`; domain caption: `cert.juvaoil.com`.
- QR must not overlap logos or certificate borders.

## Dynamic Status

- REVOKED when the certificate has a revocation timestamp/status.
- EXPIRED when the current date is later than the canonical next thorough examination date.
- VALID otherwise.
- The shared certificate status resolver remains authoritative for archive, PDF, verification, and reports.

## Canonical Field Inventory

| Label | Field Key | Section | Type | Required Stage | Source | Duplicate Of | PDF |
|---|---|---|---|---|---|---|---|
| Date of Thorough Examination | `inspections.inspection_date` | Certificate | date | Submit | Inspection | old `date_of_current_examination` | Yes |
| Date of Report | `report_date` | Certificate | date | Issue | Form/default examination date | old issue-date variants | Yes |
| Certificate Number | `inspections.reference` | Certificate | generated | Draft allocation | Inspection | old certificate/reference inputs | Yes |
| Next Thorough Examination | `inspections.next_due_date` | Certificate | date | Submit | Inspection | old `next_due_date` | Yes |
| Client / Employer | linked client | Client | linked | Submit | `clients.name` | old `client_name` | Yes |
| Client Address | linked client | Client | linked | Submit | `clients.address` | old `client_address` | Yes |
| Premises Address | `premises_address` | Client | textarea | Submit | Form | old `inspection_location` | Yes |
| Equipment Type | `equipment_type` | Equipment | text | Submit | Form/equipment | old equipment description | Yes |
| Length | `length` | Equipment | text | Submit | Form | old circumference/effective length | Yes |
| Description | `equipment_description` | Equipment | textarea | Submit | Form | old description | Yes |
| Manufacturer | `manufacturer` | Equipment | text | Submit | Form | old manufacturer | Yes |
| ID Number | `equipment_id_number` | Equipment | text | Submit | Form/equipment asset code | old identifier | Yes |
| Asset Number | `asset_number` | Equipment | text | Optional | Form | none | Yes |
| Standard | `standard` | Equipment | text | Submit | Form | none | Yes |
| Safe Working Load | `safe_working_load` | Equipment | text | Submit | Form | old SWL | Yes |
| Manufacture Date | `date_of_manufacture` | Equipment | date | Optional | Form | old manufacture date | Yes |
| Last Examination Date | `date_of_last_examination` | Equipment | date | Optional | Form | old previous examination | Yes |
| Six checklist answers | canonical boolean keys | Checklist | select Yes/No | Submit | Form | old result-summary fields | Yes |
| Defect Description | `defect_description` | Defects | textarea | Submit | Form | old defects field | Yes |
| Danger Answers | canonical danger keys | Defects | select Yes/No | Conditional | Form | none | Yes |
| Corrective Date | `action_due_date` | Defects | date | Conditional | Form | none | Yes |
| Repairs | `repair_required` | Results | textarea | Issue | Form | old repairs/tests | Yes |
| Tests | `tests_carried_out` | Results | textarea | Issue | Form | old repairs/tests | Yes |
| Observations | `observations` | Results | textarea | Optional | Form | old remarks | Yes |
| Fit for Purpose | `fit_for_purpose` | Fitness | select Yes/No | Issue | Form | old result summary | Yes |
| Inspector/Auth details | snapshot/profile keys | Signoff | linked/text | Issue | User/form | old inspector fields | Yes |

## Validation Stages

| Field Group | Draft | Submit | Approval | Issue/PDF |
|---|---|---|---|---|
| System dates/reference/client/equipment | Optional | Required | Required | Required |
| Equipment identity, length, manufacturer, standard, SWL | Optional | Required | Required | Required |
| Checklist answers | Optional | Required | Required | Required |
| Defect description | Optional | Required (`NONE` allowed) | Required | Required |
| Danger answers/corrective date | Optional | Conditional | Conditional | Conditional |
| Repairs/tests/fitness | Optional | Core values required | Required | Required |
| Evidence | Optional | Configurable | Configurable | Configurable |
| Inspection signature | Optional | Configurable | Configurable | Configurable with profile fallback |
| Authenticator/signature | Optional | Optional | Configurable | Configurable with approver/profile fallback |

## Form-To-PDF Traceability

All dynamic values listed above map through `endless_round_webbing_sling_payload()` into the dedicated renderer. Client/equipment/system dates come from linked records; category-specific values come from `inspection_values`; authentication assets come from `certificate_authentication_assets()`. No client, equipment, certificate number, date, manufacturer, SWL, inspector, authenticator, result, or location is hardcoded in production rendering logic.
