# MPI / NDT Spreader Bar Certificate Layout Specification

## Page Geometry

- A4 portrait, 1654 x 2339 render canvas, embedded as one 595 x 842 PDF page.
- Outer content margins: 80 px left/right; 70 px top; footer remains inside the page.
- Header: JUVA logo upper left, rounded company banner centered, LEEA upper right.
- Compact derived status sits below the LEEA logo without moving the approved header.

## Vertical Layout

1. Header logos and company banner.
2. `VISUAL/MAGNETIC PARTICLE INSPECTION CERTIFICATE`.
3. Five centered regulatory lines.
4. Examination date, report date and report number row.
5. Client/employer and examination premises row.
6. Two-column item/material details box.
7. Magnetic Particle and Dye Penetrant matrix.
8. NDE procedure reference, method and multiline remarks.
9. Safe-for-use Yes/No row.
10. Inspector/authenticator snapshot and next-examination date.
11. Company authentication block.
12. Rounded registered-office and operational-base box.
13. BSI, ASNT and ACEBI accreditation marks.
14. Compact QR block at bottom right with `SCAN TO VERIFY AUTHENTICITY`.

## Typography And Borders

- Company banner: 36 px bold.
- Certificate title: 28 px bold.
- Regulatory copy: 14-16 px.
- Table labels: 15-18 px; important values bold.
- Borders: 2 px primary tables, 1 px internal cell lines.
- Cell padding: 10-14 px.
- Check boxes: 34-40 px with a two-stroke printed check, centered and clear of borders.

## Matrix

- Magnetic Particle equipment: Coil, Prods, Yoke, UV Light.
- Medium: Dry, Visible, Wet, Fluorescent.
- Current: AC, HWDC, DC.
- Process: Continuous, Residual.
- Dye Penetrant: Penetrant, Developer, Solvent/Cleaner as independent free-text values.
- Only stored selections receive a printed check.

## Field Inventory And Traceability

| Display value | Canonical source | Mapper key | PDF location | Hardcoded |
| --- | --- | --- | --- | --- |
| Examination date | `inspections.inspection_date` | `examination_date` | Metadata row | NO |
| Report date | `inspection_values.report_date` | `report_date` | Metadata row | NO |
| Report number | `inspections.reference` | `certificate_number` | Metadata row | NO |
| Next examination | `inspections.next_due_date` | `next_examination_date` | Signatory row/status | NO |
| Client/name/address | linked `clients` row | `client_name`, `client_address` | Client row | NO |
| Premises | `inspection_values.premises_address` | `fields.premises_address` | Client row | NO |
| Item/material details | canonical item fields | same field keys | Details box | NO |
| MPI selections | four canonical checkbox fields | parsed selection arrays | Matrix boxes | NO |
| Dye values | three canonical text fields | same field keys | Matrix right column | NO |
| Procedure/method/remarks | three canonical NDE fields | same field keys | NDE area | NO |
| Safe for use | `equipment_safe_for_use` | boolean resolver | Yes/No row | NO |
| Inspector/authenticator | user/profile plus snapshots | signatory keys | Signatory area | NO |
| Company identity | company settings | `company.*` | Header/footer | NO |
| Status | certificate status resolver | `status` | Upper right | NO |
| QR | secure verification token URL | `verification_url` | Bottom right | NO |

## Validation Stages

| Field group | Draft | Submit | Approval | Issue/PDF |
| --- | --- | --- | --- | --- |
| Linked client/equipment/category | Save incomplete | Required | Required | Required |
| Examination/report/next dates | Save incomplete | Valid dates | Valid dates | Next date after examination |
| Item/material details | Save incomplete | Required | Required | Required |
| MPI matrix | Save incomplete | Valid allowed selections | Required selections | Required selections |
| Dye Penetrant values | Optional | Optional | Optional | Optional |
| Procedure/method/remarks | Save incomplete | Required | Required | Required |
| Safe for use | Save incomplete | Yes or No required | Required | Required; No is valid data |
| Evidence/signatures | Optional | Optional | Template-controlled | Template-controlled; profile fallback |
