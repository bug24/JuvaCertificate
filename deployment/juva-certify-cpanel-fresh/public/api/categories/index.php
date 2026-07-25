<?php
require_once __DIR__ . '/../_bootstrap.php';

function validate_category_payload(array $input, ?int $existingId = null): array
{
    $errors = [];
    $code = strtoupper(normalize_text_input($input['code'] ?? ''));
    $shortCode = strtoupper(preg_replace('/[^A-Z0-9]+/', '', normalize_text_input($input['short_code'] ?? '')) ?? '');
    $name = normalize_text_input($input['name'] ?? '');
    $description = array_key_exists('description', $input) ? normalize_text_input((string) $input['description'], true) : null;
    $validityMonths = isset($input['validity_months']) ? (int) $input['validity_months'] : 12;
    $certificateTemplate = array_key_exists('certificate_template', $input) ? normalize_text_input((string) $input['certificate_template']) : null;
    $templateFamily = array_key_exists('template_family', $input) ? strtolower(normalize_text_input((string) $input['template_family'])) : null;
    $layoutKey = array_key_exists('layout_key', $input) ? normalize_text_input((string) $input['layout_key']) : null;
    $sourceSample = array_key_exists('source_sample', $input) ? normalize_text_input((string) $input['source_sample']) : null;
    $schemaVersion = isset($input['schema_version']) ? max(1, (int) $input['schema_version']) : 1;
    $certificatePrefix = array_key_exists('certificate_prefix', $input) ? strtoupper(normalize_text_input((string) $input['certificate_prefix'])) : null;
    $identifierLabel = array_key_exists('identifier_label', $input) ? normalize_text_input((string) $input['identifier_label']) : 'Certificate / Inspection ID';
    $themeColor = array_key_exists('theme_color', $input) ? strtoupper(normalize_text_input((string) $input['theme_color'])) : '#151515';
    $requiresReview = array_key_exists('requires_review', $input) ? (!empty($input['requires_review']) ? 1 : 0) : 1;
    $status = isset($input['status']) ? (string) $input['status'] : 'active';

    if ($code === '') {
        $errors['code'] = 'This field is required.';
    } elseif (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/', $code)) {
        $errors['code'] = 'Use 2-30 uppercase letters, numbers, underscores or dashes.';
    }
    if ($shortCode === '') {
        $errors['short_code'] = 'Category short code is required.';
    } elseif (!preg_match('/^[A-Z0-9]{2,12}$/', $shortCode)) {
        $errors['short_code'] = 'Use 2-12 uppercase letters or numbers.';
    }
    if ($name === '') {
        $errors['name'] = 'This field is required.';
    } elseif (text_length($name) > 180 || contains_markup($name)) {
        $errors['name'] = 'Use plain text up to 180 characters.';
    }
    if ($description !== null && $description !== '') {
        if (text_length($description) > 5000 || contains_markup($description)) {
            $errors['description'] = 'Use plain text only.';
        }
    } else {
        $description = null;
    }
    if ($validityMonths < 1 || $validityMonths > 120) {
        $errors['validity_months'] = 'Enter a validity between 1 and 120 months.';
    }
    foreach ([['certificate_template', $certificateTemplate, 100], ['layout_key', $layoutKey, 100], ['source_sample', $sourceSample, 190]] as $meta) {
        [$key, $value, $limit] = $meta;
        if ($value !== null && $value !== '' && (text_length($value) > $limit || contains_markup($value))) {
            $errors[$key] = 'Use plain text only.';
        }
    }
    if ($templateFamily !== null && $templateFamily !== '' && !preg_match('/^[a-z][a-z0-9_-]{1,49}$/', $templateFamily)) {
        $errors['template_family'] = 'Use lowercase letters, numbers, underscores or dashes only.';
    }
    if ($certificatePrefix !== null && $certificatePrefix !== '') {
        if (!preg_match('/^[A-Z0-9][A-Z0-9._\/-]{1,39}$/', $certificatePrefix)) {
            $errors['certificate_prefix'] = 'Use 2-40 uppercase letters, numbers, dots, slashes, underscores or dashes.';
        }
    } else {
        $certificatePrefix = null;
    }
    if ($identifierLabel === '' || text_length($identifierLabel) > 120 || contains_markup($identifierLabel)) {
        $errors['identifier_label'] = 'Use plain text up to 120 characters.';
    }
    if (!preg_match('/^#[0-9A-F]{6}$/', $themeColor)) {
        $errors['theme_color'] = 'Use a valid hex color like #151515.';
    }
    if (!in_array($status, ['active', 'inactive', 'legacy'], true)) {
        $errors['status'] = 'Invalid category status.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM certification_categories WHERE code = ? AND (? IS NULL OR id <> ?) LIMIT 1');
        $stmt->execute([$code, $existingId, $existingId]);
        if ($stmt->fetch()) {
            $errors['code'] = 'This category code already exists.';
        }
        $shortStmt = db()->prepare('SELECT id FROM certification_categories WHERE short_code = ? AND (? IS NULL OR id <> ?) LIMIT 1');
        $shortStmt->execute([$shortCode, $existingId, $existingId]);
        if ($shortStmt->fetch()) {
            $errors['short_code'] = 'This short code already exists.';
        }
    }

    return [$errors, [
        'code' => $code,
        'short_code' => $shortCode,
        'name' => $name,
        'description' => $description,
        'validity_months' => $validityMonths,
        'certificate_template' => $certificateTemplate ?: null,
        'template_family' => $templateFamily ?: null,
        'layout_key' => $layoutKey ?: null,
        'source_sample' => $sourceSample ?: null,
        'schema_version' => $schemaVersion,
        'certificate_prefix' => $certificatePrefix,
        'identifier_label' => $identifierLabel,
        'theme_color' => $themeColor,
        'requires_review' => $requiresReview,
        'status' => $status,
    ]];
}

require_methods(['GET', 'POST']);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_permission('categories.view');
    $statusFilter = strtolower(trim((string) ($_GET['status'] ?? '')));
    if ($statusFilter !== '' && !in_array($statusFilter, ['active','inactive','legacy'], true)) {
        api_error('Invalid category status filter.', 422);
    }
    $sql = "SELECT c.*,
        (SELECT COUNT(*) FROM form_fields f JOIN form_templates t ON t.id=f.template_id WHERE t.category_id=c.id AND t.status='active') AS field_count,
        (SELECT COUNT(*) FROM form_repeatable_sections s JOIN form_templates t ON t.id=s.template_id WHERE t.category_id=c.id AND t.status='active') AS section_count,
        (SELECT MAX(version) FROM form_templates t WHERE t.category_id=c.id AND t.status='active') AS current_version,
        (SELECT COUNT(*) FROM inspections i WHERE i.category_id=c.id) AS inspection_count,
        (SELECT COUNT(*) FROM inspections i WHERE i.category_id=c.id AND i.status IN ('draft','correction')) AS draft_count,
        (SELECT COUNT(*) FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id WHERE i.category_id=c.id) AS certificate_count
      FROM certification_categories c
      WHERE (:statusFilterEmpty = '' OR c.status = :statusFilterValue)
      ORDER BY c.name";
    $stmt = db()->prepare($sql);
    $stmt->execute(['statusFilterEmpty'=>$statusFilter,'statusFilterValue'=>$statusFilter]);
    respond(['categories'=>$stmt->fetchAll()]);
}
$user = require_any_permission(['categories.create','categories.manage']);
require_csrf();
$input = json_input();
[$errors, $payload] = validate_category_payload($input);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO certification_categories (code, short_code, name, description, validity_months, certificate_template, template_family, layout_key, source_sample, schema_version, certificate_prefix, identifier_label, theme_color, requires_review, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $payload['code'],
        $payload['short_code'],
        $payload['name'],
        $payload['description'],
        $payload['validity_months'],
        $payload['certificate_template'],
        $payload['template_family'],
        $payload['layout_key'],
        $payload['source_sample'],
        $payload['schema_version'],
        $payload['certificate_prefix'],
        $payload['identifier_label'],
        $payload['theme_color'],
        $payload['requires_review'],
        $payload['status'],
        now_sql(),
        now_sql(),
    ]);
    $categoryId = (int) $pdo->lastInsertId();
    $template = $pdo->prepare('INSERT INTO form_templates (category_id, name, version, status, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?)');
    $template->execute([$categoryId, $payload['name'] . ' Form', 'active', now_sql(), now_sql()]);
    $templateId = (int) $pdo->lastInsertId();

    $fields = [
        ['client_name', 'Client Name', 'Client or asset owner shown on the certificate.', null, 'text', 1, 10, 'summary'],
        ['equipment_identifier', 'Equipment ID / Tag Number', 'Tag, asset number, serial or ID used by the client.', null, 'text', 1, 20, 'equipment'],
        ['date_of_current_examination', 'Date of Current Examination', 'Current examination date as shown on the certificate.', null, 'date', 1, 30, 'examination'],
        ['safe_working_load', 'SWL / WLL / Capacity', 'Rated safe working load, working load limit or capacity.', null, 'text', 0, 40, 'examination'],
        ['additional_remarks', 'Additional Remarks', 'Any extra remarks to appear on the certificate.', null, 'textarea', 0, 50, 'remarks'],
    ];
    $fieldStmt = $pdo->prepare('INSERT INTO form_fields (template_id, field_key, label, help_text, placeholder_text, field_type, is_required, appears_on_pdf, editable_after_approval, pdf_section, repeatable_group, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($fields as $field) {
        $fieldStmt->execute([$templateId, $field[0], $field[1], $field[2], $field[3], $field[4], $field[5], 1, 0, $field[7], null, $field[6], now_sql()]);
    }
    $pdo->commit();

    audit_log((int) $user['id'], 'categories.created', 'category', $categoryId, ['short_code' => $payload['short_code']]);
    $stmt = db()->prepare('SELECT * FROM certification_categories WHERE id = ?');
    $stmt->execute([$categoryId]);
    respond(['category' => $stmt->fetch()], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    api_error('Unable to create category.', 422);
}
