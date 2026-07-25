# CCU certificate typography and watermark specification

## Typography scale (renderer pixels at 1654 x 2339 A4 raster)

| Element | Previous | Corrected |
|---|---:|---:|
| Company name | 30 | 35 |
| Certificate title | 21 | 28 |
| Regulatory text | 11 | 14 |
| Client/location | 14 | 18 |
| Metadata | 13 | 17 |
| Main headers | 13 | 17 |
| Unit values | 13 | 18 |
| Sling/shackle values | 13 | 17 |
| Load/inspection grids | 11 | 15 |
| Test result | 10 | 15 |
| Due date | 18 | 23 |
| Declaration | 12 | 16 |
| Signatories | 10 | 14 |
| Addresses | 11 | 15 |

Text fitting now uses 1.16 line height, 7px horizontal padding, 5px vertical padding, vertical centering, normal wrapping, and a 13px minimum fallback.

## Row heights (reference-coordinate units)

| Row | Previous | Corrected |
|---|---:|---:|
| Client/location | 48 | 45 |
| Metadata | 36 | 31 |
| Main header | 49 | 38 |
| Unit | 64 | 58 |
| Sling | 80 | 62 |
| Shackle | 78 | 61 |
| Load-test rows | 17 | 15 |
| Inspection rows | 17 | 15 |
| Test-result/due-date | 51 | 42 |
| Declaration | 33 | 27 |
| Signatory | 67 | 52 |
| Address | 64 | 48 |

## Watermark

Asset: `public/assets/certificates/ccu/juva-oil-watermark.png`

- Wording: `JUVA OIL`
- Rotation: 45 degrees, lower-left to upper-right
- Asset alpha: approximately 11% visible opacity
- Placement: reference coordinates x=150, y=260, width=500, height=500
- Layer: drawn before all certificate borders and text
