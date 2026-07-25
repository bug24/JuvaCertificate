ALTER TABLE certification_categories
  ADD COLUMN certificate_prefix VARCHAR(40) NULL AFTER certificate_template,
  ADD COLUMN identifier_label VARCHAR(120) NOT NULL DEFAULT 'Certificate / Inspection ID' AFTER certificate_prefix,
  ADD COLUMN theme_color VARCHAR(20) NOT NULL DEFAULT '#151515' AFTER identifier_label;

UPDATE certification_categories SET
  certificate_prefix = CONCAT('JUVA-', code),
  identifier_label = 'Certificate Number',
  theme_color = CASE code
    WHEN 'MPI' THEN '#4447d7'
    WHEN 'SHK' THEN '#18794e'
    WHEN 'FLT' THEN '#f3b700'
    ELSE '#151515'
  END
WHERE certificate_prefix IS NULL;
