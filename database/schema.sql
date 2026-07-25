CREATE DATABASE IF NOT EXISTS juva_certify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE juva_certify;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  permissions_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NULL,
  legacy_reg_id VARCHAR(40) NULL,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL DEFAULT '',
  phone VARCHAR(40) NULL,
  qualification VARCHAR(255) NULL,
  job_title VARCHAR(160) NULL,
  professional_memberships VARCHAR(500) NULL,
  certificate_signing_role VARCHAR(160) NULL,
  signature_path VARCHAR(255) NULL,
  signature_original_name VARCHAR(255) NULL,
  signature_mime_type VARCHAR(100) NULL,
  signature_uploaded_at DATETIME NULL,
  signature_uploaded_by BIGINT UNSIGNED NULL,
  signature_is_active TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','invited','suspended','disabled') NOT NULL DEFAULT 'invited',
  email_verified_at DATETIME NULL,
  invited_at DATETIME NULL,
  last_login_at DATETIME NULL,
  sessions_revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role_id),
  INDEX idx_users_client (client_id),
  INDEX idx_users_legacy_reg_id (legacy_reg_id),
  INDEX idx_users_status (status),
  INDEX idx_users_sessions_revoked_at (sessions_revoked_at)
) ENGINE=InnoDB;

ALTER TABLE users ADD CONSTRAINT fk_user_signature_uploader FOREIGN KEY (signature_uploaded_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE certificate_branding_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  company_stamp_path VARCHAR(255) NULL,
  company_stamp_original_name VARCHAR(255) NULL,
  company_stamp_mime_type VARCHAR(100) NULL,
  company_stamp_uploaded_at DATETIME NULL,
  company_stamp_uploaded_by BIGINT UNSIGNED NULL,
  company_stamp_is_active TINYINT(1) NOT NULL DEFAULT 0,
  watermark_path VARCHAR(255) NULL,
  registered_address TEXT NULL,
  operational_address TEXT NULL,
  phone VARCHAR(100) NULL,
  email VARCHAR(190) NULL,
  website VARCHAR(190) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_branding_stamp_uploader FOREIGN KEY (company_stamp_uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE clients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_code VARCHAR(100) NOT NULL UNIQUE,
  short_code VARCHAR(20) NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  contact_person VARCHAR(160) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(50) NULL,
  address TEXT NULL,
  logo_path VARCHAR(255) NULL,
  status ENUM('active','review','inactive') NOT NULL DEFAULT 'active',
  legacy_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE users
  ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
  ADD CONSTRAINT fk_users_client FOREIGN KEY (client_id) REFERENCES clients(id);

CREATE TABLE auth_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope VARCHAR(40) NOT NULL,
  identifier_hash CHAR(64) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  failure_reason VARCHAR(80) NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_attempt_scope_identifier (scope, identifier_hash, created_at),
  INDEX idx_attempt_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE otp_challenges (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  challenge_token_hash CHAR(64) NOT NULL UNIQUE,
  code_hash VARCHAR(255) NOT NULL,
  purpose VARCHAR(40) NOT NULL DEFAULT 'login',
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  consumed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_otp_user (user_id),
  INDEX idx_otp_expiry (expires_at),
  CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE auth_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  type ENUM('invite','password_reset') NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_auth_tokens_user (user_id),
  INDEX idx_auth_tokens_type (type, expires_at),
  CONSTRAINT fk_auth_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_auth_token_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE remember_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  selector VARCHAR(32) NOT NULL UNIQUE,
  validator_hash CHAR(64) NOT NULL,
  user_agent_hash CHAR(64) NULL,
  ip_hash CHAR(64) NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_remember_user (user_id),
  INDEX idx_remember_expiry (expires_at),
  CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE equipment (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  asset_code VARCHAR(120) NOT NULL,
  name VARCHAR(200) NOT NULL,
  manufacturer VARCHAR(160) NULL,
  model VARCHAR(120) NULL,
  serial_number VARCHAR(120) NULL,
  safe_working_load VARCHAR(100) NULL,
  location VARCHAR(255) NULL,
  manufacture_date DATE NULL,
  reference_standard VARCHAR(200) NULL,
  status ENUM('active','out_of_service','retired') NOT NULL DEFAULT 'active',
  legacy_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_equipment_client_asset (client_id, asset_code),
  INDEX idx_equipment_serial (serial_number),
  CONSTRAINT fk_equipment_client FOREIGN KEY (client_id) REFERENCES clients(id)
) ENGINE=InnoDB;

CREATE TABLE certification_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  short_code VARCHAR(20) NULL UNIQUE,
  name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  validity_months SMALLINT UNSIGNED NOT NULL DEFAULT 12,
  certificate_template VARCHAR(100) NULL,
  certificate_prefix VARCHAR(40) NULL,
  identifier_label VARCHAR(120) NOT NULL DEFAULT 'Certificate / Inspection ID',
  theme_color VARCHAR(20) NOT NULL DEFAULT '#151515',
  requires_review TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('active','inactive','legacy') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE form_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(180) NOT NULL,
  version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
  show_inspector_signature TINYINT(1) NOT NULL DEFAULT 1,
  show_authenticator_signature TINYINT(1) NOT NULL DEFAULT 1,
  show_company_stamp TINYINT(1) NOT NULL DEFAULT 0,
  requires_evidence TINYINT(1) NOT NULL DEFAULT 0,
  minimum_evidence_files SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  allowed_evidence_types VARCHAR(255) NOT NULL DEFAULT 'image/jpeg,image/png,image/webp,application/pdf',
  requires_inspection_photo TINYINT(1) NOT NULL DEFAULT 0,
  requires_inspector_signature TINYINT(1) NOT NULL DEFAULT 0,
  requires_authenticator_signature TINYINT(1) NOT NULL DEFAULT 0,
  requires_company_stamp TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_form_template_version (category_id, version),
  CONSTRAINT fk_form_template_category FOREIGN KEY (category_id) REFERENCES certification_categories(id)
) ENGINE=InnoDB;

CREATE TABLE form_fields (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id BIGINT UNSIGNED NOT NULL,
  field_key VARCHAR(100) NOT NULL,
  label VARCHAR(180) NOT NULL,
  field_type ENUM('text','textarea','number','date','select','checkbox','pass_fail','photo','signature') NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  options_json LONGTEXT NULL,
  validation_json LONGTEXT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_form_field_key (template_id, field_key),
  CONSTRAINT fk_form_field_template FOREIGN KEY (template_id) REFERENCES form_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inspections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(150) NOT NULL UNIQUE,
  sequence_number INT UNSIGNED NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  equipment_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  form_template_id BIGINT UNSIGNED NOT NULL,
  inspector_id BIGINT UNSIGNED NOT NULL,
  inspection_date DATE NOT NULL,
  next_due_date DATE NULL,
  location VARCHAR(255) NULL,
  result ENUM('pending','safe','unsafe','conditional') NOT NULL DEFAULT 'pending',
  status ENUM('draft','submitted','correction','approved','issued','revoked','expired') NOT NULL DEFAULT 'draft',
  remarks TEXT NULL,
  cloned_from_inspection_id BIGINT UNSIGNED NULL,
  renewal_source_certificate_id BIGINT UNSIGNED NULL,
  legacy_id BIGINT UNSIGNED NULL,
  is_legacy TINYINT(1) NOT NULL DEFAULT 0,
  legacy_source_table VARCHAR(100) NULL,
  legacy_source_reference VARCHAR(190) NULL,
  legacy_mapping_status ENUM('complete','partial','raw_only') NOT NULL DEFAULT 'complete',
  legacy_payload_json LONGTEXT NULL,
  submitted_at DATETIME NULL,
  approved_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inspection_scope_sequence (client_id, category_id, sequence_number),
  INDEX idx_inspections_status (status),
  INDEX idx_inspections_due (next_due_date),
  INDEX idx_inspections_legacy (is_legacy, legacy_id),
  INDEX idx_inspection_clone_source (cloned_from_inspection_id),
  INDEX idx_inspection_renewal_source (renewal_source_certificate_id),
  CONSTRAINT fk_inspection_client FOREIGN KEY (client_id) REFERENCES clients(id),
  CONSTRAINT fk_inspection_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id),
  CONSTRAINT fk_inspection_category FOREIGN KEY (category_id) REFERENCES certification_categories(id),
  CONSTRAINT fk_inspection_template FOREIGN KEY (form_template_id) REFERENCES form_templates(id),
  CONSTRAINT fk_inspection_inspector FOREIGN KEY (inspector_id) REFERENCES users(id),
  CONSTRAINT fk_inspection_clone_source FOREIGN KEY (cloned_from_inspection_id) REFERENCES inspections(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE inspection_values (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  field_id BIGINT UNSIGNED NOT NULL,
  value_text LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inspection_field (inspection_id, field_id),
  CONSTRAINT fk_value_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
  CONSTRAINT fk_value_field FOREIGN KEY (field_id) REFERENCES form_fields(id)
) ENGINE=InnoDB;

CREATE TABLE inspection_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  attachment_type ENUM('evidence', 'signature') NOT NULL DEFAULT 'evidence',
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  file_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inspection_attachments_type (inspection_id, attachment_type, created_at),
  CONSTRAINT fk_attachment_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
  CONSTRAINT fk_attachment_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;


CREATE TABLE inspection_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  comment_type ENUM('comment','correction','review','status') NOT NULL DEFAULT 'comment',
  comment_text TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inspection_comments_inspection (inspection_id, created_at),
  CONSTRAINT fk_comment_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE approvals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  reviewer_id BIGINT UNSIGNED NOT NULL,
  decision ENUM('approved','rejected','correction') NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_approval_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(id),
  CONSTRAINT fk_approval_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE certificate_sequences (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  last_number INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_certificate_sequence_scope (client_id, category_id),
  CONSTRAINT fk_certificate_sequence_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_certificate_sequence_category FOREIGN KEY (category_id) REFERENCES certification_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE certificates (  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL UNIQUE,
  certificate_number VARCHAR(150) NOT NULL UNIQUE,
  verification_token CHAR(64) NOT NULL UNIQUE,
  revision SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  pdf_path VARCHAR(255) NULL,
  barcode_path VARCHAR(255) NULL,
  pdf_hash CHAR(64) NULL,
  issued_at DATE NOT NULL,
  expires_at DATE NULL,
  status ENUM('valid','expired','revoked','superseded') NOT NULL DEFAULT 'valid',
  revoked_at DATETIME NULL,
  revocation_reason TEXT NULL,
  legacy_id BIGINT UNSIGNED NULL,
  is_legacy TINYINT(1) NOT NULL DEFAULT 0,
  legacy_original_reference VARCHAR(190) NULL,
  legacy_mapping_status ENUM('complete','partial','raw_only') NOT NULL DEFAULT 'complete',
  legacy_payload_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_certificates_status (status),
  INDEX idx_certificates_expires (expires_at),
  INDEX idx_certificates_legacy (is_legacy, legacy_id),
  CONSTRAINT fk_certificate_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(id)
) ENGINE=InnoDB;


CREATE TABLE certificate_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  certificate_id BIGINT UNSIGNED NOT NULL,
  revision SMALLINT UNSIGNED NOT NULL,
  pdf_path VARCHAR(255) NOT NULL,
  barcode_path VARCHAR(255) NULL,
  pdf_hash CHAR(64) NOT NULL,
  inspector_name_snapshot VARCHAR(160) NULL,
  inspector_qualifications_snapshot VARCHAR(500) NULL,
  inspector_signature_source ENUM('inspection_upload','user_profile','none') NOT NULL DEFAULT 'none',
  inspector_signature_path_snapshot VARCHAR(255) NULL,
  authenticator_name_snapshot VARCHAR(160) NULL,
  authenticator_qualifications_snapshot VARCHAR(500) NULL,
  authenticator_signature_source ENUM('inspection_upload','user_profile','none') NOT NULL DEFAULT 'none',
  authenticator_signature_path_snapshot VARCHAR(255) NULL,
  company_stamp_path_snapshot VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_certificate_revision (certificate_id, revision),
  CONSTRAINT fk_cert_revision_certificate FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_revision_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
ALTER TABLE inspections
  ADD CONSTRAINT fk_inspection_renewal_certificate FOREIGN KEY (renewal_source_certificate_id) REFERENCES certificates(id) ON DELETE SET NULL;

CREATE TABLE verification_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  certificate_id BIGINT UNSIGNED NULL,
  searched_reference VARCHAR(190) NOT NULL,
  result_status VARCHAR(30) NOT NULL,
  ip_hash CHAR(64) NULL,
  user_agent_hash CHAR(64) NULL,
  verified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_verification_date (verified_at),
  CONSTRAINT fk_verification_certificate FOREIGN KEY (certificate_id) REFERENCES certificates(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(100) NULL,
  entity_id BIGINT UNSIGNED NULL,
  legacy_id BIGINT UNSIGNED NULL,
  metadata_json LONGTEXT NULL,
  ip_address VARCHAR(45) NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_legacy_id (legacy_id),
  INDEX idx_audit_date (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;


CREATE TABLE legacy_inspection_details (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  legacy_table VARCHAR(120) NOT NULL,
  legacy_row_id BIGINT UNSIGNED NULL,
  detail_json LONGTEXT NOT NULL,
  mapping_status ENUM('partial','raw_only') NOT NULL DEFAULT 'raw_only',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_legacy_detail_inspection (inspection_id),
  INDEX idx_legacy_detail_source (legacy_table, legacy_row_id),
  CONSTRAINT fk_legacy_detail_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE legacy_migration_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_name VARCHAR(190) NOT NULL,
  notes TEXT NULL,
  clients_imported INT UNSIGNED NOT NULL DEFAULT 0,
  equipment_imported INT UNSIGNED NOT NULL DEFAULT 0,
  users_imported INT UNSIGNED NOT NULL DEFAULT 0,
  inspections_imported INT UNSIGNED NOT NULL DEFAULT 0,
  certificates_imported INT UNSIGNED NOT NULL DEFAULT 0,
  details_imported INT UNSIGNED NOT NULL DEFAULT 0,
  audit_imported INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT INTO roles (name, slug, permissions_json) VALUES
('Super Administrator', 'super-admin', '["*"]'),
('Operations Admin', 'operations-admin', '["dashboard.view","clients.view","clients.manage","equipment.view","equipment.manage","categories.view","categories.create","categories.edit","categories.change_status","categories.manage","inspections.view","inspections.create","inspections.edit","certificates.view","verification.view","reports.view","audit.view","users.manage","roles.view"]'),
('Inspector', 'inspector', '["dashboard.view","clients.view","equipment.view","categories.view","inspections.view","inspections.create","inspections.edit","certificates.view","verification.view"]'),
('Reviewer / Approver', 'reviewer', '["dashboard.view","clients.view","equipment.view","categories.view","inspections.view","inspections.review","certificates.view","certificates.issue","verification.view"]'),
('Client User', 'client', '["certificates.own.view","certificates.own.download","verification.view"]');

INSERT INTO certification_categories
  (code, short_code, name, description, validity_months, certificate_template, certificate_prefix, identifier_label, theme_color, requires_review, status)
VALUES
  ('CCU', 'CCU', 'CCU Thorough Examination', 'Inspection and certification of cargo carrying units and associated lifting sets.', 6, 'Form 6A', 'JUVA-CCU', 'Certificate Number', '#151515', 1, 'active'),
  ('MPI', 'MPI', 'Magnetic Particle Inspection', 'Magnetic particle and general non-destructive examination.', 12, 'NDE Report', 'JUVA-MPI', 'Report Number', '#4447d7', 1, 'active'),
  ('SHK', 'SHK', 'Shackle Inspection', 'Thorough examination of shackles and lifting accessories.', 6, 'Form 7', 'JUVA-SHK', 'Certificate Number', '#18794e', 1, 'active'),
  ('FLT', 'FLT', 'Forklift Inspection', 'Visual, functional and safety inspection of forklift equipment.', 12, 'Plant Certificate', 'JUVA-FLT', 'Certificate Number', '#f3b700', 1, 'active');
INSERT INTO clients (registration_code, short_code, name, contact_person, email, phone, address, status) VALUES
  ('JV-2020-04706', 'AOL', 'AVEON Offshore Limited', 'Operations Desk', 'operations@aveonoffshore.example', '+234 84 232 794', 'Port Harcourt, Rivers State', 'active'),
  ('JV-2020-05046', 'PON', 'PONTICELLI Nigeria Limited', 'QA/QC Department', 'qaqc@ponticelli.example', '+234 84 235 240', 'Port Harcourt, Rivers State', 'active'),
  ('JV-2019-42421', 'SPIE', 'SPIE Nigeria Limited', 'Marine Base', 'marine@spie.example', '+234 84 304 734', 'Port Harcourt, Rivers State', 'active');

INSERT INTO equipment (client_id, asset_code, name, serial_number, safe_working_load, location, reference_standard, status)
SELECT id, 'AOL-CCU-014', 'CCU Basket', 'AOL 1408-145', '17 T', 'Operational Yard', 'LOLER / BS EN 12079', 'active' FROM clients WHERE registration_code = 'JV-2020-04706';
INSERT INTO equipment (client_id, asset_code, name, serial_number, safe_working_load, location, reference_standard, status)
SELECT id, 'PNL-SHK-366', 'Safety Pin Bow Shackle', 'NES 366', '25 T', 'Client Site', 'BS EN 13889', 'active' FROM clients WHERE registration_code = 'JV-2020-05046';
INSERT INTO equipment (client_id, asset_code, name, serial_number, safe_working_load, location, reference_standard, status)
SELECT id, 'SPIE-FLT-019', 'Forklift', 'MHA181 XA', '5 T', 'Marine Base', 'OEM / HSE', 'active' FROM clients WHERE registration_code = 'JV-2019-42421';

INSERT INTO form_templates (category_id, name, version, status)
SELECT id, CONCAT(name, ' Form'), 1, 'active' FROM certification_categories;

INSERT INTO form_fields (template_id, field_key, label, field_type, is_required, sort_order)
SELECT t.id, seed.field_key, seed.label, seed.field_type, seed.is_required, seed.sort_order
FROM form_templates t
JOIN (
  SELECT 'client_name' AS field_key, 'Client Name' AS label, 'text' AS field_type, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'equipment_id', 'Equipment ID / Tag Number', 'text', 1, 20
  UNION ALL SELECT 'inspection_date', 'Inspection Date', 'date', 1, 30
  UNION ALL SELECT 'swl_capacity', 'SWL / Capacity', 'text', 0, 40
  UNION ALL SELECT 'remarks', 'Remarks', 'textarea', 0, 50
) seed;











CREATE TABLE IF NOT EXISTS email_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  certificate_id BIGINT UNSIGNED NOT NULL,
  revision SMALLINT UNSIGNED NOT NULL,
  event_type VARCHAR(40) NOT NULL,
  recipient_email VARCHAR(190) NOT NULL,
  recipient_name VARCHAR(190) NULL,
  status ENUM('pending','sending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 5,
  last_error VARCHAR(500) NULL,
  next_attempt_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email_notification_delivery (certificate_id, revision, event_type, recipient_email),
  INDEX idx_email_notification_queue (status, next_attempt_at),
  INDEX idx_email_notification_certificate (certificate_id, revision),
  CONSTRAINT fk_email_notification_certificate FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

