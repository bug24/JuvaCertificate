START TRANSACTION;

INSERT INTO clients (registration_code, name, contact_person, email, phone, address, logo_path, status, legacy_id)
SELECT
  old.regID,
  old.client_name,
  NULL,
  NULLIF(old.client_email, ''),
  NULLIF(old.client_phone, ''),
  NULLIF(old.client_address, ''),
  NULLIF(old.client_logo, ''),
  CASE WHEN old.status IN ('01','1') THEN 'active' ELSE 'inactive' END,
  old.id
FROM juva_client_master old
LEFT JOIN clients existing ON existing.legacy_id = old.id OR existing.registration_code = old.regID
WHERE existing.id IS NULL;

INSERT INTO clients (registration_code, name, status, legacy_id)
SELECT 'LEGACY-UNASSIGNED', 'Legacy Unassigned Client', 'inactive', 0
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE registration_code = 'LEGACY-UNASSIGNED');

INSERT INTO users (role_id, legacy_reg_id, name, email, username, password_hash, phone, qualification, status, created_at)
SELECT
  CASE
    WHEN ua.sup_adm = '1' THEN (SELECT id FROM roles WHERE slug = 'super-admin' LIMIT 1)
    WHEN ua.report = '1' OR ua.accessID = '001' THEN (SELECT id FROM roles WHERE slug = 'operations-admin' LIMIT 1)
    ELSE (SELECT id FROM roles WHERE slug = 'inspector' LIMIT 1)
  END,
  up.regID,
  TRIM(CONCAT(up.SurName, ' ', up.otherName)),
  CASE WHEN up.eMail = '' THEN CONCAT(LOWER(REPLACE(up.regID, '-', '')), '@legacy.local') ELSE up.eMail END,
  CONCAT(NULLIF(up.userID, ''), '-', LOWER(REPLACE(up.regID, '-', ''))),
  '',
  NULLIF(up.phoneNo, ''),
  NULLIF(up.qualification, ''),
  'disabled',
  NOW()
FROM user_profile up
LEFT JOIN user_access ua ON ua.regID = up.regID
LEFT JOIN users existing ON existing.legacy_reg_id = up.regID
WHERE existing.id IS NULL;

INSERT INTO users (role_id, name, email, username, password_hash, status, created_at)
SELECT (SELECT id FROM roles WHERE slug = 'inspector' LIMIT 1), 'Legacy Import', 'legacy-import@juva.local', 'legacy-import', '', 'disabled', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'legacy-import');

INSERT INTO certification_categories (code, name, description, validity_months, certificate_template, certificate_prefix, identifier_label, theme_color, requires_review, status)
SELECT
  CONCAT('LEG-', NULLIF(m.inspCate, '')),
  CONCAT('Legacy Inspection ', NULLIF(m.inspCate, '')),
  CONCAT('Imported from legacy inspection category ', NULLIF(m.inspCate, ''), '. Original sub-categories are preserved on imported inspection records.'),
  6,
  'Legacy Certificate',
  CONCAT('JOSL-', NULLIF(m.inspCate, '')),
  'Legacy Certificate Number',
  '#515151',
  1,
  'active'
FROM juva_equipment_inspt_master m
LEFT JOIN certification_categories existing ON existing.code = CONCAT('LEG-', NULLIF(m.inspCate, ''))
WHERE NULLIF(m.inspCate, '') IS NOT NULL AND existing.id IS NULL
GROUP BY m.inspCate;

INSERT INTO form_templates (category_id, name, version, status)
SELECT cat.id, CONCAT(cat.name, ' Form'), 1, 'active'
FROM certification_categories cat
LEFT JOIN form_templates ft ON ft.category_id = cat.id AND ft.version = 1
WHERE cat.code LIKE 'LEG-%' AND ft.id IS NULL;

INSERT INTO form_fields (template_id, field_key, label, field_type, is_required, sort_order)
SELECT ft.id, seed.field_key, seed.label, seed.field_type, seed.is_required, seed.sort_order
FROM form_templates ft
JOIN certification_categories cat ON cat.id = ft.category_id
JOIN (
  SELECT 'legacy_certificate_no' AS field_key, 'Legacy Certificate Number' AS label, 'text' AS field_type, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'legacy_title', 'Legacy Inspection Title', 'text', 0, 20
  UNION ALL SELECT 'legacy_safe_for_use', 'Safe For Use', 'text', 0, 30
  UNION ALL SELECT 'legacy_standard', 'Reference Standard', 'text', 0, 40
  UNION ALL SELECT 'legacy_remarks', 'Legacy Remarks', 'textarea', 0, 50
) seed
LEFT JOIN form_fields existing ON existing.template_id = ft.id AND existing.field_key = seed.field_key
WHERE cat.code LIKE 'LEG-%' AND existing.id IS NULL;

INSERT INTO equipment (client_id, asset_code, name, manufacturer, model, serial_number, safe_working_load, location, manufacture_date, reference_standard, status, legacy_id)
SELECT
  c.id,
  CONCAT('LEG-', m.clientID, '-', em.id),
  NULLIF(em.equip_desc, ''),
  NULLIF(em.man_make, ''),
  NULLIF(em.model, ''),
  NULLIF(em.sno_ident_no, ''),
  NULLIF(em.safe_working_load, ''),
  NULLIF(em.equip_location, ''),
  NULLIF(em.date_manufacture, '0000-00-00'),
  NULLIF(em.ref_standard, ''),
  CASE WHEN em.status IN ('01','1') THEN 'active' ELSE 'retired' END,
  em.id
FROM juva_equipment_master em
JOIN (SELECT DISTINCT equipID, clientID FROM juva_equipment_inspt_master) m ON m.equipID = em.id
JOIN clients c ON c.legacy_id = m.clientID
LEFT JOIN equipment existing ON existing.client_id = c.id AND existing.asset_code = CONCAT('LEG-', m.clientID, '-', em.id)
WHERE existing.id IS NULL;

INSERT INTO equipment (client_id, asset_code, name, manufacturer, model, serial_number, safe_working_load, location, manufacture_date, reference_standard, status, legacy_id)
SELECT
  c.id,
  CONCAT('LEG-UNASSIGNED-', em.id),
  NULLIF(em.equip_desc, ''),
  NULLIF(em.man_make, ''),
  NULLIF(em.model, ''),
  NULLIF(em.sno_ident_no, ''),
  NULLIF(em.safe_working_load, ''),
  NULLIF(em.equip_location, ''),
  NULLIF(em.date_manufacture, '0000-00-00'),
  NULLIF(em.ref_standard, ''),
  CASE WHEN em.status IN ('01','1') THEN 'active' ELSE 'retired' END,
  em.id
FROM juva_equipment_master em
JOIN clients c ON c.registration_code = 'LEGACY-UNASSIGNED'
LEFT JOIN equipment existing ON existing.asset_code = CONCAT('LEG-UNASSIGNED-', em.id)
WHERE NOT EXISTS (SELECT 1 FROM juva_equipment_inspt_master m WHERE m.equipID = em.id)
  AND existing.id IS NULL;

INSERT INTO inspections (reference, client_id, equipment_id, category_id, form_template_id, inspector_id, inspection_date, next_due_date, location, result, status, remarks, legacy_id, is_legacy, legacy_source_table, legacy_source_reference, legacy_mapping_status, legacy_payload_json, submitted_at, approved_at, created_at)
SELECT
  CASE WHEN NULLIF(m.cert_no, '') IS NULL THEN CONCAT('LEG-INSP-', m.id) ELSE m.cert_no END,
  c.id,
  e.id,
  cat.id,
  ft.id,
  COALESCE(inspector.id, importer.id),
  COALESCE(NULLIF(m.insp_date, '0000-00-00'), NULLIF(m.insp_report_date, '0000-00-00'), CURDATE()),
  NULLIF(m.next_insp_due, '0000-00-00'),
  NULLIF(m.insp_address_premises, ''),
  CASE WHEN UPPER(m.safe_not) IN ('YES','Y','SAFE','OK') THEN 'safe' WHEN UPPER(m.safe_not) IN ('NO','N','UNSAFE') THEN 'unsafe' ELSE 'pending' END,
  CASE WHEN m.status NOT IN ('01','1') THEN 'revoked' WHEN NULLIF(m.next_insp_due, '0000-00-00') IS NOT NULL AND m.next_insp_due < CURDATE() THEN 'expired' ELSE 'issued' END,
  NULLIF(m.insp_remark, ''),
  m.id,
  1,
  'juva_equipment_inspt_master',
  NULLIF(m.cert_no, ''),
  'partial',
  JSON_OBJECT('insp_title', m.insp_title, 'inspCate', m.inspCate, 'insp_category', m.insp_category, 'insp_report_date', m.insp_report_date, 'insp_date_last_examine', m.insp_date_last_examine, 'insp_photo', m.insp_photo, 'insp_officer', m.insp_officer, 'insp_officer_position', m.insp_officer_position, 'insp_ref_standard', m.insp_ref_standard, 'safe_not', m.safe_not, 'legacy_status', m.status),
  NULLIF(m.insp_report_date, '0000-00-00'),
  NULLIF(m.insp_report_date, '0000-00-00'),
  COALESCE(NULLIF(m.insp_report_date, '0000-00-00'), NOW())
FROM juva_equipment_inspt_master m
JOIN clients c ON c.legacy_id = m.clientID
JOIN equipment e ON e.client_id = c.id AND e.asset_code = CONCAT('LEG-', m.clientID, '-', m.equipID)
JOIN certification_categories cat ON cat.code = CONCAT('LEG-', NULLIF(m.inspCate, ''))
JOIN form_templates ft ON ft.category_id = cat.id AND ft.status = 'active'
LEFT JOIN users inspector ON inspector.status <> 'active' AND m.insp_officer LIKE CONCAT('%', SUBSTRING_INDEX(inspector.name, ' ', -1), '%')
JOIN users importer ON importer.username = 'legacy-import'
LEFT JOIN inspections existing ON existing.legacy_id = m.id AND existing.legacy_source_table = 'juva_equipment_inspt_master'
WHERE existing.id IS NULL;

INSERT INTO certificates (inspection_id, certificate_number, verification_token, revision, issued_at, expires_at, status, legacy_id, is_legacy, legacy_original_reference, legacy_mapping_status, legacy_payload_json, created_at)
SELECT
  i.id,
  CASE WHEN NULLIF(m.cert_no, '') IS NULL THEN CONCAT('LEG-CERT-', m.id) ELSE m.cert_no END,
  SHA2(CONCAT('legacy-certificate|', m.id, '|', COALESCE(NULLIF(m.cert_no, ''), 'none'), '|', UUID()), 256),
  1,
  COALESCE(NULLIF(m.insp_report_date, '0000-00-00'), NULLIF(m.insp_date, '0000-00-00'), CURDATE()),
  NULLIF(m.next_insp_due, '0000-00-00'),
  CASE WHEN m.status NOT IN ('01','1') THEN 'revoked' WHEN NULLIF(m.next_insp_due, '0000-00-00') IS NOT NULL AND m.next_insp_due < CURDATE() THEN 'expired' ELSE 'valid' END,
  m.id,
  1,
  NULLIF(m.cert_no, ''),
  'partial',
  JSON_OBJECT('legacy_insp_category', m.insp_category, 'legacy_inspCate', m.inspCate, 'legacy_photo', m.insp_photo, 'legacy_officer', m.insp_officer, 'legacy_position', m.insp_officer_position),
  COALESCE(NULLIF(m.insp_report_date, '0000-00-00'), NOW())
FROM juva_equipment_inspt_master m
JOIN inspections i ON i.legacy_id = m.id AND i.legacy_source_table = 'juva_equipment_inspt_master'
LEFT JOIN certificates existing ON existing.legacy_id = m.id OR existing.certificate_number = CASE WHEN NULLIF(m.cert_no, '') IS NULL THEN CONCAT('LEG-CERT-', m.id) ELSE m.cert_no END
WHERE existing.id IS NULL;

INSERT INTO legacy_inspection_details (inspection_id, legacy_table, legacy_row_id, detail_json, mapping_status)
SELECT i.id, 'juva_equipment_inspt_ccu', d.id,
  JSON_OBJECT('ccu_slingID_no', d.ccu_slingID_no, 'ccu_SWL', d.ccu_SWL, 'ccu_sling_angle', d.ccu_sling_angle, 'ccu_sling_desc', d.ccu_sling_desc, 'ccu_sling_cert_no_date', d.ccu_sling_cert_no_date, 'ccu_load_date_tested', d.ccu_load_date_tested, 'ccu_load_proof_applied', d.ccu_load_proof_applied, 'ccu_load_comp_name', d.ccu_load_comp_name, 'ccu_load_cert_no', d.ccu_load_cert_no, 'ccu_shacle_ID_no', d.ccu_shacle_ID_no, 'ccu_shacle_SWL', d.ccu_shacle_SWL, 'ccu_shackle_QTY', d.ccu_shackle_QTY, 'ccu_shacle_desc', d.ccu_shacle_desc, 'ccu_shacle_cert_no_date', d.ccu_shacle_cert_no_date),
  'raw_only'
FROM juva_equipment_inspt_ccu d
JOIN inspections i ON i.legacy_id = d.inspMastID AND i.legacy_source_table = 'juva_equipment_inspt_master'
LEFT JOIN legacy_inspection_details existing ON existing.legacy_table = 'juva_equipment_inspt_ccu' AND existing.legacy_row_id = d.id
WHERE existing.id IS NULL;

INSERT INTO legacy_inspection_details (inspection_id, legacy_table, legacy_row_id, detail_json, mapping_status)
SELECT i.id, 'juva_equipment_inspt_shackle', d.id,
  JSON_OBJECT('is_first_exam', d.is_first_exam, 'equipment_con_standard', d.equipment_con_standard, 'next_exam_date', d.next_exam_date, 'ident_defect_part_detected', d.ident_defect_part_detected, 'defect_imm_danger', d.defect_imm_danger, 'defect_could_danger', d.defect_could_danger, 'repair_carried_particulars', d.repair_carried_particulars, 'test_carried_particulars', d.test_carried_particulars),
  'raw_only'
FROM juva_equipment_inspt_shackle d
JOIN inspections i ON i.legacy_id = d.inspMastID AND i.legacy_source_table = 'juva_equipment_inspt_master'
LEFT JOIN legacy_inspection_details existing ON existing.legacy_table = 'juva_equipment_inspt_shackle' AND existing.legacy_row_id = d.id
WHERE existing.id IS NULL;

INSERT INTO legacy_inspection_details (inspection_id, legacy_table, legacy_row_id, detail_json, mapping_status)
SELECT i.id, 'juva_equipment_inspt_mpi', d.id,
  JSON_OBJECT('mpi_equipment', d.mpi_equipment, 'mpi_magnet', d.mpi_magnet, 'mpi_magnet_curr_ac', d.mpi_magnet_curr_ac, 'mpi_magnet_curr_dc', d.mpi_magnet_curr_dc, 'mpi_magnet_curr_hwdc', d.mpi_magnet_curr_hwdc, 'mpi_magnet_particle_coil', d.mpi_magnet_particle_coil, 'mpi_magnet_particle_prods', d.mpi_magnet_particle_prods, 'mpi_magnet_particle_yoke', d.mpi_magnet_particle_yoke, 'mpi_magnet_particle_uv', d.mpi_magnet_particle_uv, 'mpi_magnet_proc_cont', d.mpi_magnet_proc_cont, 'mpi_magnet_proc_res', d.mpi_magnet_proc_res, 'mpi_dye_penetrant', d.mpi_dye_penetrant, 'mpi_dye_developer', d.mpi_dye_developer, 'mpi_solvent_cleaner', d.mpi_solvent_cleaner),
  'raw_only'
FROM juva_equipment_inspt_mpi d
JOIN inspections i ON i.legacy_id = d.inspMastID AND i.legacy_source_table = 'juva_equipment_inspt_master'
LEFT JOIN legacy_inspection_details existing ON existing.legacy_table = 'juva_equipment_inspt_mpi' AND existing.legacy_row_id = d.id
WHERE existing.id IS NULL;

INSERT INTO legacy_inspection_details (inspection_id, legacy_table, legacy_row_id, detail_json, mapping_status)
SELECT i.id, 'juva_equip_insp_check_list', d.id,
  JSON_OBJECT('inspComponent', d.inspComponent, 'inspVal', d.inspVal, 'inspObservation', d.inspObservation, 'inspComment_Rec', d.inspComment_Rec, 'insp_date', d.insp_date, 'legacy_status', d.status),
  'partial'
FROM juva_equip_insp_check_list d
JOIN inspections i ON i.legacy_id = d.inspID AND i.legacy_source_table = 'juva_equipment_inspt_master'
LEFT JOIN legacy_inspection_details existing ON existing.legacy_table = 'juva_equip_insp_check_list' AND existing.legacy_row_id = d.id
WHERE existing.id IS NULL;


INSERT INTO legacy_inspection_details (inspection_id, legacy_table, legacy_row_id, detail_json, mapping_status)
SELECT i.id, 'juva_equipment_inspt_excavator', d.id,
  JSON_OBJECT('visual_insp', d.visual_insp, 'funct_test', d.funct_test, 'legacy_status', d.status),
  'raw_only'
FROM juva_equipment_inspt_excavator d
JOIN inspections i ON i.legacy_id = d.inspMastID AND i.legacy_source_table = 'juva_equipment_inspt_master'
LEFT JOIN legacy_inspection_details existing ON existing.legacy_table = 'juva_equipment_inspt_excavator' AND existing.legacy_row_id = d.id
WHERE existing.id IS NULL;

INSERT INTO legacy_inspection_details (inspection_id, legacy_table, legacy_row_id, detail_json, mapping_status)
SELECT i.id, 'juva_equipment_inspt_gen_nde', d.id,
  JSON_OBJECT('procedure_ref_no', d.procedure_ref_no, 'specification', d.specification, 'material', d.material, 'equipment', d.equipment, 'medium', d.medium, 'indicator', d.indicator, 'method', d.method, 'date_mpi_test', d.date_mpi_test, 'ndt_tech', d.ndt_tech, 'legacy_status', d.status),
  'raw_only'
FROM juva_equipment_inspt_gen_nde d
JOIN inspections i ON i.legacy_id = d.inspMastID AND i.legacy_source_table = 'juva_equipment_inspt_master'
LEFT JOIN legacy_inspection_details existing ON existing.legacy_table = 'juva_equipment_inspt_gen_nde' AND existing.legacy_row_id = d.id
WHERE existing.id IS NULL;

INSERT INTO legacy_inspection_details (inspection_id, legacy_table, legacy_row_id, detail_json, mapping_status)
SELECT i.id, 'juva_equipment_inspt_sub', d.id,
  JSON_OBJECT('equip_desc', d.equip_desc, 'man_make', d.man_make, 'model', d.model, 'man_year', d.man_year, 'sno_ident_no', d.sno_ident_no, 'asset_reg_no', d.asset_reg_no, 'tare_weight', d.tare_weight, 'safe_working_load', d.safe_working_load, 'equip_location', d.equip_location, 'service_type', d.service_type, 'ref_standard', d.ref_standard, 'man_number', d.man_number, 'date_manufacture', d.date_manufacture, 'gross_weight', d.gross_weight, 'dimen_length', d.dimen_length, 'dimen_width', d.dimen_width, 'dimen_height', d.dimen_height, 'dimen_no_lifting', d.dimen_no_lifting, 'dimen_body_forklift_cond', d.dimen_body_forklift_cond, 'type_material', d.type_material, 'area_insp_surf_condtn', d.area_insp_surf_condtn, 'accp_limit_no_crack_accpted', d.accp_limit_no_crack_accpted, 'legacy_status', d.status),
  'raw_only'
FROM juva_equipment_inspt_sub d
JOIN inspections i ON i.legacy_id = d.inspMastID AND i.legacy_source_table = 'juva_equipment_inspt_master'
LEFT JOIN legacy_inspection_details existing ON existing.legacy_table = 'juva_equipment_inspt_sub' AND existing.legacy_row_id = d.id
WHERE existing.id IS NULL;
INSERT INTO audit_logs (user_id, action, entity_type, entity_id, legacy_id, metadata_json, created_at)
SELECT
  u.id,
  CONCAT('legacy.', COALESCE(NULLIF(a.action, ''), LOWER(a.method), 'event')),
  'legacy_audit',
  NULL,
  a.id,
  JSON_OBJECT('legacy_user_id', a.user_ID, 'legacy_user_name', a.userName, 'access', a.access, 'department', a.deptmnt, 'params', a.param, 'method', a.method, 'script', a.scriptName, 'referrer', a.http_reff),
  a.date_time
FROM audit_trail a
LEFT JOIN users u ON u.legacy_reg_id = a.user_ID
LEFT JOIN audit_logs existing ON existing.legacy_id = a.id AND existing.entity_type = 'legacy_audit'
WHERE existing.id IS NULL;

INSERT INTO legacy_migration_runs (source_name, notes, clients_imported, equipment_imported, users_imported, inspections_imported, certificates_imported, details_imported, audit_imported)
SELECT
  'localhost.sql',
  'Legacy SQL imported into normalized Phase 8 schema. Legacy passwords were not imported. Some detail rows are preserved as raw JSON.',
  (SELECT COUNT(*) FROM clients WHERE legacy_id IS NOT NULL),
  (SELECT COUNT(*) FROM equipment WHERE legacy_id IS NOT NULL),
  (SELECT COUNT(*) FROM users WHERE legacy_reg_id IS NOT NULL),
  (SELECT COUNT(*) FROM inspections WHERE is_legacy = 1),
  (SELECT COUNT(*) FROM certificates WHERE is_legacy = 1),
  (SELECT COUNT(*) FROM legacy_inspection_details),
  (SELECT COUNT(*) FROM audit_logs WHERE entity_type = 'legacy_audit');

COMMIT;
