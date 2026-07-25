<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/form_schema.php';

require_method('GET');
$user = require_permission('inspections.view');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    api_error('Inspection id is required.', 422, ['id' => 'Required.']);
}

$inspection = fetch_inspection_for_user($id, $user, 'i.id');
if (!$inspection) {
    api_error('Inspection not found.', 404);
}

$stmt = db()->prepare('SELECT i.*, c.name AS client_name, c.short_code AS client_short_code, e.asset_code, e.name AS equipment_name, cat.name AS category_name, cat.short_code AS category_short_code, cat.certificate_prefix, cat.identifier_label, cat.validity_months, u.name AS inspector_name, src.reference AS cloned_from_reference FROM inspections i JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id JOIN users u ON u.id = i.inspector_id LEFT JOIN inspections src ON src.id = i.cloned_from_inspection_id WHERE i.id = ? LIMIT 1');
$stmt->execute([$id]);
$inspection = $stmt->fetch();
$fields = db()->prepare('SELECT f.*, v.value_text FROM form_fields f LEFT JOIN inspection_values v ON v.field_id = f.id AND v.inspection_id = ? WHERE f.template_id = ? ORDER BY f.sort_order, f.id');
$fields->execute([$id, $inspection['form_template_id']]);
$attachments = db()->prepare('SELECT id, attachment_type, file_name, mime_type, file_size, created_at FROM inspection_attachments WHERE inspection_id = ? ORDER BY created_at DESC');
$attachments->execute([$id]);
$attachmentRows = [];
foreach ($attachments->fetchAll() as $attachment) {
    $attachment['download_url'] = api_url('inspections/attachment.php?id=' . (int) $attachment['id']);
    $attachmentRows[] = $attachment;
}
$comments = db()->prepare('SELECT c.*, u.name AS user_name, r.name AS role_name FROM inspection_comments c JOIN users u ON u.id = c.user_id JOIN roles r ON r.id = u.role_id WHERE c.inspection_id = ? ORDER BY c.created_at ASC');
$comments->execute([$id]);
respond([
    'inspection' => $inspection,
    'fields' => $fields->fetchAll(),
    'sections' => fetch_repeatable_schema_for_template((int) $inspection['form_template_id']),
    'items' => fetch_inspection_items_grouped($id),
    'attachments' => $attachmentRows,
    'comments' => $comments->fetchAll(),
]);
