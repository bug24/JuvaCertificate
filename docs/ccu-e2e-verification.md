# CCU end-to-end verification evidence

- Test inspection ID: 1043
- Test certificate ID: 1031
- Reference: JUVA/JGS/CCUVIS/002
- Client: Jukoso Global Services
- Draft persisted before submission: PASS
- Server-side submission: PASS
- Privileged approval and issuance: PASS
- Generated revision: 1
- Live verification status: VALID
- Secure token length: 64 hexadecimal characters
- Decoded QR destination: http://127.0.0.1:4175/verify/4f031a628f32868feaff09b4454379795249f19e83acc9df2c2aecaf55ee796d
- Public verification API record match: PASS
- PDF guarded download: HTTP 200, 604506 bytes
- VALID resolver test: PASS
- EXPIRED resolver test: PASS
- REVOKED resolver test: PASS

## Hardcoded sample audit

Reference values occur only in the explicit reference fixture, reference documentation, legacy SQL data, and unrelated development demo arrays. None occur in `api/certificates/templates/ccu_visual.php` or the production CCU mapper.

## Footer correction

The gap was caused by fixed y coordinates (929-1018 reference units) retained after table rows were shortened. The footer row now derives from the final address-table y coordinate plus a 10-unit controlled gap.
