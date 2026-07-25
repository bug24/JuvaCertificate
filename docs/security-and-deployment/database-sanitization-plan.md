# Database Sanitization Plan

Generated from the live `juva_certify` schema before any development-database mutation. The original database remains unchanged.

## Group Legend

- C: Users, roles and permissions
- D: Company and system settings
- E: Categories, forms and templates
- F: Clients and equipment
- G: Inspections and workflow
- H: Certificates and revisions
- I: Evidence metadata
- J: Verification logs
- K: Audit/application logs
- L: Sessions, resets and temporary authentication state
- M: Legacy imported records
- N: Queue, cron and temporary records

## Table Classification

| Table | Group | Row Count | Keep | Purge | Reseed | Reason | Dependency Order |
|---|---:|---:|---:|---:|---:|---|---:|
| `approvals` | G | 14 | false | true | false | Development business, security, workflow, token or temporary data. | 1 |
| `audit_logs` | K | 6209 | false | true | false | Development business, security, workflow, token or temporary data. | 2 |
| `audit_trail` | K | 5888 | false | true | false | Development business, security, workflow, token or temporary data. | 3 |
| `auth_attempts` | L | 166 | false | true | false | Development business, security, workflow, token or temporary data. | 4 |
| `auth_tokens` | L | 1 | false | true | false | Development business, security, workflow, token or temporary data. | 5 |
| `certificate_branding_settings` | D | 1 | true | false | true | Required application configuration/reference data. | 6 |
| `certificate_revisions` | H | 56 | false | true | false | Development business, security, workflow, token or temporary data. | 7 |
| `certificate_sequences` | H | 19 | false | true | false | Development business, security, workflow, token or temporary data. | 8 |
| `certificates` | H | 880 | false | true | false | Development business, security, workflow, token or temporary data. | 9 |
| `certification_categories` | E | 22 | true | false | true | Required application configuration/reference data. | 10 |
| `clients` | F | 15 | false | true | false | Development business, security, workflow, token or temporary data. | 11 |
| `code_param_desc` | M | 955 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 12 |
| `code_param_tab` | M | 17 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 13 |
| `department_tab` | M | 2 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 14 |
| `email_notifications` | N | 0 | false | true | false | Development business, security, workflow, token or temporary data. | 15 |
| `equipment` | F | 41 | false | true | false | Development business, security, workflow, token or temporary data. | 16 |
| `folder_grouping` | M | 4 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 17 |
| `form_fields` | E | 708 | true | false | true | Required application configuration/reference data. | 18 |
| `form_repeatable_columns` | E | 74 | true | false | true | Required application configuration/reference data. | 19 |
| `form_repeatable_sections` | E | 10 | true | false | true | Required application configuration/reference data. | 20 |
| `form_templates` | E | 36 | true | false | true | Required application configuration/reference data. | 21 |
| `inspection_attachments` | I | 0 | false | true | false | Development business, security, workflow, token or temporary data. | 22 |
| `inspection_comments` | G | 24 | false | true | false | Development business, security, workflow, token or temporary data. | 23 |
| `inspection_items` | G | 198 | false | true | false | Development business, security, workflow, token or temporary data. | 24 |
| `inspection_values` | G | 1022 | false | true | false | Development business, security, workflow, token or temporary data. | 25 |
| `inspections` | G | 898 | false | true | false | Development business, security, workflow, token or temporary data. | 26 |
| `juva_client_master` | M | 6 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 27 |
| `juva_equip_insp_check_list` | M | 36 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 28 |
| `juva_equipment_insp_calibration` | M | 0 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 29 |
| `juva_equipment_inspt_ccu` | M | 12 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 30 |
| `juva_equipment_inspt_excavator` | M | 2 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 31 |
| `juva_equipment_inspt_gen_nde` | M | 12 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 32 |
| `juva_equipment_inspt_master` | M | 852 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 33 |
| `juva_equipment_inspt_mpi` | M | 0 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 34 |
| `juva_equipment_inspt_shackle` | M | 838 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 35 |
| `juva_equipment_inspt_sub` | M | 852 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 36 |
| `juva_equipment_master` | M | 11 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 37 |
| `legacy_inspection_details` | M | 1752 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 38 |
| `legacy_migration_runs` | M | 1 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 39 |
| `message_master_sub` | M | 4 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 40 |
| `message_master_tab` | M | 4 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 41 |
| `otp_challenges` | L | 49 | false | true | false | Development business, security, workflow, token or temporary data. | 42 |
| `remember_tokens` | L | 25 | false | true | false | Development business, security, workflow, token or temporary data. | 43 |
| `roles` | C | 5 | true | false | true | Required application configuration/reference data. | 44 |
| `system_configuration` | M | 1 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 45 |
| `system_configuration_add` | M | 1 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 46 |
| `user_access` | M | 5 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 47 |
| `user_profile` | M | 5 | false | true | false | Unapproved legacy or migration-test data; original source is retained separately. | 48 |
| `users` | C | 8 | false | true | false | Development business, security, workflow, token or temporary data. | 49 |
| `verification_logs` | J | 166 | false | true | false | Development business, security, workflow, token or temporary data. | 50 |

## Preservation Rules

Preserve roles, category definitions and statuses, all form/template versions, fields, repeatable definitions, renderer/layout metadata, and production-safe branding structure. All form placeholders and category source-sample paths are cleared in the production copy.

## Purge Rules

Purge users, clients, equipment, workflow instances, certificates, revisions, verification tokens/logs, uploads, audit history, authentication state, notifications, and unapproved legacy rows in foreign-key order. Foreign-key checks remain enabled.

## Initial Admin Strategy

The clean SQL contains no users. Run `bootstrap-initial-admin.php` once with environment-provided values, verify login, clear the environment variables, and remove/disable the bootstrap utility. No password or hash is stored in SQL or documentation.

## Numbering Policy

No sequence rows are shipped because there are no clients. Per-client/per-category numbering begins only after production clients are created. JUVA must approve any historical opening sequence before launch; the sanitizer does not guess or reserve numbers.

## Legacy Decision

No historical legacy business records are included because no reviewed historical dataset has been formally approved. Legacy schemas and inactive/legacy category definitions remain available for compatibility. The original legacy SQL and full pre-sanitization backup remain separate from deployment.
