# CCU Certificate Layout Specification

Reference PNG: `BAR CODE CERTIFICATE SAMPLES/AVEON CCU  CERTIFICATE TEMPLATE/1783779191118-2a1cb747-b0c0-4b20-ba01-f6946b9e1cd5_1.png`.
Measured reference image size: 791 x 1024 px.
Renderer target canvas: 1654 x 2339 px. Coordinate mapping uses measured reference positions scaled to target A4 canvas.

## Reference Coordinate Map

| Section | Ref X | Ref Y | Ref W | Ref H | Notes |
| --- | ---: | ---: | ---: | ---: | --- |
| JUVA logo square | 116 | 36 | 53 | 62 | Border square with JUVA icon. |
| Company banner | 171 | 42 | 410 | 36 | Rounded black border/shadow. |
| LEEA logo | 597 | 48 | 58 | 44 | Top right. |
| Certificate title | 245 | 97 | 300 | 16 | Centered. |
| Regulatory text | 200 | 115 | 392 | 40 | Four compact lines. |
| Main table left | 93 | 173 | 614 | 737 | Main bordered certificate body. |
| Client/location row | 93 | 173 | 614 | 48 | Two equal-ish columns. |
| Metadata row | 93 | 221 | 614 | 36 | Four columns. |
| Unit/sling/shackle table | 93 | 257 | 614 | 272 | Six-column shared geometry. |
| Load-test heading | 93 | 533 | 150 | 16 | Blue heading in reference. |
| Load-test table | 93 | 561 | 614 | 66 | Five-column grid. |
| Inspection details | 93 | 628 | 614 | 118 | Bordered grid. |
| Declaration | 93 | 746 | 614 | 33 | Full-width declaration row. |
| Signatories | 93 | 779 | 614 | 67 | Two columns. |
| Address footer | 93 | 846 | 614 | 64 | Two columns. |
| Accreditation logos | 94 | 936 | 238 | 47 | Lower left. |
| Watermark | 260 | 292 | 360 | 460 | Diagonal central grey mark behind content. |
| QR placement | 594 | 934 | 80 | 80 | Added discreetly outside main table. |

## Column Proportions

Shared unit/sling/shackle table width: 614 px.
- ID column: 94 px
- Qty column: 42 px
- Description column: 262 px
- Tare column: 70 px
- SWL/Payload column: 74 px
- Gross column: 73 px

Load-test table width: 614 px.
- Unit ID: 116 px
- Tested By: 104 px
- Certificate No: 122 px
- Proof Load Test: 128 px
- Test Date: 144 px

## Rendering Rules

- All borders are black, one-pixel equivalent on the reference image and scaled consistently on the PDF canvas.
- Header and body text use bold hierarchy matching reference density.
- No red footer, no registry disclaimer, no large QR panel, no revision column.
- Watermark is placed behind all text and borders at low opacity.
- The QR code is placed outside the main table at the lower-right unused area near accreditation marks.
