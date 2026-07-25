<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$user = require_permission('categories.manage');
require_csrf();
$input = json_input();
$id = isset($input['category_id']) ? (int) $input['category_id'] : 0;
if ($id <= 0) {
    api_error('Category id is required.', 422, ['category_id' => 'Required.']);
}

$source = db()->prepare('SELECT * FROM form_templates WHERE category_id = ? ORDER BY version DESC LIMIT 1');
$source->execute([$id]);
$template = $source->fetch();
if (!$template) {
    api_error('No form template exists for this category.', 404);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $version = ((int) $template['version']) + 1;
    $pdo->prepare('UPDATE form_templates SET status = ? WHERE category_id = ? AND status = ?')->execute(['archived', $id, 'active']);
    $create = $pdo->prepare('INSERT INTO form_templates (category_id, name, version, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
    $create->execute([$id, $template['name'], $version, 'active', now_sql(), now_sql()]);
    $newTemplateId = (int) $pdo->lastInsertId();

    $fields = $pdo->prepare('SELECT * FROM form_fields WHERE template_id = ? ORDER BY sort_order, id');
    $fields->execute([(int) $template['id']]);
    $copyField = $pdo->prepare('INSERT INTO form_fields (template_id, field_key, label, help_text, placeholder_text, field_type, is_required, options_json, validation_json, appears_on_pdf, editable_after_approval, pdf_section, repeatable_group, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($fields->fetchAll() as $field) {
        $copyField->execute([
            $newTemplateId,
            $field['field_key'],
            $field['label'],
            $field['help_text'] ?? null,
            $field['placeholder_text'] ?? null,
            $field['field_type'],
            $field['is_required'],
            $field['options_json'],
            $field['validation_json'],
            $field['appears_on_pdf'] ?? 1,
            $field['editable_after_approval'] ?? 0,
            $field['pdf_section'] ?? null,
            $field['repeatable_group'] ?? null,
            $field['sort_order'],
            now_sql(),
        ]);
    }

    $sections = $pdo->prepare('SELECT * FROM form_repeatable_sections WHERE template_id = ? ORDER BY sort_order, id');
    $sections->execute([(int) $template['id']]);
    $sectionCopy = $pdo->prepare('INSERT INTO form_repeatable_sections (template_id, section_key, label, help_text, min_rows, max_rows, pdf_section, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $columnFetch = $pdo->prepare('SELECT * FROM form_repeatable_columns WHERE section_id = ? ORDER BY sort_order, id');
    $columnCopy = $pdo->prepare('INSERT INTO form_repeatable_columns (section_id, column_key, label, column_type, is_required, options_json, validation_json, placeholder_text, appears_on_pdf, editable_after_approval, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($sections->fetchAll() as $section) {
        $sectionCopy->execute([
            $newTemplateId,
            $section['section_key'],
            $section['label'],
            $section['help_text'] ?? null,
            $section['min_rows'],
            $section['max_rows'],
            $section['pdf_section'] ?? null,
            $section['sort_order'],
            now_sql(),
            now_sql(),
        ]);
        $newSectionId = (int) $pdo->lastInsertId();
        $columnFetch->execute([(int) $section['id']]);
        foreach ($columnFetch->fetchAll() as $column) {
            $columnCopy->execute([
                $newSectionId,
                $column['column_key'],
                $column['label'],
                $column['column_type'],
                $column['is_required'],
                $column['options_json'],
                $column['validation_json'],
                $column['placeholder_text'] ?? null,
                $column['appears_on_pdf'] ?? 1,
                $column['editable_after_approval'] ?? 0,
                $column['sort_order'],
                now_sql(),
                now_sql(),
            ]);
        }
    }

    $pdo->commit();
    audit_log((int) $user['id'], 'form_templates.versioned', 'category', $id, ['version' => $version]);
    respond(['template_id' => $newTemplateId, 'version' => $version], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    api_error('Unable to publish a new form version.', 422);
}
