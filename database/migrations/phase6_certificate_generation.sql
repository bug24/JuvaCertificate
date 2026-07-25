ALTER TABLE certificates
  ADD COLUMN barcode_path VARCHAR(255) NULL AFTER pdf_path;

CREATE TABLE certificate_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  certificate_id BIGINT UNSIGNED NOT NULL,
  revision SMALLINT UNSIGNED NOT NULL,
  pdf_path VARCHAR(255) NOT NULL,
  barcode_path VARCHAR(255) NULL,
  pdf_hash CHAR(64) NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_certificate_revision (certificate_id, revision),
  CONSTRAINT fk_cert_revision_certificate FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_revision_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
