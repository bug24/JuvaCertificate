# Eye Bolt Certificate Layout Specification

## Source

Authoritative reference: `BAR CODE CERTIFICATE SAMPLES/EYE BOLT.jpg`.

The Eye Bolt certificate is a dedicated A4 landscape document. It does not use the generic certificate renderer.

## Geometry

- PDF page: A4 landscape, 842 x 595 points.
- Render canvas: 2339 x 1654 pixels.
- Header: LEEA logo left, JUVA logo right, centered rounded company banner.
- Title and five-line regulatory statement sit below the banner.
- Metadata row contains certificate number, thorough examination date, colour code, and standard.
- Employer, premises, and fixed status legend occupy the row above the item register.
- The item register has eleven columns and supports one to six rows.
- The reason-code legend is directly below the register.
- Inspector and authenticator snapshots use a two-column sign-off block.
- Defect / Observation Sheet Attached uses mutually exclusive Yes/No marks.
- Accreditation assets sit at bottom left. A compact scan-only QR and live document status sit at bottom right.

The renderer must reject more than six item rows instead of shrinking text beyond readability or creating overlap.

## Canonical Data Sources

| Visible value | Source | Mapper key |
|---|---|---|
| Certificate number | `inspections.reference` / `certificates.certificate_number` | `certificate_number` |
| Examination date | `inspections.inspection_date` | `examination_date` |
| Client name and address | linked `clients` record | `client_name`, `client_address` |
| Premises | `inspections.location` | `inspection_location` |
| Colour code | `inspection_values.colour_code` | `fields.colour_code` |
| Standard | `inspection_values.standard` | `fields.standard` |
| Item rows | `inspection_items.eye_bolt_items` | `items` |
| Inspector | linked user with optional saved snapshots | `inspector_name`, `inspector_qualification` |
| Authenticator | saved certificate snapshots | `authenticator_name`, `authenticator_qualification` |
| Next due / public status | `inspections.next_due_date` and certificate lifecycle | `expiry_date`, `status` |
| QR destination | secure certificate verification token URL | `verification_url` |

## Codes

- Reason: `A` installation, `B` six-monthly, `C` twelve-monthly, `D` written scheme, `E` exceptional circumstance.
- Status: `ND` no defect, `SDR` see defect report, `NF` not found, `OBS` observation.
- Safe to Use and Defect / Observation Sheet Attached are stored as canonical `Yes` or `No` values.

## Validation Stages

- Draft: incomplete data may be saved.
- Preview: canonical dates, colour code, standard, signatory display names, and one complete item row are required.
- Issue: uses the same readiness function as preview, plus configured evidence/signature requirements.
- Historical issued documents remain immutable; regeneration creates a certificate revision.

## QR Contract

The QR encodes only the canonical token route: `https://cert.juvaoil.com/verify/{verification_token}` in production, or the configured local application URL in development. Human-readable certificate numbers are never used as QR secrets.
