<?php

declare(strict_types=1);

function fetch_repeatable_schema_for_template(int $templateId): array
{
    $sectionsStmt = db()->prepare('SELECT * FROM form_repeatable_sections WHERE template_id = ? ORDER BY sort_order, id');
    $sectionsStmt->execute([$templateId]);
    $sections = $sectionsStmt->fetchAll();
    if (!$sections) {
        return [];
    }

    $columnsStmt = db()->prepare('SELECT * FROM form_repeatable_columns WHERE section_id = ? ORDER BY sort_order, id');
    $result = [];
    foreach ($sections as $section) {
        $columnsStmt->execute([(int) $section['id']]);
        $section['columns'] = $columnsStmt->fetchAll();
        $result[] = $section;
    }
    return $result;
}

function normalize_repeatable_section_payload(array $input, array &$errors): ?array
{
    $sectionKey = strtolower(normalize_text_input($input['section_key'] ?? ''));
    $label = normalize_text_input($input['label'] ?? '');
    $helpText = array_key_exists('help_text', $input) ? normalize_text_input((string) $input['help_text']) : null;
    $minRows = isset($input['min_rows']) ? (int) $input['min_rows'] : 0;
    $maxRows = array_key_exists('max_rows', $input) && $input['max_rows'] !== '' ? (int) $input['max_rows'] : null;
    $pdfSection = array_key_exists('pdf_section', $input) ? normalize_text_input((string) $input['pdf_section']) : null;
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : 100;

    if ($sectionKey === '' || !preg_match('/^[a-z][a-z0-9_]{1,99}$/', $sectionKey)) {
        $errors['section_key'] = 'Use lowercase letters, numbers and underscores only.';
    }
    if ($label === '' || text_length($label) > 180 || contains_markup($label)) {
        $errors['label'] = 'Use plain text up to 180 characters.';
    }
    if ($helpText !== null && ($helpText === '' || text_length($helpText) > 255 || contains_markup($helpText))) {
        $errors['help_text'] = 'Use plain text up to 255 characters.';
    }
    if ($minRows < 0 || $minRows > 250) {
        $errors['min_rows'] = 'Minimum rows must be between 0 and 250.';
    }
    if ($maxRows !== null && ($maxRows < 1 || $maxRows > 250 || $maxRows < $minRows)) {
        $errors['max_rows'] = 'Maximum rows must be at least the minimum row count and no more than 250.';
    }
    if ($pdfSection !== null && ($pdfSection === '' || text_length($pdfSection) > 80 || contains_markup($pdfSection))) {
        $errors['pdf_section'] = 'Use plain text up to 80 characters.';
    }
    if ($sortOrder < 0 || $sortOrder > 32000) {
        $errors['sort_order'] = 'Sort order must be between 0 and 32000.';
    }

    if ($errors) {
        return null;
    }

    return [
        'section_key' => $sectionKey,
        'label' => $label,
        'help_text' => $helpText ?: null,
        'min_rows' => $minRows,
        'max_rows' => $maxRows,
        'pdf_section' => $pdfSection ?: null,
        'sort_order' => $sortOrder,
    ];
}

function normalize_repeatable_column_payload(array $input, array &$errors): ?array
{
    $columnKey = strtolower(normalize_text_input($input['column_key'] ?? ''));
    $label = normalize_text_input($input['label'] ?? '');
    $columnType = isset($input['column_type']) ? (string) $input['column_type'] : 'text';
    $allowed = ['text', 'textarea', 'number', 'date', 'select', 'checkbox', 'pass_fail'];
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : 100;
    $placeholder = array_key_exists('placeholder_text', $input) ? normalize_text_input((string) $input['placeholder_text']) : null;

    if ($columnKey === '' || !preg_match('/^[a-z][a-z0-9_]{1,99}$/', $columnKey)) {
        $errors['column_key'] = 'Use lowercase letters, numbers and underscores only.';
    }
    if ($label === '' || text_length($label) > 180 || contains_markup($label)) {
        $errors['label'] = 'Use plain text up to 180 characters.';
    }
    if (!in_array($columnType, $allowed, true)) {
        $errors['column_type'] = 'Invalid column type.';
    }
    if ($sortOrder < 0 || $sortOrder > 32000) {
        $errors['sort_order'] = 'Sort order must be between 0 and 32000.';
    }
    if ($placeholder !== null && ($placeholder === '' || text_length($placeholder) > 150 || contains_markup($placeholder))) {
        $errors['placeholder_text'] = 'Use plain text up to 150 characters.';
    }

    $optionsJson = normalize_field_options($columnType, $input['options'] ?? null, $errors);
    $validationJson = normalize_field_validation($input['validation'] ?? null, $errors);
    if ($errors) {
        return null;
    }

    return [
        'column_key' => $columnKey,
        'label' => $label,
        'column_type' => $columnType,
        'is_required' => !empty($input['is_required']) ? 1 : 0,
        'options_json' => $optionsJson,
        'validation_json' => $validationJson,
        'placeholder_text' => $placeholder ?: null,
        'appears_on_pdf' => !array_key_exists('appears_on_pdf', $input) || !empty($input['appears_on_pdf']) ? 1 : 0,
        'editable_after_approval' => !empty($input['editable_after_approval']) ? 1 : 0,
        'sort_order' => $sortOrder,
    ];
}

function validate_repeatable_items(array $itemsInput, array $sections, bool $enforceRequired = true): array
{
    $errors = [];
    $normalized = [];
    $sectionsByKey = [];
    foreach ($sections as $section) {
        $columns = [];
        foreach (($section['columns'] ?? []) as $column) {
            $columns[(string) $column['column_key']] = $column;
        }
        $section['columns_by_key'] = $columns;
        $sectionsByKey[(string) $section['section_key']] = $section;
    }

    foreach ($itemsInput as $sectionKey => $rows) {
        if (!isset($sectionsByKey[(string) $sectionKey])) {
            $errors['items'] = 'Submission contains an unknown repeatable section.';
            continue;
        }
        if (!is_array($rows)) {
            $errors['items_' . $sectionKey] = 'Section rows must be sent as a list.';
            continue;
        }
        $section = $sectionsByKey[(string) $sectionKey];
        if ($section['max_rows'] !== null && count($rows) > (int) $section['max_rows']) {
            $errors['items_' . $sectionKey] = 'Too many rows submitted for ' . $section['label'] . '.';
            continue;
        }

        $normalized[(string) $sectionKey] = [];
        foreach (array_values($rows) as $rowIndex => $row) {
            if (!is_array($row)) {
                $errors['items_' . $sectionKey . '_' . $rowIndex] = 'Each row must be an object.';
                continue;
            }
            $normalizedRow = [];
            foreach ($section['columns_by_key'] as $columnKey => $column) {
                $rawValue = $row[$columnKey] ?? '';
                $value = is_array($rawValue) || is_object($rawValue) ? null : normalize_text_input((string) $rawValue, (string) $column['column_type'] === 'textarea');
                if ($value === null) {
                    $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' must be a scalar value.';
                    continue;
                }

                if ($value === '') {
                    if ($enforceRequired && (int) $column['is_required'] === 1) {
                        $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' is required.';
                    }
                    $normalizedRow[$columnKey] = '';
                    continue;
                }

                $validation = json_decode((string) $column['validation_json'], true);
                switch ((string) $column['column_type']) {
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' must be numeric.';
                            break;
                        }
                        if (is_array($validation)) {
                            if (isset($validation['min']) && (float) $value < (float) $validation['min']) {
                                $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' is below the minimum allowed value.';
                                break;
                            }
                            if (isset($validation['max']) && (float) $value > (float) $validation['max']) {
                                $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' exceeds the maximum allowed value.';
                                break;
                            }
                        }
                        $normalizedRow[$columnKey] = (string) $value;
                        break;
                    case 'date':
                        if (supports_partial_historical_date_key($columnKey) ? !valid_partial_date($value) : !valid_iso_date($value)) {
                            $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' must use a valid date.';
                            break;
                        }
                        $normalizedRow[$columnKey] = $value;
                        break;
                    case 'select':
                        $options = json_decode((string) $column['options_json'], true);
                        if (!is_array($options) || !in_array($value, $options, true)) {
                            $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = 'Select a valid option for ' . $column['label'] . '.';
                            break;
                        }
                        $normalizedRow[$columnKey] = $value;
                        break;
                    case 'checkbox':
                        if (!in_array($value, ['Yes', 'No', 'True', 'False', '1', '0'], true)) {
                            $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' must use an allowed checkbox value.';
                            break;
                        }
                        $normalizedRow[$columnKey] = in_array($value, ['Yes', 'True', '1'], true) ? 'Yes' : 'No';
                        break;
                    case 'pass_fail':
                        if (!in_array($value, ['Pass', 'Fail', 'N/A'], true)) {
                            $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' must be Pass, Fail or N/A.';
                            break;
                        }
                        $normalizedRow[$columnKey] = $value;
                        break;
                    default:
                        if (contains_markup($value) || text_length($value) > 5000) {
                            $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' must be plain text.';
                            break;
                        }
                        if (is_array($validation)) {
                            if (isset($validation['min_length']) && text_length($value) < (int) $validation['min_length']) {
                                $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' is shorter than the minimum allowed length.';
                                break;
                            }
                            if (isset($validation['max_length']) && text_length($value) > (int) $validation['max_length']) {
                                $errors['items_' . $sectionKey . '_' . $rowIndex . '_' . $columnKey] = $column['label'] . ' exceeds the maximum allowed length.';
                                break;
                            }
                        }
                        $normalizedRow[$columnKey] = $value;
                        break;
                }
            }
            $normalized[(string) $sectionKey][] = $normalizedRow;
        }

        if ($enforceRequired && count($normalized[(string) $sectionKey]) < (int) $section['min_rows']) {
            $errors['items_' . $sectionKey] = 'At least ' . $section['min_rows'] . ' row(s) are required for ' . $section['label'] . '.';
        }
    }

    return [$errors, $normalized];
}

function fetch_inspection_items_grouped(int $inspectionId): array
{
    $stmt = db()->prepare('SELECT section_key, row_index, column_key, value_text FROM inspection_items WHERE inspection_id = ? ORDER BY section_key, row_index, id');
    $stmt->execute([$inspectionId]);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $sectionKey = (string) $row['section_key'];
        $rowIndex = (int) $row['row_index'];
        if (!isset($rows[$sectionKey])) {
            $rows[$sectionKey] = [];
        }
        if (!isset($rows[$sectionKey][$rowIndex])) {
            $rows[$sectionKey][$rowIndex] = [];
        }
        $rows[$sectionKey][$rowIndex][(string) $row['column_key']] = (string) ($row['value_text'] ?? '');
    }

    foreach ($rows as $sectionKey => $sectionRows) {
        ksort($sectionRows);
        $rows[$sectionKey] = array_values($sectionRows);
    }

    return $rows;
}

