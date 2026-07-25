START TRANSACTION;

SET @category_id := (SELECT id FROM certification_categories WHERE code='HOOK' ORDER BY id LIMIT 1);

UPDATE certification_categories SET template_family='hook',layout_key='hook-landscape-v1',source_sample='HOOK.jpg',certificate_template='Certificate of Thorough Examination',schema_version=2,updated_at=UTC_TIMESTAMP() WHERE id=@category_id;
UPDATE form_templates SET status='archived',updated_at=UTC_TIMESTAMP() WHERE category_id=@category_id AND status='active';

INSERT INTO form_templates (category_id,name,version,status,show_inspector_signature,show_authenticator_signature,show_company_stamp,requires_evidence,minimum_evidence_files,allowed_evidence_types,requires_inspection_photo,requires_inspector_signature,requires_authenticator_signature,requires_company_stamp,created_at,updated_at)
SELECT @category_id,'Hook Thorough Examination Register',COALESCE(MAX(version),0)+1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM form_templates WHERE category_id=@category_id;
SET @template_id := LAST_INSERT_ID();

INSERT INTO form_fields (template_id,field_key,label,help_text,field_type,is_required,options_json,validation_json,appears_on_pdf,editable_after_approval,pdf_section,sort_order,created_at) VALUES
(@template_id,'report_date','Date of Report','Defaults to the thorough examination date when left blank.','date',0,NULL,NULL,1,0,'certificate',5,UTC_TIMESTAMP()),
(@template_id,'colour_code','Colour Code',NULL,'text',1,NULL,NULL,1,0,'certificate',10,UTC_TIMESTAMP()),
(@template_id,'standard','Standard','Example: BS EN 13889.','text',1,NULL,NULL,1,0,'certificate',20,UTC_TIMESTAMP()),
(@template_id,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the assigned inspector.','text',0,NULL,NULL,1,0,'signoff',100,UTC_TIMESTAMP()),
(@template_id,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the inspector profile.','text',0,NULL,NULL,1,0,'signoff',110,UTC_TIMESTAMP()),
(@template_id,'authenticator_name','Authenticator Name',NULL,'text',0,NULL,NULL,1,0,'signoff',120,UTC_TIMESTAMP()),
(@template_id,'authenticator_qualification','Authenticator Qualifications',NULL,'text',0,NULL,NULL,1,0,'signoff',130,UTC_TIMESTAMP()),
(@template_id,'defect_observation_sheet_attached','Defect / Observation Sheet Attached?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'signoff',140,UTC_TIMESTAMP());

INSERT INTO form_repeatable_sections (template_id,section_key,label,help_text,min_rows,max_rows,pdf_section,sort_order,created_at,updated_at) VALUES
(@template_id,'hook_items','Hook Examination Register','Add one row for each hook covered by this certificate. Maximum six rows per one-page certificate.',1,6,'equipment_register',200,UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @section_id := LAST_INSERT_ID();

INSERT INTO form_repeatable_columns (section_id,column_key,label,column_type,is_required,options_json,validation_json,placeholder_text,appears_on_pdf,editable_after_approval,sort_order,created_at,updated_at) VALUES
(@section_id,'serial_number','S/N','text',0,NULL,NULL,NULL,1,0,10,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'identification_number','Identification Number','text',1,NULL,NULL,NULL,1,0,20,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'description','Description','text',1,NULL,NULL,NULL,1,0,30,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'working_load_limit','WLL or SWL','text',1,NULL,NULL,NULL,1,0,40,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'last_thorough_examination_date','Last Thorough Examination','date',0,NULL,NULL,NULL,1,0,50,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'manufacturer','Manufacturer','text',1,NULL,NULL,NULL,1,0,60,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'next_thorough_examination_date','Next Thorough Examination','date',1,NULL,NULL,NULL,1,0,70,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'reason_for_examination_code','Reason Code','select',1,'["A","B","C","D","E"]',NULL,NULL,1,0,80,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'test_details','Test Details','text',1,NULL,NULL,NULL,1,0,90,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'status_code','Status','select',1,'["ND","SDR","NF","OBS"]',NULL,NULL,1,0,100,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'safe_to_use','Safe to Use','select',1,'["Yes","No"]',NULL,NULL,1,0,110,UTC_TIMESTAMP(),UTC_TIMESTAMP());

COMMIT;
