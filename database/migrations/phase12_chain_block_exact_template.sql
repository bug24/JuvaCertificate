START TRANSACTION;

SET @category_id := (SELECT id FROM certification_categories WHERE code = 'CHBLK' LIMIT 1);
UPDATE certification_categories
SET template_family = 'chain_block', layout_key = 'chain-block-v1', source_sample = 'CHAIN BLOCK.pdf', schema_version = 2, updated_at = UTC_TIMESTAMP()
WHERE id = @category_id;

UPDATE form_templates SET status = 'archived', updated_at = UTC_TIMESTAMP()
WHERE category_id = @category_id AND status = 'active';

INSERT INTO form_templates (category_id, name, version, status, created_at, updated_at)
SELECT @category_id, 'Chain Block Thorough Examination', COALESCE(MAX(version), 0) + 1, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()
FROM form_templates WHERE category_id = @category_id;
SET @template_id := LAST_INSERT_ID();

INSERT INTO form_fields (template_id, field_key, label, help_text, field_type, is_required, options_json, validation_json, appears_on_pdf, editable_after_approval, pdf_section, sort_order, created_at) VALUES
(@template_id,'report_date','Date of Report','Defaults to issue date when left blank.','date',0,NULL,NULL,1,0,'certificate',10,UTC_TIMESTAMP()),
(@template_id,'premises_address','Address of Premises Where Examination Was Made',NULL,'textarea',1,NULL,NULL,1,0,'client',20,UTC_TIMESTAMP()),
(@template_id,'equipment_type','Equipment','Normally CHAIN BLOCK for this category.','text',1,NULL,NULL,1,0,'equipment',30,UTC_TIMESTAMP()),
(@template_id,'equipment_description','Description',NULL,'textarea',1,NULL,NULL,1,0,'equipment',40,UTC_TIMESTAMP()),
(@template_id,'manufacturer','Manufacturer',NULL,'text',0,NULL,NULL,1,0,'equipment',50,UTC_TIMESTAMP()),
(@template_id,'equipment_id_number','ID Number','Defaults to the selected equipment asset code.','text',0,NULL,NULL,1,0,'equipment',60,UTC_TIMESTAMP()),
(@template_id,'asset_number','Asset Number',NULL,'text',0,NULL,NULL,1,0,'equipment',70,UTC_TIMESTAMP()),
(@template_id,'chain_dimensions','Chain Dimensions',NULL,'text',1,NULL,NULL,1,0,'equipment',80,UTC_TIMESTAMP()),
(@template_id,'standard','Standard',NULL,'text',1,NULL,NULL,1,0,'equipment',90,UTC_TIMESTAMP()),
(@template_id,'safe_working_load','Safe Working Load',NULL,'text',1,NULL,NULL,1,0,'equipment',100,UTC_TIMESTAMP()),
(@template_id,'date_of_manufacture','Date of Manufacture',NULL,'date',0,NULL,NULL,1,0,'equipment',110,UTC_TIMESTAMP()),
(@template_id,'date_of_last_examination','Date of Last Thorough Examination',NULL,'date',1,NULL,NULL,1,0,'equipment',120,UTC_TIMESTAMP()),
(@template_id,'first_examination_new_site','First Examination After Installation or Assembly at a New Site?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',200,UTC_TIMESTAMP()),
(@template_id,'installed_correctly','If Yes, Has the Equipment Been Installed Correctly?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',210,UTC_TIMESTAMP()),
(@template_id,'exam_within_6_months','Examination Carried Out Within 6 Months?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',220,UTC_TIMESTAMP()),
(@template_id,'exam_within_12_months','Examination Carried Out Within 12 Months?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',230,UTC_TIMESTAMP()),
(@template_id,'exam_scheme','Examination Carried Out in Accordance With an Examination Scheme?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',240,UTC_TIMESTAMP()),
(@template_id,'exceptional_circumstances','Examination Carried Out After Exceptional Circumstances?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',250,UTC_TIMESTAMP()),
(@template_id,'defect_description','Identification and Description of Defects','Enter NONE when no defects were found.','textarea',1,NULL,NULL,1,0,'defects',300,UTC_TIMESTAMP()),
(@template_id,'defect_immediate_danger','Is the Defect of Immediate Danger?',NULL,'select',0,'["Yes","No"]',NULL,1,0,'defects',310,UTC_TIMESTAMP()),
(@template_id,'defect_future_danger','Could the Defect Become Dangerous?',NULL,'select',0,'["Yes","No"]',NULL,1,0,'defects',320,UTC_TIMESTAMP()),
(@template_id,'action_due_date','Date by Which Action Must Be Taken',NULL,'date',0,NULL,NULL,1,0,'defects',330,UTC_TIMESTAMP()),
(@template_id,'repair_required','Repair, Renewal or Alteration Required',NULL,'textarea',0,NULL,NULL,1,0,'defects',340,UTC_TIMESTAMP()),
(@template_id,'tests_carried_out','Tests Carried Out',NULL,'textarea',1,NULL,NULL,1,0,'defects',350,UTC_TIMESTAMP()),
(@template_id,'observations','Observations / Additional Comments',NULL,'textarea',0,NULL,NULL,1,0,'defects',360,UTC_TIMESTAMP()),
(@template_id,'fit_for_purpose','Is This Equipment Fit for Purpose?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'fitness',400,UTC_TIMESTAMP()),
(@template_id,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the selected inspector.','text',0,NULL,NULL,1,0,'signoff',500,UTC_TIMESTAMP()),
(@template_id,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the selected inspector profile.','text',0,NULL,NULL,1,0,'signoff',510,UTC_TIMESTAMP()),
(@template_id,'inspector_signature_name','Inspector Signature / Signatory Mark',NULL,'text',1,NULL,NULL,1,0,'signoff',520,UTC_TIMESTAMP()),
(@template_id,'authenticator_name','Authenticator Name',NULL,'text',1,NULL,NULL,1,0,'signoff',530,UTC_TIMESTAMP()),
(@template_id,'authenticator_qualification','Authenticator Qualifications',NULL,'text',1,NULL,NULL,1,0,'signoff',540,UTC_TIMESTAMP()),
(@template_id,'authenticator_signature_name','Authenticator Signature / Signatory Mark',NULL,'text',1,NULL,NULL,1,0,'signoff',550,UTC_TIMESTAMP());

COMMIT;
