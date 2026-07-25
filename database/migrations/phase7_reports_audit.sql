ALTER TABLE audit_logs
  ADD COLUMN ip_address VARCHAR(45) NULL AFTER metadata_json;

UPDATE roles
SET permissions_json = REPLACE(permissions_json, '"verification.view","audit.view"', '"verification.view","reports.view","audit.view"')
WHERE slug IN ('operations-admin', 'super-admin');
