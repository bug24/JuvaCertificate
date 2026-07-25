START TRANSACTION;
SET @ccu_template_id := (SELECT t.id FROM form_templates t JOIN certification_categories c ON c.id=t.category_id WHERE c.template_family='ccu_visual' AND t.status='active' ORDER BY t.version DESC LIMIT 1);
SET @canonical_due_id := (SELECT id FROM form_fields WHERE template_id=@ccu_template_id AND field_key='next_due_date' LIMIT 1);
SET @duplicate_due_id := (SELECT id FROM form_fields WHERE template_id=@ccu_template_id AND field_key='inspection_due_date' LIMIT 1);
INSERT INTO inspection_values (inspection_id,field_id,value_text,created_at,updated_at)
SELECT d.inspection_id,@canonical_due_id,d.value_text,UTC_TIMESTAMP(),UTC_TIMESTAMP()
FROM inspection_values d LEFT JOIN inspection_values c ON c.inspection_id=d.inspection_id AND c.field_id=@canonical_due_id
WHERE d.field_id=@duplicate_due_id AND NULLIF(TRIM(d.value_text),'') IS NOT NULL AND (c.id IS NULL OR NULLIF(TRIM(c.value_text),'') IS NULL)
ON DUPLICATE KEY UPDATE value_text=VALUES(value_text),updated_at=VALUES(updated_at);
DELETE FROM inspection_values WHERE field_id=@duplicate_due_id;
DELETE FROM form_fields WHERE id=@duplicate_due_id;
COMMIT;
