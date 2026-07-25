ALTER TABLE certification_categories
  ADD COLUMN template_family VARCHAR(50) NULL AFTER certificate_template,
  ADD COLUMN layout_key VARCHAR(100) NULL AFTER template_family,
  ADD COLUMN source_sample VARCHAR(190) NULL AFTER layout_key,
  ADD COLUMN schema_version SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER source_sample;

ALTER TABLE form_fields
  ADD COLUMN help_text VARCHAR(255) NULL AFTER label,
  ADD COLUMN placeholder_text VARCHAR(150) NULL AFTER help_text,
  ADD COLUMN appears_on_pdf TINYINT(1) NOT NULL DEFAULT 1 AFTER is_required,
  ADD COLUMN editable_after_approval TINYINT(1) NOT NULL DEFAULT 0 AFTER appears_on_pdf,
  ADD COLUMN pdf_section VARCHAR(80) NULL AFTER editable_after_approval,
  ADD COLUMN repeatable_group VARCHAR(80) NULL AFTER pdf_section;

CREATE TABLE IF NOT EXISTS form_repeatable_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id BIGINT UNSIGNED NOT NULL,
  section_key VARCHAR(100) NOT NULL,
  label VARCHAR(180) NOT NULL,
  help_text VARCHAR(255) NULL,
  min_rows SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  max_rows SMALLINT UNSIGNED NULL,
  pdf_section VARCHAR(80) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_repeatable_section_key (template_id, section_key),
  CONSTRAINT fk_repeatable_section_template FOREIGN KEY (template_id) REFERENCES form_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS form_repeatable_columns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id BIGINT UNSIGNED NOT NULL,
  column_key VARCHAR(100) NOT NULL,
  label VARCHAR(180) NOT NULL,
  column_type ENUM('text','textarea','number','date','select','checkbox','pass_fail') NOT NULL DEFAULT 'text',
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  options_json LONGTEXT NULL,
  validation_json LONGTEXT NULL,
  placeholder_text VARCHAR(150) NULL,
  appears_on_pdf TINYINT(1) NOT NULL DEFAULT 1,
  editable_after_approval TINYINT(1) NOT NULL DEFAULT 0,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_repeatable_column_key (section_id, column_key),
  CONSTRAINT fk_repeatable_column_section FOREIGN KEY (section_id) REFERENCES form_repeatable_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inspection_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id BIGINT UNSIGNED NOT NULL,
  section_key VARCHAR(100) NOT NULL,
  row_index SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  column_key VARCHAR(100) NOT NULL,
  value_text LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inspection_item_cell (inspection_id, section_key, row_index, column_key),
  INDEX idx_inspection_items_lookup (inspection_id, section_key, row_index),
  CONSTRAINT fk_inspection_items_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
) ENGINE=InnoDB;
