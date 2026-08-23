-- Phase 28: precision-preserving equipment dates and revised Shackles form.
-- State-aware, rerunnable migration. No certificate or inspection history is updated.

SET @phase28_lock_name := CONCAT('juva:phase28:', DATABASE());
SELECT GET_LOCK(@phase28_lock_name, 60) INTO @phase28_lock_acquired;
DROP TEMPORARY TABLE IF EXISTS phase28_lock_guard;
CREATE TEMPORARY TABLE phase28_lock_guard (
  PHASE28_ERROR_MIGRATION_LOCK_UNAVAILABLE TINYINT NOT NULL
);
INSERT INTO phase28_lock_guard
  VALUES (IF(@phase28_lock_acquired=1,1,NULL));
DROP TEMPORARY TABLE phase28_lock_guard;

DROP PROCEDURE IF EXISTS migrate_phase28_partial_dates_shackles;
DELIMITER $$
CREATE PROCEDURE migrate_phase28_partial_dates_shackles()
phase28: BEGIN
  DECLARE v_count INT DEFAULT 0;
  DECLARE v_columns INT DEFAULT 0;
  DECLARE v_compatible INT DEFAULT 0;
  DECLARE v_category_id BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_short_code VARCHAR(20);
  DECLARE v_name VARCHAR(180);
  DECLARE v_family VARCHAR(50);
  DECLARE v_layout VARCHAR(100);
  DECLARE v_source VARCHAR(190);
  DECLARE v_certificate_template VARCHAR(100);
  DECLARE v_schema_version SMALLINT UNSIGNED;
  DECLARE v_status VARCHAR(20);
  DECLARE v_active INT DEFAULT 0;
  DECLARE v_complete INT DEFAULT 0;
  DECLARE v_complete_active INT DEFAULT 0;
  DECLARE v_complete_archived INT DEFAULT 0;
  DECLARE v_partial INT DEFAULT 0;
  DECLARE v_phase27 INT DEFAULT 0;
  DECLARE v_old_template BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_new_template BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_section BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_version SMALLINT UNSIGNED DEFAULT NULL;
  DECLARE v_invalid BIGINT UNSIGNED DEFAULT 0;
  DECLARE v_backfilled BIGINT UNSIGNED DEFAULT 0;
  DECLARE v_transaction TINYINT DEFAULT 0;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    IF v_transaction=1 THEN ROLLBACK; END IF;
    DO RELEASE_LOCK(@phase28_lock_name);
    RESIGNAL;
  END;

  SELECT COUNT(*) INTO v_count
  FROM information_schema.tables
  WHERE table_schema=DATABASE() AND table_name IN
    ('equipment','certification_categories','form_templates','form_fields',
     'form_repeatable_sections','form_repeatable_columns');
  IF v_count<>6 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: required Phase 11/27 tables are missing';
  END IF;

  SELECT COUNT(DISTINCT CONCAT(table_name,':',index_name)) INTO v_count
  FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND non_unique=0 AND
    ((table_name='form_templates' AND index_name='uq_form_template_version')
     OR (table_name='form_fields' AND index_name='uq_form_field_key')
     OR (table_name='form_repeatable_sections' AND index_name='uq_repeatable_section_key')
     OR (table_name='form_repeatable_columns' AND index_name='uq_repeatable_column_key'));
  IF v_count<>4 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: required form-template unique indexes are missing';
  END IF;

  SELECT COUNT(*) INTO v_columns
  FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='equipment'
    AND column_name IN ('manufacture_date_value','manufacture_date_precision');

  SELECT COUNT(*) INTO v_compatible
  FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='equipment' AND
    ((column_name='manufacture_date_value' AND data_type='varchar'
      AND character_maximum_length=10 AND is_nullable='YES')
     OR
     (column_name='manufacture_date_precision' AND data_type='enum'
      AND column_type='enum(''day'',''month'',''year'')' AND is_nullable='YES'));

  IF v_columns=1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: only one companion date column exists';
  END IF;
  IF v_columns=2 AND v_compatible<>2 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: incompatible companion date column definition';
  END IF;

  SELECT COUNT(*) INTO v_count FROM certification_categories WHERE code='SHK';
  IF v_count<>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: exactly one SHK category is required';
  END IF;

  SELECT id,short_code,name,template_family,layout_key,source_sample,
         certificate_template,schema_version,status
    INTO v_category_id,v_short_code,v_name,v_family,v_layout,v_source,
         v_certificate_template,v_schema_version,v_status
  FROM certification_categories WHERE code='SHK';

  IF NOT (
    v_short_code <=> 'SHK' AND v_name <=> 'Shackles'
    AND v_family <=> 'shackles' AND v_layout <=> 'shackles-landscape-v1'
    AND v_source <=> 'SHACKLES AND ACCESSORIES.pdf'
    AND v_certificate_template <=> 'Certificate of Thorough Examination'
    AND COALESCE(v_schema_version,0)>=2 AND v_status <=> 'active'
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: SHK category is not Phase 27 compatible';
  END IF;

  SELECT COUNT(*) INTO v_active
  FROM form_templates WHERE category_id=v_category_id AND status='active';
  IF v_active>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: multiple active SHK templates found';
  END IF;

  SELECT COUNT(*),COALESCE(SUM(status='active'),0),COALESCE(SUM(status='archived'),0)
    INTO v_complete,v_complete_active,v_complete_archived
  FROM (
    SELECT ft.id,ft.status,
      (SELECT COUNT(*) FROM form_fields f WHERE f.template_id=ft.id) fields_total,
      (SELECT COUNT(*) FROM form_fields f WHERE f.template_id=ft.id AND f.field_key IN
        ('colour_code','standard','reason_for_examination_code','pin_condition',
         'body_distortion_check','defect_observation_sheet_attached')) fields_match,
      (SELECT COUNT(*) FROM form_repeatable_sections s WHERE s.template_id=ft.id) sections_total,
      (SELECT COUNT(*) FROM form_repeatable_sections s WHERE s.template_id=ft.id AND s.section_key='shackles_items') sections_match,
      (SELECT COUNT(*) FROM form_repeatable_columns c JOIN form_repeatable_sections s ON s.id=c.section_id WHERE s.template_id=ft.id) columns_total,
      (SELECT COUNT(*) FROM form_repeatable_columns c JOIN form_repeatable_sections s ON s.id=c.section_id
        WHERE s.template_id=ft.id AND c.column_key IN
        ('identification_number','description','swl_wll','date_last_examined',
         'manufacturer','test_details','status','safe_to_use')) columns_match
    FROM form_templates ft WHERE ft.category_id=v_category_id
  ) a
  WHERE fields_total=6 AND fields_match=6 AND sections_total=1
    AND sections_match=1 AND columns_total=8 AND columns_match=8;

  IF v_complete>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: multiple complete Phase 28 templates found';
  END IF;
  IF v_complete_archived=1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: complete Phase 28 template is archived';
  END IF;
  IF v_complete_active=1 AND v_active<>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: active Phase 28 template has a competitor';
  END IF;
  IF v_complete_active=1 AND v_columns=0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: template exists without companion columns';
  END IF;

  SELECT COUNT(*) INTO v_partial
  FROM form_templates ft
  WHERE ft.category_id=v_category_id
    AND NOT (
      (SELECT COUNT(*) FROM form_fields f WHERE f.template_id=ft.id)=6
      AND (SELECT COUNT(*) FROM form_fields f WHERE f.template_id=ft.id AND f.field_key IN
        ('colour_code','standard','reason_for_examination_code','pin_condition',
         'body_distortion_check','defect_observation_sheet_attached'))=6
      AND (SELECT COUNT(*) FROM form_repeatable_sections s WHERE s.template_id=ft.id)=1
      AND (SELECT COUNT(*) FROM form_repeatable_sections s WHERE s.template_id=ft.id AND s.section_key='shackles_items')=1
      AND (SELECT COUNT(*) FROM form_repeatable_columns c JOIN form_repeatable_sections s ON s.id=c.section_id WHERE s.template_id=ft.id)=8
      AND (SELECT COUNT(*) FROM form_repeatable_columns c JOIN form_repeatable_sections s ON s.id=c.section_id
        WHERE s.template_id=ft.id AND c.column_key IN
        ('identification_number','description','swl_wll','date_last_examined',
         'manufacturer','test_details','status','safe_to_use'))=8
    )
    AND (
      EXISTS (SELECT 1 FROM form_fields f WHERE f.template_id=ft.id
              AND f.field_key IN ('colour_code','reason_for_examination_code'))
      OR EXISTS (
        SELECT 1 FROM form_repeatable_columns c
        JOIN form_repeatable_sections s ON s.id=c.section_id
        WHERE s.template_id=ft.id
          AND c.column_key IN ('identification_number','test_details','safe_to_use')
      )
    );
  IF v_partial>0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: partial Phase 28 template found; no repair attempted';
  END IF;

  IF v_complete_active=0 THEN
    IF v_active=0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: no active Phase 27 SHK template found';
    END IF;

    SELECT COUNT(*) INTO v_phase27
    FROM form_templates ft
    WHERE ft.category_id=v_category_id AND ft.status='active'
      AND (SELECT COUNT(*) FROM form_fields f WHERE f.template_id=ft.id)=12
      AND (SELECT COUNT(*) FROM form_fields f WHERE f.template_id=ft.id AND f.field_key IN
        ('client_address','inspection_location','accessory_type','size_marking',
         'manufacturer','standard','date_of_manufacture','date_of_previous_examination',
         'safe_working_load','pin_condition','body_distortion_check',
         'defect_observation_sheet_attached'))=12
      AND (SELECT COUNT(*) FROM form_repeatable_sections s WHERE s.template_id=ft.id)=1
      AND (SELECT COUNT(*) FROM form_repeatable_sections s WHERE s.template_id=ft.id AND s.section_key='shackles_items')=1
      AND (SELECT COUNT(*) FROM form_repeatable_columns c JOIN form_repeatable_sections s ON s.id=c.section_id WHERE s.template_id=ft.id)=7
      AND (SELECT COUNT(*) FROM form_repeatable_columns c JOIN form_repeatable_sections s ON s.id=c.section_id
        WHERE s.template_id=ft.id AND c.column_key IN
        ('serial_number','description','swl_wll','manufacturer',
         'date_last_examined','remarks','status'))=7;
    IF v_phase27<>1 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: active template is not complete Phase 27';
    END IF;

    SELECT id INTO v_old_template
    FROM form_templates WHERE category_id=v_category_id AND status='active';
  END IF;

  SELECT COUNT(*) INTO v_invalid
  FROM equipment
  WHERE manufacture_date IS NOT NULL AND
    (DATE_FORMAT(manufacture_date,'%Y-%m-%d') IS NULL
     OR CAST(manufacture_date AS CHAR)='0000-00-00');
  IF v_invalid>0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: invalid legacy manufacture dates found';
  END IF;

  IF v_columns=2 THEN
    SELECT COUNT(*) INTO v_invalid
    FROM equipment
    WHERE
      ((manufacture_date_value IS NULL OR TRIM(manufacture_date_value)='')
       AND manufacture_date_precision IS NOT NULL)
      OR (manufacture_date_value IS NOT NULL AND TRIM(manufacture_date_value)<>'' AND NOT (
        (manufacture_date_precision='year' AND manufacture_date_value REGEXP '^[0-9]{4}$')
        OR (manufacture_date_precision='month' AND manufacture_date_value REGEXP '^[0-9]{4}-(0[1-9]|1[0-2])$')
        OR (manufacture_date_precision='day'
            AND manufacture_date_value REGEXP '^[0-9]{4}-(0[1-9]|1[0-2])-([0-2][0-9]|3[01])$'
            AND DATE_FORMAT(STR_TO_DATE(manufacture_date_value,'%Y-%m-%d'),'%Y-%m-%d')=manufacture_date_value)
      ))
      OR (manufacture_date IS NOT NULL AND manufacture_date_value IS NOT NULL
          AND TRIM(manufacture_date_value)<>'' AND
          (NOT (manufacture_date_precision <=> 'day')
           OR manufacture_date_value<>DATE_FORMAT(manufacture_date,'%Y-%m-%d')))
      OR (manufacture_date IS NULL AND manufacture_date_precision='day');
    IF v_invalid>0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: inconsistent companion manufacture-date data';
    END IF;
  END IF;

  -- DDL is outside the later transactions because ALTER TABLE implicitly commits.
  IF v_columns=0 THEN
    ALTER TABLE equipment
      ADD COLUMN manufacture_date_value VARCHAR(10) NULL AFTER manufacture_date,
      ADD COLUMN manufacture_date_precision ENUM('day','month','year') NULL AFTER manufacture_date_value;
  END IF;

  START TRANSACTION;
  SET v_transaction=1;
  UPDATE equipment
  SET manufacture_date_value=DATE_FORMAT(manufacture_date,'%Y-%m-%d'),
      manufacture_date_precision='day'
  WHERE manufacture_date IS NOT NULL
    AND (manufacture_date_value IS NULL OR TRIM(manufacture_date_value)='')
    AND manufacture_date_precision IS NULL;
  SET v_backfilled=ROW_COUNT();

  SELECT COUNT(*) INTO v_invalid
  FROM equipment
  WHERE (manufacture_date IS NOT NULL AND
         (NOT (manufacture_date_value <=> DATE_FORMAT(manufacture_date,'%Y-%m-%d'))
          OR NOT (manufacture_date_precision <=> 'day')))
     OR (manufacture_date IS NULL AND manufacture_date_precision='day')
     OR (manufacture_date_precision IN ('month','year') AND manufacture_date IS NOT NULL);
  IF v_invalid>0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: manufacture-date backfill verification failed';
  END IF;
  COMMIT;
  SET v_transaction=0;

  IF v_complete_active=1 THEN
    SELECT 'ALREADY APPLIED / NO ACTION REQUIRED' phase28_status,
           v_backfilled manufacture_dates_backfilled;
    LEAVE phase28;
  END IF;

  START TRANSACTION;
  SET v_transaction=1;

  SELECT id INTO v_category_id
  FROM certification_categories WHERE id=v_category_id FOR UPDATE;

  SELECT version INTO v_version
  FROM form_templates WHERE category_id=v_category_id
  ORDER BY version DESC,id DESC LIMIT 1 FOR UPDATE;
  SET v_version=v_version+1;

  SELECT COUNT(*) INTO v_count
  FROM form_templates
  WHERE id=v_old_template AND category_id=v_category_id AND status='active';
  IF v_count<>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: active template changed during migration';
  END IF;

  INSERT INTO form_templates
    (category_id,name,version,status,show_inspector_signature,
     show_authenticator_signature,show_company_stamp,requires_evidence,
     minimum_evidence_files,allowed_evidence_types,requires_inspection_photo,
     requires_inspector_signature,requires_authenticator_signature,
     requires_company_stamp,created_at,updated_at)
  VALUES
    (v_category_id,'Shackles Thorough Examination',v_version,'draft',1,1,0,0,0,
     'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,
     UTC_TIMESTAMP(),UTC_TIMESTAMP());
  SET v_new_template=LAST_INSERT_ID();

  INSERT INTO form_fields
    (template_id,field_key,label,help_text,field_type,is_required,options_json,
     validation_json,appears_on_pdf,editable_after_approval,pdf_section,sort_order,created_at)
  VALUES
    (v_new_template,'colour_code','Colour Code','Colour code shown on the certificate.','text',1,NULL,NULL,1,0,'certificate',10,UTC_TIMESTAMP()),
    (v_new_template,'standard','Standard','Automatically sourced from the selected equipment record.','text',1,NULL,NULL,1,0,'certificate',20,UTC_TIMESTAMP()),
    (v_new_template,'reason_for_examination_code','Reason for Examination','Choose the examination reason; the certificate stores the corresponding A-E code.','select',1,'["A","B","C","D","E"]',NULL,1,0,'examination',30,UTC_TIMESTAMP()),
    (v_new_template,'pin_condition','Pin / Thread Condition','Pass/fail condition of pin and threads.','pass_fail',1,NULL,NULL,1,0,'inspection',40,UTC_TIMESTAMP()),
    (v_new_template,'body_distortion_check','Body Distortion Check','Pass/fail body and bow condition.','pass_fail',1,NULL,NULL,1,0,'inspection',50,UTC_TIMESTAMP()),
    (v_new_template,'defect_observation_sheet_attached','Defect / Observation Sheet Attached?','Whether a separate sheet accompanies the report.','select',1,'["Yes","No"]',NULL,1,0,'signoff',60,UTC_TIMESTAMP());

  INSERT INTO form_repeatable_sections
    (template_id,section_key,label,help_text,min_rows,max_rows,pdf_section,
     sort_order,created_at,updated_at)
  VALUES
    (v_new_template,'shackles_items','Shackles Examination Register',
     'The first row is sourced from the selected Equipment Register record. Add rows only when this certificate legitimately covers additional individually identified shackles.',
     1,6,'equipment_register',200,UTC_TIMESTAMP(),UTC_TIMESTAMP());
  SET v_section=LAST_INSERT_ID();

  INSERT INTO form_repeatable_columns
    (section_id,column_key,label,column_type,is_required,options_json,
     validation_json,placeholder_text,appears_on_pdf,editable_after_approval,
     sort_order,created_at,updated_at)
  VALUES
    (v_section,'identification_number','Identification Number','text',1,NULL,NULL,NULL,1,0,10,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
    (v_section,'description','Description','text',1,NULL,NULL,NULL,1,0,20,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
    (v_section,'swl_wll','WLL or SWL','text',1,NULL,NULL,NULL,1,0,30,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
    (v_section,'date_last_examined','Date of Last Thorough Examination','date',0,NULL,'{"pattern":"^(\\d{4}|\\d{4}-(0[1-9]|1[0-2])|\\d{4}-(0[1-9]|1[0-2])-([0-2]\\d|3[01]))$"}',NULL,1,0,40,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
    (v_section,'manufacturer','Manufacturer','text',1,NULL,NULL,NULL,1,0,50,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
    (v_section,'test_details','Details of Any Test','text',1,NULL,NULL,NULL,1,0,60,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
    (v_section,'status','Status','select',1,'["ND","SDR","NF","OBS"]',NULL,NULL,1,0,70,UTC_TIMESTAMP(),UTC_TIMESTAMP()),
    (v_section,'safe_to_use','Safe to Use','select',1,'["Yes","No"]',NULL,NULL,1,0,80,UTC_TIMESTAMP(),UTC_TIMESTAMP());

  SELECT COUNT(*) INTO v_count FROM form_fields
  WHERE template_id=v_new_template AND field_key IN
    ('colour_code','standard','reason_for_examination_code','pin_condition',
     'body_distortion_check','defect_observation_sheet_attached');
  IF v_count<>6 OR (SELECT COUNT(*) FROM form_fields WHERE template_id=v_new_template)<>6 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: new field verification failed';
  END IF;

  SELECT COUNT(*) INTO v_count FROM form_repeatable_sections
  WHERE id=v_section AND template_id=v_new_template
    AND section_key='shackles_items' AND min_rows=1 AND max_rows=6;
  IF v_count<>1 OR (SELECT COUNT(*) FROM form_repeatable_sections WHERE template_id=v_new_template)<>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: new section verification failed';
  END IF;

  SELECT COUNT(*) INTO v_count FROM form_repeatable_columns
  WHERE section_id=v_section AND column_key IN
    ('identification_number','description','swl_wll','date_last_examined',
     'manufacturer','test_details','status','safe_to_use');
  IF v_count<>8 OR (SELECT COUNT(*) FROM form_repeatable_columns WHERE section_id=v_section)<>8 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: new column verification failed';
  END IF;

  UPDATE form_templates SET status='archived',updated_at=UTC_TIMESTAMP()
  WHERE id=v_old_template AND category_id=v_category_id AND status='active';
  IF ROW_COUNT()<>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: old template archive failed';
  END IF;

  UPDATE form_templates SET status='active',updated_at=UTC_TIMESTAMP()
  WHERE id=v_new_template AND category_id=v_category_id AND status='draft';
  IF ROW_COUNT()<>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: new template activation failed';
  END IF;

  SELECT COUNT(*) INTO v_count FROM form_templates
  WHERE category_id=v_category_id AND status='active' AND id=v_new_template;
  IF v_count<>1 OR
     (SELECT COUNT(*) FROM form_templates WHERE category_id=v_category_id AND status='active')<>1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PHASE28: final active template verification failed';
  END IF;

  COMMIT;
  SET v_transaction=0;
  SELECT 'PHASE 28 APPLIED' phase28_status,
         v_backfilled manufacture_dates_backfilled,
         v_new_template active_shackles_template_id,
         v_version active_shackles_template_version;
END$$
DELIMITER ;

CALL migrate_phase28_partial_dates_shackles();
DROP PROCEDURE IF EXISTS migrate_phase28_partial_dates_shackles;
SELECT RELEASE_LOCK(@phase28_lock_name) AS phase28_lock_released;
