# CCU Certificate Comparison Report

Reference: `BAR CODE CERTIFICATE SAMPLES/AVEON CCU  CERTIFICATE TEMPLATE.pdf` and rendered PNG `1783779191118-2a1cb747-b0c0-4b20-ba01-f6946b9e1cd5_1.png`.
Failed output: `BAR CODE CERTIFICATE SAMPLES/cac631a3-30c8-4ed0-8cad-5d52ba4f9a33.pdf`.

| Element | Reference | Failed Output | Required Correction |
| --- | --- | --- | --- |
| Page dimensions | Portrait certificate page; reference PNG is 791 x 1024 px. Main certificate body occupies x 93-707 and y 36-910. | Uses A4 canvas but layout proportions differ and content is redesigned. | Preserve portrait A4 output while mapping all visual elements to reference proportions. |
| Outer margins | Main body left/right margin around 93/84 px in reference image; top body starts y 36. | Different large margins and modern spacing. | Use reference margins and dense document layout. |
| Header logos | JUVA icon in bordered square at upper left; LEEA logo at upper right. | Wrong header composition and earlier removal attempts. | Restore JUVA square and LEEA logo in reference positions. |
| Company-name banner | Rounded black-bordered banner with shadow, text `JUVA-OIL SERVICES (NIG) LIMITED`. | Normal letterhead-style title. | Draw rounded banner with black border/shadow and same text hierarchy. |
| Certificate title | Centered `CERTIFICATE OF VISUAL EXAMINATION`, compact below banner. | Larger modern title with different spacing. | Use compact centered title at reference Y position. |
| Legal and regulatory text | Four regulatory lines under title. | Replaced with generic statement. | Restore original four-line regulatory text. |
| Client/location block | Two bordered columns, left client and right location. | Similar data but different dimensions and spacing. | Use exact two-column block, dense bold labels. |
| Certificate metadata row | Four columns: certificate no, customer order no, date, next due date. | Different modern label cards. | Use four bordered cells immediately below client/location block. |
| Unit table | Six-column grid continuous with sling/shackle table. | Split into modern sections with changed proportions. | Reproduce six-column shared geometry and row order. |
| Sling table | `SLING ID` row directly under unit details. | Separated with section heading and larger spacing. | Integrate into continuous six-column table. |
| Shackle table | `SHACKLE ID` row and shackle details within same grid. | Separated and not visually continuous. | Integrate into common table with multiple shackle IDs supported. |
| Load-test table | Compact heading `LOAD TEST DETAILS`, five columns. | Has added revision box and altered table. | Remove revision box; use exact five-column table. |
| Sling-test table | Second compact row group under load test. | Added different row/columns. | Reproduce sling ID/manufacturer/OEM/proof/test date row. |
| Inspection-details table | Dense bordered grid with standards/equipment/contrast/test procedure/pole/indicator/technique/test method/result/due/demagnetization/colour. | Simplified KV layout. | Rebuild reference grid with same ordering. |
| Declaration | Full-width row immediately after inspection details. | Different wording and placement. | Use reference declaration wording and row position. |
| Inspector block | Left bottom signatory block. | Different signature layout. | Reproduce left block labels and signature area. |
| Authenticator block | Right bottom signatory block. | Different signature layout. | Reproduce right block labels and signature area. |
| Registered address | Left footer column inside border. | Red footer bar added. | Remove red bar; restore bordered address block. |
| Operational address | Right footer column inside border with email/website/phone. | Red footer bar added. | Restore bordered address block. |
| Watermark | Large faint diagonal grey JUVA/JOSL style mark behind tables. | Wrong/weak watermark. | Add central diagonal transparent watermark behind borders/text. |
| Accreditation logos | BSI, ASNT, ACEBI logos below certificate table, lower left. | Missing or changed. | Extract/use supplied logos and place at lower-left reference coordinates. |
| QR placement | No large QR panel in original certificate. | Large QR panel changes document structure. | Use small QR near lower accreditation area without changing main table. |
| Footer | White/black bordered address footer; no colored marketing/footer strip. | Red footer and registry disclaimer. | Remove red footer and registry disclaimer. |

QR decision: use a small QR beside the lower accreditation logos because it preserves the main table geometry and does not dominate or alter the certificate body.
