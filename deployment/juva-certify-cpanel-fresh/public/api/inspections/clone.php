<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/form_schema.php';

require_method('POST');
$user = require_permission('inspections.create');
require_csrf();
$input = json_input();
$errors = validate_required($input, ['inspection_id']);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$sourceId = (int) $input['inspection_id'];
$source = fetch_inspection_for_user($sourceId, $user);
if (!$source) {
    api_error('Inspection not found.', 404);
}
if (!in_array((string) $source['status'], ['approved', 'correction', 'issued', 'expired', 'revoked'], true)) {
    api_error('Only approved, returned, issued, expired or revoked inspections can be cloned for renewal.', 422, ['inspection_id' => 'This record is not eligible for renewal.']);
}

$clientStmt = db()->prepare('SELECT id, short_code, name FROM clients WHERE id = ? LIMIT 1');
$clientStmt->execute([(int) $source['client_id']]);
$client = $clientStmt->fetch();
if (!$client || empty($client['short_code'])) {
    api_error('The source client needs a short code before renewal can continue.', 422, ['client_id' => 'Update the client short code first.']);
}

$templateStmt = db()->prepare('SELECT t.id, c.id AS category_id, c.short_code, c.name FROM form_templates t JOIN certification_categories c ON c.id = t.category_id WHERE t.category_id = ? AND t.status = ? AND c.status = ? ORDER BY t.version DESC LIMIT 1');
$templateStmt->execute([(int) $source['category_id'], 'active', 'active']);
$template = $templateStmt->fetch();
if (!$template || empty($template['short_code'])) {
    api_error('The source category needs an active template and short code before renewal can continue.', 422, ['category_id' => 'Update the category setup first.']);
}

$valueStmt = db()->prepare('SELECT f.field_key, v.value_text FROM inspection_values v JOIN form_fields f ON f.id=v.field_id WHERE v.inspection_id = ?');
$valueStmt->execute([$sourceId]);
$sourceValues = $valueStmt->fetchAll();
$itemStmt = db()->prepare('SELECT section_key, row_index, column_key, value_text FROM inspection_items WHERE inspection_id = ? ORDER BY section_key, row_index, id');
$itemStmt->execute([$sourceId]);
$sourceItems = $itemStmt->fetchAll();
$certificateStmt = db()->prepare('SELECT id FROM certificates WHERE inspection_id = ? LIMIT 1');
$certificateStmt->execute([$sourceId]);
$certificate = $certificateStmt->fetch();

$pdo = db();
$pdo->beginTransaction();
try {
    $allocation = allocate_scoped_reference($pdo, (int) $source['client_id'], (int) $source['category_id'], (string) $client['short_code'], (string) $template['short_code']);
    $today = gmdate('Y-m-d');
    $insert = $pdo->prepare('INSERT INTO inspections (reference, sequence_number, client_id, equipment_id, category_id, form_template_id, inspector_id, inspection_date, next_due_date, location, result, status, remarks, cloned_from_inspection_id, renewal_source_certificate_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([
        $allocation['reference'],
        (int) $allocation['sequence_number'],
        (int) $source['client_id'],
        (int) $source['equipment_id'],
        (int) $source['category_id'],
        (int) $template['id'],
        (int) $user['id'],
        $today,
        null,
        $source['location'],
        $source['result'],
        'draft',
        $source['remarks'],
        $sourceId,
        $certificate ? (int) $certificate['id'] : null,
        now_sql(),
        now_sql(),
    ]);
    $newInspectionId = (int) $pdo->lastInsertId();

    if ($sourceValues) {
        $targetFields = $pdo->prepare('SELECT id, field_key FROM form_fields WHERE template_id = ?');
        $targetFields->execute([(int) $template['id']]);
        $targetByKey = [];
        foreach ($targetFields->fetchAll() as $targetField) {
            $targetByKey[(string) $targetField['field_key']] = (int) $targetField['id'];
        }
        $valueInsert = $pdo->prepare('INSERT INTO inspection_values (inspection_id, field_id, value_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
        foreach ($sourceValues as $row) {
            $fieldKey = (string) $row['field_key'];
            if (!isset($targetByKey[$fieldKey])) { continue; }
            $valueInsert->execute([$newInspectionId, $targetByKey[$fieldKey], $row['value_text'], now_sql(), now_sql()]);
        }
    }
    if ($sourceItems) {
        $itemInsert = $pdo->prepare('INSERT INTO inspection_items (inspection_id, section_key, row_index, column_key, value_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($sourceItems as $row) {
            $itemInsert->execute([$newInspectionId, $row['section_key'], (int) $row['row_index'], $row['column_key'], $row['value_text'], now_sql(), now_sql()]);
        }
    }

    $comment = $pdo->prepare('INSERT INTO inspection_comments (inspection_id, user_id, comment_type, comment_text, created_at) VALUES (?, ?, ?, ?, ?)');
    $comment->execute([$newInspectionId, (int) $user['id'], 'status', 'Renewal draft cloned from inspection ' . $source['reference'] . '.', now_sql()]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    api_error('Unable to create renewal draft.', 422);
}

audit_log((int) $user['id'], 'inspections.cloned', 'inspection', $newInspectionId, ['source_inspection_id' => $sourceId, 'source_reference' => $source['reference']]);
audit_log((int) $user['id'], 'inspections.renewal_created', 'inspection', $newInspectionId, ['source_inspection_id' => $sourceId, 'source_certificate_id' => $certificate ? (int) $certificate['id'] : null]);

$stmt = db()->prepare('SELECT i.*, c.name AS client_name, c.short_code AS client_short_code, e.asset_code, e.name AS equipment_name, cat.name AS category_name, cat.short_code AS category_short_code, u.name AS inspector_name, src.reference AS cloned_from_reference FROM inspections i JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id JOIN users u ON u.id = i.inspector_id LEFT JOIN inspections src ON src.id = i.cloned_from_inspection_id WHERE i.id = ? LIMIT 1');
$stmt->execute([$newInspectionId]);
$inspection = $stmt->fetch();
$fieldsStmt = db()->prepare('SELECT f.*, v.value_text FROM form_fields f LEFT JOIN inspection_values v ON v.field_id = f.id AND v.inspection_id = ? WHERE f.template_id = ? ORDER BY f.sort_order, f.id');
$fieldsStmt->execute([$newInspectionId, $inspection['form_template_id']]);
respond([
    'inspection' => $inspection,
    'fields' => $fieldsStmt->fetchAll(),
    'sections' => fetch_repeatable_schema_for_template((int) $inspection['form_template_id']),
    'items' => fetch_inspection_items_grouped($newInspectionId),
    'source_reference' => $source['reference'],
], 201);
