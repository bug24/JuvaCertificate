START TRANSACTION;
SET @ccu_template_id := (SELECT t.id FROM form_templates t JOIN certification_categories c ON c.id=t.category_id WHERE c.template_family='ccu_visual' AND t.status='active' ORDER BY t.version DESC LIMIT 1);
UPDATE inspections i JOIN inspection_values v ON v.inspection_id=i.id JOIN form_fields f ON f.id=v.field_id
SET i.next_due_date=STR_TO_DATE(v.value_text,'%Y-%m-%d')
WHERE f.template_id=@ccu_template_id AND f.field_key IN ('next_due_date','inspection_due_date') AND i.next_due_date IS NULL AND v.value_text REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$';
DELETE v FROM inspection_values v JOIN form_fields f ON f.id=v.field_id WHERE f.template_id=@ccu_template_id AND f.field_key IN ('date_of_examination','next_due_date','inspection_due_date');
DELETE FROM form_fields WHERE template_id=@ccu_template_id AND field_key IN ('date_of_examination','next_due_date','inspection_due_date');
UPDATE form_fields SET label='Certificate Client Name Override',is_required=0 WHERE template_id=@ccu_template_id AND field_key='client_name';
UPDATE form_fields SET label='Certificate Client Address Override',is_required=0 WHERE template_id=@ccu_template_id AND field_key='client_address';
UPDATE form_fields SET label='Inspector Name Override',is_required=0 WHERE template_id=@ccu_template_id AND field_key='inspector_name';
COMMIT;
