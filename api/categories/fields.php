<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/form_field_helpers.php';
require_once __DIR__ . '/../lib/form_schema.php';

function validate_field_payload(array $input): array
{
    $errors = [];
    $fieldKey = strtolower(normalize_text_input($input['field_key'] ?? ''));
    $label = normalize_text_input($input['label'] ?? '');
    $helpText = array_key_exists('help_text', $input) ? normalize_text_input((string) $input['help_text']) : null;
    $placeholder = array_key_exists('placeholder_text', $input) ? normalize_text_input((string) $input['placeholder_text']) : null;
    $fieldType = isset($input['field_type']) ? (string) $input['field_type'] : '';
    $allowed = ['text', 'textarea', 'number', 'date', 'select', 'checkbox', 'pass_fail', 'photo', 'signature'];
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : 100;
    $pdfSection = array_key_exists('pdf_section', $input) ? normalize_text_input((string) $input['pdf_section']) : null;
    $repeatableGroup = array_key_exists('repeatable_group', $input) ? strtolower(normalize_text_input((string) $input['repeatable_group'])) : null;

    if ($fieldKey === '') {
        $errors['field_key'] = 'This field is required.';
    } elseif (!preg_match('/^[a-z][a-z0-9_]{1,99}$/', $fieldKey)) {
        $errors['field_key'] = 'Use lowercase letters, numbers and underscores only.';
    }

    if ($label === '' || text_length($label) > 180 || contains_markup($label)) {
        $errors['label'] = 'Use plain text up to 180 characters.';
    }
    if ($helpText !== null && ($helpText === '' || text_length($helpText) > 255 || contains_markup($helpText))) {
        $errors['help_text'] = 'Use plain text up to 255 characters.';
    }
    if ($placeholder !== null && ($placeholder === '' || text_length($placeholder) > 150 || contains_markup($placeholder))) {
        $errors['placeholder_text'] = 'Use plain text up to 150 characters.';
    }

    if (!in_array($fieldType, $allowed, true)) {
        $errors['field_type'] = 'Invalid field type.';
    }
    if ($sortOrder < 0 || $sortOrder > 32000) {
        $errors['sort_order'] = 'Sort order must be between 0 and 32000.';
    }
    if ($pdfSection !== null && ($pdfSection === '' || text_length($pdfSection) > 80 || contains_markup($pdfSection))) {
        $errors['pdf_section'] = 'Use plain text up to 80 characters.';
    }
    if ($repeatableGroup !== null && ($repeatableGroup === '' || !preg_match('/^[a-z][a-z0-9_]{1,99}$/', $repeatableGroup))) {
        $errors['repeatable_group'] = 'Use lowercase letters, numbers and underscores only.';
    }

    $optionsJson = normalize_field_options($fieldType, $input['options'] ?? null, $errors);
    $validationJson = normalize_field_validation($input['validation'] ?? null, $errors);

    return [$errors, [
        'field_key' => preg_replace('/[^a-z0-9_]/', '_', $fieldKey),
        'label' => $label,
        'help_text' => $helpText ?: null,
        'placeholder_text' => $placeholder ?: null,
        'field_type' => $fieldType,
        'is_required' => !empty($input['is_required']) ? 1 : 0,
        'options_json' => $optionsJson,
        'validation_json' => $validationJson,
        'appears_on_pdf' => !array_key_exists('appears_on_pdf', $input) || !empty($input['appears_on_pdf']) ? 1 : 0,
        'editable_after_approval' => !empty($input['editable_after_approval']) ? 1 : 0,
        'pdf_section' => $pdfSection ?: null,
        'repeatable_group' => $repeatableGroup ?: null,
        'sort_order' => $sortOrder,
    ]];
}

require_methods(['GET', 'POST']);
$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
if ($categoryId <= 0) {
    api_error('Category id is required.', 422, ['category_id' => 'Required.']);
}

$templateStmt = db()->prepare('SELECT * FROM form_templates WHERE category_id = ? ORDER BY version DESC LIMIT 1');
$templateStmt->execute([$categoryId]);
$template = $templateStmt->fetch();
if (!$template) {
    respond(['template' => null, 'fields' => [], 'sections' => []]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_permission('categories.view');
    $fieldsStmt = db()->prepare('SELECT * FROM form_fields WHERE template_id = ? ORDER BY sort_order, id');
    $fieldsStmt->execute([(int) $template['id']]);
    respond([
        'template' => $template,
        'fields' => $fieldsStmt->fetchAll(),
        'sections' => fetch_repeatable_schema_for_template((int) $template['id']),
    ]);
}

$user = require_permission('categories.manage');
require_csrf();
$input = json_input();
[$errors, $payload] = validate_field_payload($input);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$dupe = db()->prepare('SELECT id FROM form_fields WHERE template_id = ? AND field_key = ? LIMIT 1');
$dupe->execute([(int) $template['id'], $payload['field_key']]);
if ($dupe->fetch()) {
    api_error('Please correct the highlighted fields.', 422, ['field_key' => 'This field key already exists on the active template.']);
}

$stmt = db()->prepare('INSERT INTO form_fields (template_id, field_key, label, help_text, placeholder_text, field_type, is_required, options_json, validation_json, appears_on_pdf, editable_after_approval, pdf_section, repeatable_group, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    (int) $template['id'],
    $payload['field_key'],
    $payload['label'],
    $payload['help_text'],
    $payload['placeholder_text'],
    $payload['field_type'],
    $payload['is_required'],
    $payload['options_json'],
    $payload['validation_json'],
    $payload['appears_on_pdf'],
    $payload['editable_after_approval'],
    $payload['pdf_section'],
    $payload['repeatable_group'],
    $payload['sort_order'],
    now_sql(),
]);
$fieldId = (int) db()->lastInsertId();
audit_log((int) $user['id'], 'form_fields.created', 'form_field', $fieldId, ['category_id' => $categoryId]);
$fetch = db()->prepare('SELECT * FROM form_fields WHERE id = ?');
$fetch->execute([$fieldId]);
respond(['field' => $fetch->fetch()], 201);
