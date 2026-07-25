START TRANSACTION;

SET @category_id := (SELECT id FROM certification_categories WHERE code='ERS' OR short_code='ERWSLNG' ORDER BY id LIMIT 1);

UPDATE certification_categories
SET template_family='endless_round_webbing_sling',
    layout_key='endless-round-webbing-sling-v1',
    source_sample='ENDLESS ROUND  WEBBING SLING.jpg',
    certificate_template='Certificate of Thorough Examination',
    schema_version=2,
    updated_at=UTC_TIMESTAMP()
WHERE id=@category_id;

UPDATE form_templates SET status='archived',updated_at=UTC_TIMESTAMP()
WHERE category_id=@category_id AND status='active';

INSERT INTO form_templates (
  category_id,name,version,status,
  show_inspector_signature,show_authenticator_signature,show_company_stamp,
  requires_evidence,minimum_evidence_files,allowed_evidence_types,requires_inspection_photo,
  requires_inspector_signature,requires_authenticator_signature,requires_company_stamp,
  created_at,updated_at
)
SELECT @category_id,'Endless Round Webbing Sling Thorough Examination',COALESCE(MAX(version),0)+1,'active',
       1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,
       UTC_TIMESTAMP(),UTC_TIMESTAMP()
FROM form_templates WHERE category_id=@category_id;
SET @template_id := LAST_INSERT_ID();

INSERT INTO form_fields (
 template_id,field_key,label,help_text,field_type,is_required,options_json,validation_json,
 appears_on_pdf,editable_after_approval,pdf_section,sort_order,created_at
) VALUES
(@template_id,'report_date','Date of Report','Defaults to the thorough examination date when left blank.','date',0,NULL,NULL,1,0,'certificate',10,UTC_TIMESTAMP()),
(@template_id,'premises_address','Address of Premises Where Examination Was Made',NULL,'textarea',1,NULL,NULL,1,0,'client',20,UTC_TIMESTAMP()),
(@template_id,'equipment_type','Equipment Type','Enter the equipment type shown on this certificate.','text',1,NULL,NULL,1,0,'equipment',30,UTC_TIMESTAMP()),
(@template_id,'length','Length',NULL,'text',1,NULL,NULL,1,0,'equipment',40,UTC_TIMESTAMP()),
(@template_id,'equipment_description','Description',NULL,'textarea',1,NULL,NULL,1,0,'equipment',50,UTC_TIMESTAMP()),
(@template_id,'manufacturer','Manufacturer',NULL,'text',1,NULL,NULL,1,0,'equipment',60,UTC_TIMESTAMP()),
(@template_id,'equipment_id_number','ID Number','Defaults to the linked equipment asset code when left blank.','text',0,NULL,NULL,1,0,'equipment',70,UTC_TIMESTAMP()),
(@template_id,'asset_number','Asset Number',NULL,'text',0,NULL,NULL,1,0,'equipment',80,UTC_TIMESTAMP()),
(@template_id,'standard','Standard',NULL,'text',1,NULL,NULL,1,0,'equipment',90,UTC_TIMESTAMP()),
(@template_id,'safe_working_load','Safe Working Load',NULL,'text',1,NULL,NULL,1,0,'equipment',100,UTC_TIMESTAMP()),
(@template_id,'date_of_manufacture','Date of Manufacture',NULL,'date',0,NULL,NULL,1,0,'equipment',110,UTC_TIMESTAMP()),
(@template_id,'date_of_last_examination','Date of Last Thorough Examination',NULL,'date',0,NULL,NULL,1,0,'equipment',120,UTC_TIMESTAMP()),
(@template_id,'first_examination_after_installation','First Examination After Installation or Assembly at a New Site?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',200,UTC_TIMESTAMP()),
(@template_id,'installed_correctly','If Yes, Has the Equipment Been Installed Correctly?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',210,UTC_TIMESTAMP()),
(@template_id,'examined_within_six_months','Examination Carried Out Within 6 Months?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',220,UTC_TIMESTAMP()),
(@template_id,'examined_within_twelve_months','Examination Carried Out Within 12 Months?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',230,UTC_TIMESTAMP()),
(@template_id,'examined_under_scheme','Examination Carried Out Under an Examination Scheme?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',240,UTC_TIMESTAMP()),
(@template_id,'examined_after_exceptional_circumstances','Examination Carried Out After Exceptional Circumstances?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'checklist',250,UTC_TIMESTAMP()),
(@template_id,'defect_description','Defect Identification and Description','Enter NONE when no defect exists.','textarea',1,NULL,NULL,1,0,'defects',300,UTC_TIMESTAMP()),
(@template_id,'defect_immediate_danger','Is the Defect of Immediate Danger?',NULL,'select',0,'["Yes","No"]',NULL,1,0,'defects',310,UTC_TIMESTAMP()),
(@template_id,'defect_future_danger','Could the Defect Become Dangerous?',NULL,'select',0,'["Yes","No"]',NULL,1,0,'defects',320,UTC_TIMESTAMP()),
(@template_id,'action_due_date','Date by Which Corrective Action Must Be Completed',NULL,'date',0,NULL,NULL,1,0,'defects',330,UTC_TIMESTAMP()),
(@template_id,'repair_required','Repair, Renewal or Alteration Required','Enter NONE where no action is required.','textarea',1,NULL,NULL,1,0,'results',340,UTC_TIMESTAMP()),
(@template_id,'tests_carried_out','Tests Carried Out','Enter NONE where no tests were carried out.','textarea',1,NULL,NULL,1,0,'results',350,UTC_TIMESTAMP()),
(@template_id,'observations','Observations / Additional Comments','Enter NONE where there are no additional observations.','textarea',0,NULL,NULL,1,0,'results',360,UTC_TIMESTAMP()),
(@template_id,'fit_for_purpose','Is This Equipment Fit for Purpose?',NULL,'select',1,'["Yes","No"]',NULL,1,0,'fitness',400,UTC_TIMESTAMP()),
(@template_id,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the assigned inspector profile.','text',0,NULL,NULL,1,0,'signoff',500,UTC_TIMESTAMP()),
(@template_id,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the assigned inspector profile.','text',0,NULL,NULL,1,0,'signoff',510,UTC_TIMESTAMP()),
(@template_id,'authenticator_name','Authenticator Name','Uses a matching active user profile signature when available.','text',0,NULL,NULL,1,0,'signoff',520,UTC_TIMESTAMP()),
(@template_id,'authenticator_qualification','Authenticator Qualifications',NULL,'text',0,NULL,NULL,1,0,'signoff',530,UTC_TIMESTAMP());

COMMIT;
