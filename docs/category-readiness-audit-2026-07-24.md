# Category Readiness Audit

Audit date: 2026-07-24

## Production-ready dedicated categories

These categories have category-owned schemas and dedicated renderer dispatch:

- CCU Visual Inspection
- Chain Block
- Endless Round Webbing Sling
- Eye Bolt
- Flat Webbing Sling
- Hook
- Lever Hoist
- MPI / NDT Spreader Bar

## Controlled general categories

- General Lifting Accessories: controlled landscape register for unusual lifting accessories.
- General Thorough Examination: controlled portrait form for unusual equipment.

These are fallback categories. Operators should use a matching dedicated category whenever one exists.

## Legacy categories

Legacy Inspection B, C, and D are marked `legacy`. They remain available to historical inspection, certificate, report, audit, PDF, QR, and verification flows, but are excluded from new-inspection selection.

## Categories requiring completion review

The following remain enabled pending an explicit management decision. They use generic or shared schema/rendering and should be made inactive until their dedicated template acceptance tests are complete:

- Forklift Inspection
- Horizontal Clamp
- Lifting Magnet
- Magnetic Particle Inspection
- Pallet Truck
- Shackles and Accessories
- Universal Clamp
- Vertical Clamp
- Wire Rope Sling

No category in this group was automatically disabled because changing availability can affect current operational work.

## Lifecycle rules

- `active`: selectable for new inspections.
- `inactive`: hidden from new inspections; historical records remain fully accessible.
- `legacy`: historical/imported use only; hidden from new inspections and visibly identified in administration.
- Only a Super Administrator can mark or restore legacy categories.
- Categories with dependencies cannot be hard deleted.
- Hard deletion is limited to unused categories and is audited.
