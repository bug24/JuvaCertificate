# Categories & Forms UI Control Map

| Friendly control | Stored field | Safety disposition |
| --- | --- | --- |
| Name, short code, internal code, description, validity, status, review rule | `certification_categories` matching columns | Visible; saved by partial PATCH |
| Template family, layout key, source sample, schema version | `certification_categories` technical columns | Super Admin read-only advanced panel |
| Certificate template, prefix, identifier label, theme | `certification_categories` technical columns | Super Admin read-only advanced panel |
| Signature, stamp and evidence rules | `form_templates` matching columns | Friendly switches |
| Evidence formats | `form_templates.allowed_evidence_types` | Friendly checkboxes converted to the original MIME string |
| Field label/type/help/required/PDF visibility | `form_fields` matching columns | Friendly field creator |
| Field key, PDF section and sort order | `form_fields` mapping columns | Generated/hidden; existing key immutable; move buttons replace raw sort |
| Table/column labels and options | `form_repeatable_*` matching columns | Friendly builders |
| Table and column keys | `form_repeatable_*` mapping columns | Generated/hidden; existing keys immutable |

## Safety boundary

- Existing fields, tables and columns are not hard-deleted from this screen.
- Category saves omit hidden renderer metadata, and the API preserves omitted values.
- Publishing remains an explicit confirmation and creates a new version.
- No renderer, inspection, issuance, PDF, QR or verification code is part of this cleanup.
