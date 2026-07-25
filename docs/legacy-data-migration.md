# Phase 8 Legacy Data Migration

This migration brings records from the old `localhost.sql` export into the new JUVA Certify Manager schema.

## Source Tables

The migration expects the old SQL dump to be imported into the same MySQL database before running `database/migrations/phase8_legacy_migration.sql`.

Expected legacy tables:

- `juva_client_master` -> `clients`
- `juva_equipment_master` -> `equipment`
- `juva_equipment_inspt_master` -> `inspections` and `certificates`
- `juva_equipment_inspt_ccu`, `juva_equipment_inspt_excavator`, `juva_equipment_inspt_gen_nde`, `juva_equipment_inspt_mpi`, `juva_equipment_inspt_shackle`, `juva_equipment_inspt_sub`, `juva_equip_insp_check_list` -> `legacy_inspection_details`
- `user_profile` and `user_access` -> disabled `users` with mapped roles
- `audit_trail` -> `audit_logs` as legacy audit records

## Import Order

1. Back up the new database.
2. Import the old `localhost.sql` dump into the same database that contains the new schema.
3. Run `database/migrations/phase8_legacy_migration.sql` once.
4. Check the Reports screen: `Legacy migration` and `Legacy detail archive`.
5. Invite approved legacy staff accounts through Users & Roles. Do not restore old passwords.

## Security Rules

- Legacy MD5 passwords are never imported into `password_hash`.
- Imported users are disabled by default.
- Approved users must be activated through the invitation flow.
- Imported certificates keep the old certificate number where possible.
- New secure verification tokens are generated for imported certificates.
- Imported certificates are marked `is_legacy = 1` and `legacy_mapping_status = partial` where old detail tables cannot fully map to a dynamic form.

## Verification

Old certificate numbers remain searchable on the public verification endpoint:

```text
https://cert.juvaoil.com/verify/JOSL/AOL/S/103
```

New QR/barcode links should use the generated `verification_token` going forward.

## Known Limits

Some old detail tables are certificate-type-specific and do not map perfectly into the new dynamic form builder. Those rows are preserved as JSON in `legacy_inspection_details` so the historical record is not lost.