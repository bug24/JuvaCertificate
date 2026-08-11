START TRANSACTION;
SET @category_id := (SELECT id FROM certification_categories WHERE code='SHK' ORDER BY id LIMIT 1);
UPDATE certification_categories SET short_code='SHK',name='Shackles',template_family='shackles',layout_key='shackles-landscape-v1',source_sample='SHACKLES AND ACCESSORIES.pdf',certificate_template='Certificate of Thorough Examination',schema_version=2,status='active',updated_at=UTC_TIMESTAMP() WHERE id=@category_id;
UPDATE form_templates SET status='archived',updated_at=UTC_TIMESTAMP() WHERE category_id=@category_id AND status='active';
INSERT INTO form_templates (category_id,name,version,status,show_inspector_signature,show_authenticator_signature,show_company_stamp,requires_evidence,minimum_evidence_files,allowed_evidence_types,requires_inspection_photo,requires_inspector_signature,requires_authenticator_signature,requires_company_stamp,created_at,updated_at)
SELECT @category_id,'Shackles Thorough Examination',COALESCE(MAX(version),0)+1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM form_templates WHERE category_id=@category_id;
SET @template_id := LAST_INSERT_ID();
INSERT INTO form_fields (template_id,field_key,label,help_text,field_type,is_required,options_json,validation_json,appears_on_pdf,editable_after_approval,pdf_section,sort_order,created_at) VALUES
(@template_id,'client_address','Client / Employer Address','Address of employer shown on the certificate.','textarea',0,NULL,NULL,1,0,'summary',10,UTC_TIMESTAMP()),
(@template_id,'inspection_location','Address of Premises','Premises where the examination was made.','text',1,NULL,NULL,1,0,'summary',20,UTC_TIMESTAMP()),
(@template_id,'accessory_type','Shackle Type','Bow, dee, screw-pin or other type.','text',1,NULL,NULL,1,0,'equipment',30,UTC_TIMESTAMP()),
(@template_id,'size_marking','Size / Marking','Shackle size, pin diameter or manufacturer marking.','text',0,NULL,NULL,1,0,'equipment',40,UTC_TIMESTAMP()),
(@template_id,'manufacturer','Manufacturer','Manufacturer shown on the shackle.','text',1,NULL,NULL,1,0,'equipment',50,UTC_TIMESTAMP()),
(@template_id,'standard','Standard','Applicable examination standard.','text',1,NULL,NULL,1,0,'equipment',60,UTC_TIMESTAMP()),
(@template_id,'date_of_manufacture','Date of Manufacture','Where marked or known.','date',0,NULL,NULL,1,0,'equipment',70,UTC_TIMESTAMP()),
(@template_id,'date_of_previous_examination','Date of Last Thorough Examination','Previous examination date where known.','date',0,NULL,NULL,1,0,'examination',80,UTC_TIMESTAMP()),
(@template_id,'safe_working_load','SWL / WLL','Rated safe working load or working load limit.','text',1,NULL,NULL,1,0,'examination',90,UTC_TIMESTAMP()),
(@template_id,'pin_condition','Pin / Thread Condition','Pass/fail condition of pin and threads.','pass_fail',1,NULL,NULL,1,0,'inspection',100,UTC_TIMESTAMP()),
(@template_id,'body_distortion_check','Body Distortion Check','Pass/fail body and bow condition.','pass_fail',1,NULL,NULL,1,0,'inspection',110,UTC_TIMESTAMP()),
(@template_id,'defect_observation_sheet_attached','Defect / Observation Sheet Attached?','Whether a separate sheet accompanies the report.','select',1,'["Yes","No"]',NULL,1,0,'signoff',120,UTC_TIMESTAMP());
INSERT INTO form_repeatable_sections (template_id,section_key,label,help_text,min_rows,max_rows,pdf_section,sort_order,created_at,updated_at) VALUES
(@template_id,'shackles_items','Shackles Examination Register','Add each shackle covered by this certificate. Maximum six rows per one-page certificate.',1,6,'equipment_register',200,UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @section_id := LAST_INSERT_ID();
INSERT INTO form_repeatable_columns (section_id,column_key,label,column_type,is_required,options_json,validation_json,placeholder_text,appears_on_pdf,editable_after_approval,sort_order,created_at,updated_at) VALUES
(@section_id,'serial_number','Identification / Serial Number','text',1,NULL,NULL,NULL,1,0,10,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'description','Description','text',1,NULL,NULL,NULL,1,0,20,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'swl_wll','SWL / WLL','text',1,NULL,NULL,NULL,1,0,30,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'manufacturer','Manufacturer','text',1,NULL,NULL,NULL,1,0,40,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'date_last_examined','Date of Last Examination','date',0,NULL,NULL,NULL,1,0,50,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'remarks','Test Details / Remarks','textarea',0,NULL,NULL,NULL,1,0,60,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
(@section_id,'status','Status','select',1,'["ND","SDR","NF","OBS"]',NULL,NULL,1,0,70,UTC_TIMESTAMP(),UTC_TIMESTAMP());
COMMIT;
