ALTER TABLE form_templates
  ADD COLUMN show_inspector_signature TINYINT(1) NOT NULL DEFAULT 1 AFTER status,
  ADD COLUMN show_authenticator_signature TINYINT(1) NOT NULL DEFAULT 1 AFTER show_inspector_signature,
  ADD COLUMN show_company_stamp TINYINT(1) NOT NULL DEFAULT 0 AFTER show_authenticator_signature,
  ADD COLUMN requires_evidence TINYINT(1) NOT NULL DEFAULT 0 AFTER show_company_stamp,
  ADD COLUMN minimum_evidence_files SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER requires_evidence,
  ADD COLUMN allowed_evidence_types VARCHAR(255) NOT NULL DEFAULT 'image/jpeg,image/png,image/webp,application/pdf' AFTER minimum_evidence_files,
  ADD COLUMN requires_inspection_photo TINYINT(1) NOT NULL DEFAULT 0 AFTER allowed_evidence_types,
  ADD COLUMN requires_inspector_signature TINYINT(1) NOT NULL DEFAULT 0 AFTER requires_inspection_photo,
  ADD COLUMN requires_authenticator_signature TINYINT(1) NOT NULL DEFAULT 0 AFTER requires_inspector_signature,
  ADD COLUMN requires_company_stamp TINYINT(1) NOT NULL DEFAULT 0 AFTER requires_authenticator_signature;

ALTER TABLE users
  ADD COLUMN job_title VARCHAR(160) NULL AFTER qualification,
  ADD COLUMN professional_memberships VARCHAR(500) NULL AFTER job_title,
  ADD COLUMN certificate_signing_role VARCHAR(160) NULL AFTER professional_memberships,
  ADD COLUMN signature_path VARCHAR(255) NULL AFTER certificate_signing_role,
  ADD COLUMN signature_original_name VARCHAR(255) NULL AFTER signature_path,
  ADD COLUMN signature_mime_type VARCHAR(100) NULL AFTER signature_original_name,
  ADD COLUMN signature_uploaded_at DATETIME NULL AFTER signature_mime_type,
  ADD COLUMN signature_uploaded_by BIGINT UNSIGNED NULL AFTER signature_uploaded_at,
  ADD COLUMN signature_is_active TINYINT(1) NOT NULL DEFAULT 0 AFTER signature_uploaded_by,
  ADD CONSTRAINT fk_user_signature_uploader FOREIGN KEY (signature_uploaded_by) REFERENCES users(id) ON DELETE SET NULL;

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

INSERT INTO certificate_branding_settings (id, registered_address, operational_address, phone, email, website)
VALUES (1, '52 Rumuolumeni Road, Port Harcourt, Rivers State', '127 Trans Amadi, Port Harcourt, Opposite Schlumberger Nig. Ltd.', '+234 806 516 4945 / +234 706 961 2375', 'juvaoil@gmail.com', 'www.juvaoil.com');

ALTER TABLE certificate_revisions
  ADD COLUMN inspector_name_snapshot VARCHAR(160) NULL AFTER pdf_hash,
  ADD COLUMN inspector_qualifications_snapshot VARCHAR(500) NULL AFTER inspector_name_snapshot,
  ADD COLUMN inspector_signature_source ENUM('inspection_upload','user_profile','none') NOT NULL DEFAULT 'none' AFTER inspector_qualifications_snapshot,
  ADD COLUMN inspector_signature_path_snapshot VARCHAR(255) NULL AFTER inspector_signature_source,
  ADD COLUMN authenticator_name_snapshot VARCHAR(160) NULL AFTER inspector_signature_path_snapshot,
  ADD COLUMN authenticator_qualifications_snapshot VARCHAR(500) NULL AFTER authenticator_name_snapshot,
  ADD COLUMN authenticator_signature_source ENUM('inspection_upload','user_profile','none') NOT NULL DEFAULT 'none' AFTER authenticator_qualifications_snapshot,
  ADD COLUMN authenticator_signature_path_snapshot VARCHAR(255) NULL AFTER authenticator_signature_source,
  ADD COLUMN company_stamp_path_snapshot VARCHAR(255) NULL AFTER authenticator_signature_path_snapshot;
