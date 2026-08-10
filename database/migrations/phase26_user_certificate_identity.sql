-- Batch 1: reusable certificate identity selection. Additive and safe for existing records.
ALTER TABLE inspections
  ADD COLUMN authenticator_id BIGINT UNSIGNED NULL AFTER inspector_id,
  ADD INDEX idx_inspections_authenticator (authenticator_id),
  ADD CONSTRAINT fk_inspection_authenticator FOREIGN KEY (authenticator_id) REFERENCES users(id) ON DELETE SET NULL;
