<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/form_schema.php';

require_methods(['GET', 'POST']);
$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
if ($categoryId <= 0) {
    api_error('Category id is required.', 422, ['category_id' => 'Required.']);
}

$templateStmt = db()->prepare('SELECT * FROM form_templates WHERE category_id = ? ORDER BY version DESC LIMIT 1');
$templateStmt->execute([$categoryId]);
$template = $templateStmt->fetch();
if (!$template) {
    respond(['sections' => []]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_permission('categories.view');
    respond(['sections' => fetch_repeatable_schema_for_template((int) $template['id'])]);
}

$user = require_permission('categories.manage');
require_csrf();
$input = json_input();
$errors = [];
$payload = normalize_repeatable_section_payload($input, $errors);
if ($errors || !$payload) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$dupe = db()->prepare('SELECT id FROM form_repeatable_sections WHERE template_id = ? AND section_key = ? LIMIT 1');
$dupe->execute([(int) $template['id'], $payload['section_key']]);
if ($dupe->fetch()) {
    api_error('Please correct the highlighted fields.', 422, ['section_key' => 'This section key already exists on the active template.']);
}

$stmt = db()->prepare('INSERT INTO form_repeatable_sections (template_id, section_key, label, help_text, min_rows, max_rows, pdf_section, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    (int) $template['id'],
    $payload['section_key'],
    $payload['label'],
    $payload['help_text'],
    $payload['min_rows'],
    $payload['max_rows'],
    $payload['pdf_section'],
    $payload['sort_order'],
    now_sql(),
    now_sql(),
]);
$sectionId = (int) db()->lastInsertId();
audit_log((int) $user['id'], 'repeatable_sections.created', 'repeatable_section', $sectionId, ['category_id' => $categoryId]);

$fetch = db()->prepare('SELECT * FROM form_repeatable_sections WHERE id = ?');
$fetch->execute([$sectionId]);
$section = $fetch->fetch();
$section['columns'] = [];
respond(['section' => $section], 201);
