<?php
require_once __DIR__ . '/../_bootstrap.php';

function validate_category_update_payload(array $input, int $existingId): array
{
    require_once __DIR__ . '/index.php';
    return validate_category_payload($input, $existingId);
}

require_methods(['GET', 'PATCH']);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    api_error('Category id is required.', 422, ['id' => 'Required.']);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_permission('categories.view');
} else {
    $user = require_any_permission(['categories.edit','categories.manage']);
    require_csrf();
}
$stmt = db()->prepare('SELECT * FROM certification_categories WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$category = $stmt->fetch();
if (!$category) {
    api_error('Category not found.', 404);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    respond(['category' => $category]);
}

$input = json_input();
$payload = [
    'code' => $input['code'] ?? $category['code'],
    'short_code' => $input['short_code'] ?? $category['short_code'],
    'name' => $input['name'] ?? $category['name'],
    'description' => array_key_exists('description', $input) ? $input['description'] : $category['description'],
    'validity_months' => $input['validity_months'] ?? $category['validity_months'],
    'certificate_template' => array_key_exists('certificate_template', $input) ? $input['certificate_template'] : $category['certificate_template'],
    'template_family' => array_key_exists('template_family', $input) ? $input['template_family'] : ($category['template_family'] ?? null),
    'layout_key' => array_key_exists('layout_key', $input) ? $input['layout_key'] : ($category['layout_key'] ?? null),
    'source_sample' => array_key_exists('source_sample', $input) ? $input['source_sample'] : ($category['source_sample'] ?? null),
    'schema_version' => $input['schema_version'] ?? ($category['schema_version'] ?? 1),
    'certificate_prefix' => array_key_exists('certificate_prefix', $input) ? $input['certificate_prefix'] : $category['certificate_prefix'],
    'identifier_label' => array_key_exists('identifier_label', $input) ? $input['identifier_label'] : $category['identifier_label'],
    'theme_color' => array_key_exists('theme_color', $input) ? $input['theme_color'] : $category['theme_color'],
    'requires_review' => array_key_exists('requires_review', $input) ? $input['requires_review'] : $category['requires_review'],
    'status' => $category['status'],
];
[$errors, $validated] = validate_category_update_payload($payload, $id);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}
$stmt = db()->prepare('UPDATE certification_categories SET code = ?, short_code = ?, name = ?, description = ?, validity_months = ?, certificate_template = ?, template_family = ?, layout_key = ?, source_sample = ?, schema_version = ?, certificate_prefix = ?, identifier_label = ?, theme_color = ?, requires_review = ?, status = ?, updated_at = ? WHERE id = ?');
$stmt->execute([
    $validated['code'],
    $validated['short_code'],
    $validated['name'],
    $validated['description'],
    $validated['validity_months'],
    $validated['certificate_template'],
    $validated['template_family'],
    $validated['layout_key'],
    $validated['source_sample'],
    $validated['schema_version'],
    $validated['certificate_prefix'],
    $validated['identifier_label'],
    $validated['theme_color'],
    $validated['requires_review'],
    $validated['status'],
    now_sql(),
    $id,
]);
audit_log((int) $user['id'], 'categories.updated', 'category', $id, ['short_code' => $validated['short_code']]);
$stmt = db()->prepare('SELECT * FROM certification_categories WHERE id = ?');
$stmt->execute([$id]);
respond(['category' => $stmt->fetch()]);
