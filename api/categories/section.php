<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/form_schema.php';
require_once __DIR__ . '/../lib/form_field_helpers.php';

require_methods(['PATCH', 'POST']);
$input = json_input();
$sectionId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($input['id']) ? (int) $input['id'] : 0);
if ($sectionId <= 0) {
    api_error('Section id is required.', 422, ['id' => 'Required.']);
}

$user = require_permission('categories.manage');
require_csrf();

$sectionStmt = db()->prepare('SELECT s.*, t.category_id FROM form_repeatable_sections s JOIN form_templates t ON t.id = s.template_id WHERE s.id = ? LIMIT 1');
$sectionStmt->execute([$sectionId]);
$section = $sectionStmt->fetch();
if (!$section) {
    api_error('Section not found.', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action']) && $input['action'] === 'delete') {
    db()->prepare('DELETE FROM form_repeatable_sections WHERE id = ?')->execute([$sectionId]);
    audit_log((int) $user['id'], 'repeatable_sections.deleted', 'repeatable_section', $sectionId, ['category_id' => (int) $section['category_id']]);
    respond(['message' => 'Section deleted.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action']) && $input['action'] === 'add_column') {
    $errors = [];
    $payload = normalize_repeatable_column_payload($input, $errors);
    if ($errors || !$payload) {
        api_error('Please correct the highlighted fields.', 422, $errors);
    }
    $dupe = db()->prepare('SELECT id FROM form_repeatable_columns WHERE section_id = ? AND column_key = ? LIMIT 1');
    $dupe->execute([$sectionId, $payload['column_key']]);
    if ($dupe->fetch()) {
        api_error('Please correct the highlighted fields.', 422, ['column_key' => 'This column key already exists on the section.']);
    }
    $insert = db()->prepare('INSERT INTO form_repeatable_columns (section_id, column_key, label, column_type, is_required, options_json, validation_json, placeholder_text, appears_on_pdf, editable_after_approval, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([
        $sectionId,
        $payload['column_key'],
        $payload['label'],
        $payload['column_type'],
        $payload['is_required'],
        $payload['options_json'],
        $payload['validation_json'],
        $payload['placeholder_text'],
        $payload['appears_on_pdf'],
        $payload['editable_after_approval'],
        $payload['sort_order'],
        now_sql(),
        now_sql(),
    ]);
    $columnId = (int) db()->lastInsertId();
    audit_log((int) $user['id'], 'repeatable_columns.created', 'repeatable_column', $columnId, ['section_id' => $sectionId, 'category_id' => (int) $section['category_id']]);
    $fetch = db()->prepare('SELECT * FROM form_repeatable_columns WHERE id = ?');
    $fetch->execute([$columnId]);
    respond(['column' => $fetch->fetch()], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action']) && $input['action'] === 'delete_column') {
    $columnId = isset($input['column_id']) ? (int) $input['column_id'] : 0;
    if ($columnId <= 0) {
        api_error('Column id is required.', 422, ['column_id' => 'Required.']);
    }
    $columnStmt = db()->prepare('SELECT id FROM form_repeatable_columns WHERE id = ? AND section_id = ? LIMIT 1');
    $columnStmt->execute([$columnId, $sectionId]);
    if (!$columnStmt->fetch()) {
        api_error('Column not found.', 404);
    }
    db()->prepare('DELETE FROM form_repeatable_columns WHERE id = ?')->execute([$columnId]);
    audit_log((int) $user['id'], 'repeatable_columns.deleted', 'repeatable_column', $columnId, ['section_id' => $sectionId, 'category_id' => (int) $section['category_id']]);
    respond(['message' => 'Column deleted.']);
}

$errors = [];
$payload = normalize_repeatable_section_payload(array_merge($section, $input), $errors);
if ($errors || !$payload) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$dupe = db()->prepare('SELECT id FROM form_repeatable_sections WHERE template_id = ? AND section_key = ? AND id <> ? LIMIT 1');
$dupe->execute([(int) $section['template_id'], $payload['section_key'], $sectionId]);
if ($dupe->fetch()) {
    api_error('Please correct the highlighted fields.', 422, ['section_key' => 'This section key already exists on the active template.']);
}

$update = db()->prepare('UPDATE form_repeatable_sections SET section_key = ?, label = ?, help_text = ?, min_rows = ?, max_rows = ?, pdf_section = ?, sort_order = ?, updated_at = ? WHERE id = ?');
$update->execute([
    $payload['section_key'],
    $payload['label'],
    $payload['help_text'],
    $payload['min_rows'],
    $payload['max_rows'],
    $payload['pdf_section'],
    $payload['sort_order'],
    now_sql(),
    $sectionId,
]);
audit_log((int) $user['id'], 'repeatable_sections.updated', 'repeatable_section', $sectionId, ['category_id' => (int) $section['category_id']]);

$fetch = db()->prepare('SELECT * FROM form_repeatable_sections WHERE id = ?');
$fetch->execute([$sectionId]);
$updatedSection = $fetch->fetch();
$columnFetch = db()->prepare('SELECT * FROM form_repeatable_columns WHERE section_id = ? ORDER BY sort_order, id');
$columnFetch->execute([$sectionId]);
$updatedSection['columns'] = $columnFetch->fetchAll();
respond(['section' => $updatedSection]);

