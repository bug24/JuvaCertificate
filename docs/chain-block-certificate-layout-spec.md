# Chain Block Certificate Layout Specification

Source of truth: `BAR CODE CERTIFICATE SAMPLES/CHAIN BLOCK.pdf`. Category dispatch is `chain_block` / `chain-block-v1`.

## Page Geometry

- A4 portrait, rendered at 1654 x 2339 pixels and embedded as one 595 x 842 point PDF page.
- Main content width: 1444 px; left/right margin: 105 px.
- Header: LEEA at upper left, JUVA at upper right, bordered company banner centered.
- Title and regulatory text sit above the bordered certificate body.
- Body order: metadata, employer/premises, equipment particulars, six-question checklist, defects/corrective actions, fitness, signatories, JUVA address.
- Footer: BSI, ASNT and ACEBI marks left aligned; certificate-token QR right aligned.

## Typography And Lines

- Arial/DejaVu Sans, 15-18 px body, 30 px title, 38 px company banner.
- Dark `#242424` text and one-pixel `#333333` borders.
- Values wrap and shrink inside fixed-height cells; renderer rejects content exceeding the A4 boundary.
- Logos preserve source aspect ratios.

## Check Marks

- Each question stores one `Yes` or `No` value.
- The renderer draws a two-stroke, five-pixel dark tick in only the selected narrow cell.
- YES and NO label cells remain distinct from their adjacent mark cells.

## Measured Table Proportions

- Metadata: three equal columns.
- Employer/premises: 50/50.
- Equipment: 930 px description plus SWL, manufacture and last-examination columns.
- Checklist: 1184 px question plus four 65 px YES/check/NO/check cells.
- Signatories: three approximately equal columns.

