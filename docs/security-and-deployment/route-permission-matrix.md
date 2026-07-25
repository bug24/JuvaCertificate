# Route Permission Matrix

The API uses endpoint-level `require_auth`, `require_permission`, `require_any_permission`, record scoping and CSRF checks.

| Action | Anonymous | Client | Inspector | Reviewer | Operations Admin | Super Admin |
|---|---:|---:|---:|---:|---:|---:|
| Login/reset/OTP | Yes | Yes | Yes | Yes | Yes | Yes |
| Public token verification | Yes | Yes | Yes | Yes | Yes | Yes |
| Public valid/expired PDF by token | Yes | Yes | Yes | Yes | Yes | Yes |
| Own-client certificates | No | Yes | No | No | Yes | Yes |
| Create/edit inspection | No | No | Yes | Limited | Yes | Yes |
| Review/return/approve | No | No | No | Yes | Yes | Yes |
| Issue/revise/revoke | No | No | No | By configured permission | Yes | Yes |
| Manage clients/equipment | No | No | No | No | Yes | Yes |
| Manage category structures | No | No | No | No | Yes | Yes |
| Advanced renderer/category lifecycle | No | No | No | No | No | Yes |
| Manage users/roles/security | No | No | No | No | Limited | Yes |
| Audit trail | No | No | No | No | Yes | Yes |

Every endpoint must remain authoritative; navigation visibility is not authorization. A generated endpoint inventory should be reviewed before release for any mutation lacking CSRF or permission enforcement.

