<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/form_field_helpers.php';

function validate_field_payload(array $input, array $field): array
{
    $errors = [];
    $fieldKey = array_key_exists('field_key', $input) ? strtolower(normalize_text_input((string) $input['field_key'])) : (string) $field['field_key'];
    $label = array_key_exists('label', $input) ? normalize_text_input((string) $input['label']) : (string) $field['label'];
    $helpText = array_key_exists('help_text', $input) ? normalize_text_input((string) $input['help_text']) : ($field['help_text'] ?? null);
    $placeholder = array_key_exists('placeholder_text', $input) ? normalize_text_input((string) $input['placeholder_text']) : ($field['placeholder_text'] ?? null);
    $fieldType = isset($input['field_type']) ? (string) $input['field_type'] : (string) $field['field_type'];
    $allowed = ['text', 'textarea', 'number', 'date', 'select', 'checkbox', 'pass_fail', 'photo', 'signature'];
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : (int) $field['sort_order'];
    $pdfSection = array_key_exists('pdf_section', $input) ? normalize_text_input((string) $input['pdf_section']) : ($field['pdf_section'] ?? null);
    $repeatableGroup = array_key_exists('repeatable_group', $input) ? strtolower(normalize_text_input((string) $input['repeatable_group'])) : ($field['repeatable_group'] ?? null);

    if ($fieldKey === '' || !preg_match('/^[a-z][a-z0-9_]{1,99}$/', $fieldKey)) {
        $errors['field_key'] = 'Use lowercase letters, numbers and underscores only.';
    }
    if ($label === '' || text_length($label) > 180 || contains_markup($label)) {
        $errors['label'] = 'Use plain text up to 180 characters.';
    }
    if ($helpText !== null && ($helpText === '' || text_length((string) $helpText) > 255 || contains_markup((string) $helpText))) {
        $errors['help_text'] = 'Use plain text up to 255 characters.';
    }
    if ($placeholder !== null && ($placeholder === '' || text_length((string) $placeholder) > 150 || contains_markup((string) $placeholder))) {
        $errors['placeholder_text'] = 'Use plain text up to 150 characters.';
    }
    if (!in_array($fieldType, $allowed, true)) {
        $errors['field_type'] = 'Invalid field type.';
    }
    if ($sortOrder < 0 || $sortOrder > 32000) {
        $errors['sort_order'] = 'Sort order must be between 0 and 32000.';
    }
    if ($pdfSection !== null && ($pdfSection === '' || text_length((string) $pdfSection) > 80 || contains_markup((string) $pdfSection))) {
        $errors['pdf_section'] = 'Use plain text up to 80 characters.';
    }
    if ($repeatableGroup !== null && ($repeatableGroup === '' || !preg_match('/^[a-z][a-z0-9_]{1,99}$/', (string) $repeatableGroup))) {
        $errors['repeatable_group'] = 'Use lowercase letters, numbers and underscores only.';
    }

    $optionsSource = array_key_exists('options', $input) ? $input['options'] : json_decode((string) ($field['options_json'] ?? ''), true);
    $validationSource = array_key_exists('validation', $input) ? $input['validation'] : json_decode((string) ($field['validation_json'] ?? ''), true);
    $optionsJson = normalize_field_options($fieldType, $optionsSource, $errors);
    $validationJson = normalize_field_validation($validationSource, $errors);

    return [$errors, [
        'field_key' => preg_replace('/[^a-z0-9_]/', '_', $fieldKey),
        'label' => $label,
        'help_text' => $helpText ?: null,
        'placeholder_text' => $placeholder ?: null,
        'field_type' => $fieldType,
        'is_required' => array_key_exists('is_required', $input) ? (!empty($input['is_required']) ? 1 : 0) : (int) $field['is_required'],
        'options_json' => $optionsJson,
        'validation_json' => $validationJson,
        'appears_on_pdf' => array_key_exists('appears_on_pdf', $input) ? (!empty($input['appears_on_pdf']) ? 1 : 0) : (int) ($field['appears_on_pdf'] ?? 1),
        'editable_after_approval' => array_key_exists('editable_after_approval', $input) ? (!empty($input['editable_after_approval']) ? 1 : 0) : (int) ($field['editable_after_approval'] ?? 0),
        'pdf_section' => $pdfSection ?: null,
        'repeatable_group' => $repeatableGroup ?: null,
        'sort_order' => $sortOrder,
    ]];
}

require_methods(['PATCH', 'POST']);
$input = json_input();
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($input['id']) ? (int) $input['id'] : 0);
if ($id <= 0) {
    api_error('Field id is required.', 422, ['id' => 'Required.']);
}

$user = require_permission('categories.manage');
require_csrf();
$stmt = db()->prepare('SELECT f.*, t.category_id FROM form_fields f JOIN form_templates t ON t.id = f.template_id WHERE f.id = ? LIMIT 1');
$stmt->execute([$id]);
$field = $stmt->fetch();
if (!$field) {
    api_error('Field not found.', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($input['action'] ?? '') === 'delete') {
    db()->prepare('DELETE FROM form_fields WHERE id = ?')->execute([$id]);
    audit_log((int) $user['id'], 'form_fields.deleted', 'form_field', $id, ['category_id' => (int) $field['category_id']]);
    respond(['message' => 'Field deleted.']);
}

[$errors, $payload] = validate_field_payload($input, $field);
if (!$errors) {
    $dupe = db()->prepare('SELECT id FROM form_fields WHERE template_id = ? AND field_key = ? AND id <> ? LIMIT 1');
    $dupe->execute([(int) $field['template_id'], $payload['field_key'], $id]);
    if ($dupe->fetch()) {
        $errors['field_key'] = 'This field key already exists on the active template.';
    }
}
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$update = db()->prepare('UPDATE form_fields SET field_key = ?, label = ?, help_text = ?, placeholder_text = ?, field_type = ?, is_required = ?, options_json = ?, validation_json = ?, appears_on_pdf = ?, editable_after_approval = ?, pdf_section = ?, repeatable_group = ?, sort_order = ? WHERE id = ?');
$update->execute([
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
    $id,
]);
audit_log((int) $user['id'], 'form_fields.updated', 'form_field', $id, ['category_id' => (int) $field['category_id']]);
$fetch = db()->prepare('SELECT * FROM form_fields WHERE id = ?');
$fetch->execute([$id]);
respond(['field' => $fetch->fetch()]);
