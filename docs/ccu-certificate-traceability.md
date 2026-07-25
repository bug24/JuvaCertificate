# CCU certificate field traceability

All dynamic certificate values follow the production path `form/client/equipment/user/config -> MySQL -> ccu_payload() -> ccu_render_certificate_pdf()`.

| Certificate Display Field | Form Input / Source | Database Table | Column / JSON Key | Data Mapper | PDF Output Location | Hardcoded? |
|---|---|---|---|---|---|---|
| Client name | Selected client with certificate override | inspection_values / clients | client_name / clients.name | ccu_value | Client block | NO |
| Client address | Client record with certificate override | inspection_values / clients | client_address / clients.address | ccu_value | Client block | NO |
| Inspection location | User entered | inspection_values | location_line_1, location_line_2 | ccu_value | Location block | NO |
| Certificate number | System generated scoped sequence | inspections / certificates | reference / certificate_number | issue_certificate | Metadata | NO |
| Customer order number | User entered | inspection_values | customer_order_number | ccu_value | Metadata | NO |
| Date of examination | User entered | inspections / inspection_values | inspection_date / date_of_examination | ccu_display_date | Metadata | NO |
| Next due date | User entered and validated | inspections / inspection_values | next_due_date | ccu_display_date | Metadata/due date | NO |
| Unit ID | User entered/equipment record | inspection_values | unit_id_number | ccu_value | Unit table | NO |
| Asset number | Equipment record/override | inspection_values / equipment | asset_number / asset_code | ccu_value | Unit table | NO |
| Unit quantity | User entered | inspection_values | unit_quantity | ccu_value | Unit table | NO |
| Unit description | User entered/equipment record | inspection_values / equipment | description_of_unit | ccu_value | Unit table | NO |
| Tare weight | User entered | inspection_values | unit_tare_weight | ccu_value | Unit table | NO |
| SWL/payload | User entered, server validated | inspection_values | unit_swl_payload | ccu_value | Unit table | NO |
| Gross weight | User entered, server validated | inspection_values | unit_gross_weight | ccu_value | Unit table | NO |
| Sling ID | User entered | inspection_values | sling_id | ccu_value | Sling table | NO |
| Sling quantity/unit | User entered | inspection_values | sling_quantity, sling_quantity_unit | ccu_value | Sling table | NO |
| Sling description | User entered | inspection_values | sling_details | ccu_value | Sling table | NO |
| Sling tare/SWL/gross | User entered | inspection_values | sling_tare_na, sling_swl, sling_gross_na | ccu_value | Sling table | NO |
| Every shackle row | Repeatable user rows | inspection_items | shackle_details: shackle_id, quantity, quantity_unit, shackle_description, tare_na, swl, gross_na | ccu_item_groups | Shackle table | NO |
| Unit load-test details | User entered | inspection_values | load_unit_id, load_tested_by, load_test_certificate_number, load_proof_load_test, load_test_date | ccu_value | Load-test grid | NO |
| Sling test details | User entered | inspection_values | sling_test_sling_id, sling_manufacturer, sling_oem_certificate_number, sling_proof_load_test, sling_test_date | ccu_value | Load-test grid | NO |
| Standards/equipment type/contrast | User entered | inspection_values | standards, equipment_type, contrast_media | ccu_value | Inspection grid | NO |
| Procedure/pole spacing/indicator | User entered | inspection_values | test_procedure, pole_spacing, indicator | ccu_value | Inspection grid | NO |
| Technique/test method/result | User entered | inspection_values | technique, test_method, test_result | ccu_value | Inspection grid | NO |
| Demagnetization/colour code | User entered | inspection_values | demagnetization, colour_code | ccu_value | Inspection grid | NO |
| Inspector name/qualification | Selected user with certificate fields | users / inspection_values | inspector_id, inspector_name, inspector_qualifications | ccu_value | Signatory block | NO |
| Authenticator name/qualification | User entered/selected reviewer | inspection_values | authenticator_name, authenticator_qualifications | ccu_value | Signatory block | NO |
| Signatures | Uploaded evidence/signature | inspection_attachments / form fields | file_path / signature field | guarded attachment mapping | Signatory block when supplied | NO |
| Company identity/contact | Company configuration | config.local.php | company_* settings | ccu_payload | Header/address blocks | NO |
| Certificate status | System calculated | certificates | status, expires_at, revoked_at | certificate_effective_status | Top-right badge | NO |
| Verification URL/token | Cryptographically generated | certificates | verification_token | issue_certificate / ccu_payload | QR block | NO |
