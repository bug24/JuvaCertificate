# Hook Certificate Layout Specification

## Authoritative Reference

`HOOK.jpg` is the single visual specification for the Hook category. The output is A4 landscape: 842 x 595 PDF points, rendered on a 2339 x 1654 pixel canvas. It must remain one page.

## Shared Certificate Frame

The Hook certificate uses the established JUVA landscape register frame without category-specific alteration:

- LEEA Development Member logo at upper left.
- Centered rounded JUVA-OIL SERVICES (NIG) LIMITED banner.
- JUVA logo at upper right.
- Centered Certificate of Thorough Examination title and statutory text.
- Compact lifecycle status badge.
- Compact accreditation footer and token-based QR verification block.
- Shared font, line, signature, stamp, logo and QR drawing behavior.

Only the bordered Hook register body is category-controlled.

## Hook Body Geometry

1. Metadata line: certificate number, examination date, colour code and standard.
2. Employer row: linked client name/address, inspection premises, fixed status-code legend.
3. Eleven-column Hook register: S/N, identification, description, WLL/SWL, last examination, manufacturer, next examination, reason code, test details, status code and safe-to-use.
4. Fixed reason-code legend: A installation, B six-monthly, C twelve-monthly, D written scheme, E exceptional circumstances.
5. Two-column inspector/authenticator block with optional signatures.
6. Defect/Observation Sheet Attached row with mutually exclusive printed Yes/No marks.

The register supports one to six Hook rows. More than six rows must fail readiness rather than shrink, overlap or spill onto a second page.

## Canonical Mapping

| Visible value | Canonical source | Mapper key | Hardcoded |
|---|---|---|---|
| Certificate number | Inspection reference / certificate | `certificate_number` | NO |
| Examination date | Inspection | `examination_date` | NO |
| Report date | Hook field | `fields.report_date` | NO |
| Colour code | Hook field | `fields.colour_code` | NO |
| Standard | Hook field | `fields.standard` | NO |
| Employer name/address | Linked client | `client_name`, `client_address` | NO |
| Premises | Inspection location | `inspection_location` | NO |
| Equipment rows | `inspection_items.hook_items` | `items` | NO |
| Inspector | Assigned user with optional snapshots | inspector keys | NO |
| Authenticator | Approval/profile with optional snapshots | authenticator keys | NO |
| Defect sheet answer | Hook field | `fields.defect_observation_sheet_attached` | NO |
| Status | Shared lifecycle resolver and due date | `status` | NO |
| QR destination | Secure certificate token route | `verification_url` | NO |

The status and reason explanatory text are fixed certificate labels, not inspection data.

## Validation Stages

- Draft: incomplete fields are allowed and persisted.
- Submit/approval: Hook certificate fields and at least one complete register row are required.
- Preview/issue: the same Hook readiness function validates dates, enums, row count and certificate identity.
- Evidence and per-inspection signatures are optional unless template settings explicitly require them.
- Certificate revisions remain append-only; previous PDFs are never overwritten.
