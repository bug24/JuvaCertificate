-- Phase 10 client/category scoped serial numbering and renewal cloning.

ALTER TABLE clients
  ADD COLUMN short_code VARCHAR(20) NULL AFTER registration_code,
  ADD UNIQUE KEY uq_clients_short_code (short_code);

ALTER TABLE certification_categories
  ADD COLUMN short_code VARCHAR(20) NULL AFTER code,
  ADD UNIQUE KEY uq_certification_categories_short_code (short_code);

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

ALTER TABLE inspections
  ADD COLUMN sequence_number INT UNSIGNED NULL AFTER reference,
  ADD COLUMN cloned_from_inspection_id BIGINT UNSIGNED NULL AFTER remarks,
  ADD COLUMN renewal_source_certificate_id BIGINT UNSIGNED NULL AFTER cloned_from_inspection_id,
  ADD UNIQUE KEY uq_inspection_scope_sequence (client_id, category_id, sequence_number),
  ADD INDEX idx_inspection_clone_source (cloned_from_inspection_id),
  ADD INDEX idx_inspection_renewal_source (renewal_source_certificate_id),
  ADD CONSTRAINT fk_inspection_clone_source FOREIGN KEY (cloned_from_inspection_id) REFERENCES inspections(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_inspection_renewal_certificate FOREIGN KEY (renewal_source_certificate_id) REFERENCES certificates(id) ON DELETE SET NULL;

UPDATE certification_categories
SET short_code = CASE
  WHEN short_code IS NOT NULL AND short_code <> '' THEN short_code
  WHEN UPPER(name) LIKE '%WEB%SLING%' THEN 'WEBSLNG'
  WHEN UPPER(name) LIKE '%BUMPER%SHOCK%' THEN 'BUMSHK'
  ELSE UPPER(REPLACE(REPLACE(code, '-', ''), '_', ''))
END
WHERE short_code IS NULL OR short_code = '';

UPDATE clients
SET short_code = CASE
  WHEN short_code IS NOT NULL AND short_code <> '' THEN short_code
  WHEN UPPER(name) = 'AVEON' THEN 'AVN'
  WHEN registration_code = 'JV-2020-04706' THEN 'AOL'
  WHEN registration_code = 'JV-201912294706' THEN 'AOFL'
  WHEN UPPER(name) LIKE 'JUKOSO GLOBAL SERVICES' THEN 'JGS'
  WHEN registration_code = 'JV-2020-05046' THEN 'PON'
  WHEN registration_code = 'JV-202003055046' THEN 'PNL'
  WHEN registration_code = 'JV-2019-42421' THEN 'SPIE'
  WHEN registration_code = 'JV-201912294241' THEN 'SPIE2'
  WHEN UPPER(name) LIKE '%NIGER DELTA MARINE LOGISTICS%' THEN 'NDML'
  WHEN UPPER(name) LIKE '%TRANS AMADI LIFT SERVICES%' THEN 'TALS'
  WHEN UPPER(name) LIKE '%OFFSHORE SAFETY SUPPORT%' THEN 'OSS'
  WHEN UPPER(name) LIKE '%LEGACY UNASSIGNED%' THEN 'LEGACY'
  WHEN UPPER(name) LIKE '%BUILDER RESOURCES%' THEN 'BLDR'
  WHEN UPPER(name) LIKE '%TOPLINE%' THEN 'TOPL'
  ELSE CONCAT('CL', id)
END
WHERE short_code IS NULL OR short_code = '';

