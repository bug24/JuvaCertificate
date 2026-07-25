ALTER TABLE inspection_attachments
    ADD COLUMN attachment_type ENUM('evidence', 'signature') NOT NULL DEFAULT 'evidence' AFTER uploaded_by;

CREATE INDEX idx_inspection_attachments_type
    ON inspection_attachments (inspection_id, attachment_type, created_at);
