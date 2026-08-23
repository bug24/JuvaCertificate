<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/form_schema.php';

function validate_inspection_values(array $inputValues, array $fieldRows, bool $enforceRequired = true): array
{
    $errors = [];
    $normalized = [];

    if (count($inputValues) > count($fieldRows)) {
        $errors['values'] = 'Form submission contains unexpected fields.';
        return [$errors, $normalized];
    }

    $fieldsById = [];
    foreach ($fieldRows as $field) {
        $fieldsById[(int) $field['id']] = $field;
    }

    foreach ($inputValues as $fieldId => $rawValue) {
        if (!is_scalar($fieldId) || !isset($fieldsById[(int) $fieldId])) {
            $errors['values'] = 'Form submission contains an invalid field.';
            continue;
        }
        $field = $fieldsById[(int) $fieldId];
        $type = (string) $field['field_type'];
                $validation = json_decode((string) $field['validation_json'], true);
        $options = $type === 'select' ? json_decode((string) $field['options_json'], true) : [];
        $isYesNo = is_array($options) && in_array('Yes', $options, true) && in_array('No', $options, true);
        if ($isYesNo && !is_array($rawValue) && !is_object($rawValue)) {
            $textValue = strtolower(trim((string) $rawValue));
            if ($rawValue === true || $rawValue === 1 || $rawValue === '1' || $textValue === 'yes' || $textValue === 'true') $rawValue = 'Yes';
            elseif ($rawValue === false || $rawValue === 0 || $rawValue === '0' || $textValue === 'no' || $textValue === 'false') $rawValue = 'No';
        }
        $value = is_array($rawValue) || is_object($rawValue) ? null : normalize_text_input((string) $rawValue, $type === 'textarea');

        if ($value === null) {
            $errors['field_' . $fieldId] = 'Field value must be a scalar.';
            continue;
        }
        if ($value === '') {
            $normalized[(int) $fieldId] = '';
            continue;
        }

        switch ($type) {
            case 'number':
                if (!is_numeric($value)) {
                    $errors['field_' . $fieldId] = $field['label'] . ' must be numeric.';
                    break;
                }
                if (is_array($validation)) {
                    if (isset($validation['min']) && (float) $value < (float) $validation['min']) {
                        $errors['field_' . $fieldId] = $field['label'] . ' is below the minimum allowed value.';
                        break;
                    }
                    if (isset($validation['max']) && (float) $value > (float) $validation['max']) {
                        $errors['field_' . $fieldId] = $field['label'] . ' exceeds the maximum allowed value.';
                        break;
                    }
                }
                $normalized[(int) $fieldId] = (string) $value;
                break;
            case 'date':
                if (supports_partial_historical_date_key((string) $field['field_key']) ? !valid_partial_date($value) : !valid_iso_date($value)) {
                    $errors['field_' . $fieldId] = $field['label'] . ' must use a valid date.';
                    break;
                }
                $normalized[(int) $fieldId] = $value;
                break;
            case 'select':
                $options = json_decode((string) $field['options_json'], true);
                if (!is_array($options) || !in_array($value, $options, true)) {
                    $errors['field_' . $fieldId] = 'Select a valid option for ' . $field['label'] . '.';
                    break;
                }
                $normalized[(int) $fieldId] = $value;
                break;
            case 'checkbox':
                $checkboxOptions = json_decode((string) $field['options_json'], true);
                if (is_array($checkboxOptions) && count($checkboxOptions) > 0) {
                    $selectedOptions = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
                    $selectedOptions = array_values(array_unique(array_map('trim', $selectedOptions ?: [])));
                    if (!$selectedOptions || array_diff($selectedOptions, $checkboxOptions)) {
                        $errors['field_' . $fieldId] = $field['label'] . ' contains an invalid selection.';
                        break;
                    }
                    $normalized[(int) $fieldId] = implode(', ', $selectedOptions);
                    break;
                }
                if (!in_array($value, ['Yes', 'No', 'True', 'False', '1', '0'], true)) {
                    $errors['field_' . $fieldId] = $field['label'] . ' must use an allowed checkbox value.';
                    break;
                }
                $normalized[(int) $fieldId] = in_array($value, ['Yes', 'True', '1'], true) ? 'Yes' : 'No';
                break;
            case 'pass_fail':
                if (!in_array($value, ['Pass', 'Fail', 'N/A'], true)) {
                    $errors['field_' . $fieldId] = $field['label'] . ' must be Pass, Fail or N/A.';
                    break;
                }
                $normalized[(int) $fieldId] = $value;
                break;
            case 'photo':
            case 'signature':
                if ($value !== '') {
                    $errors['field_' . $fieldId] = $field['label'] . ' must be uploaded as a file, not entered as text.';
                }
                break;
            default:
                if (contains_markup($value) || text_length($value) > 5000) {
                    $errors['field_' . $fieldId] = $field['label'] . ' must be plain text.';
                    break;
                }
                if (is_array($validation)) {
                    if (isset($validation['min_length']) && text_length($value) < (int) $validation['min_length']) {
                        $errors['field_' . $fieldId] = $field['label'] . ' is shorter than the minimum allowed length.';
                        break;
                    }
                    if (isset($validation['max_length']) && text_length($value) > (int) $validation['max_length']) {
                        $errors['field_' . $fieldId] = $field['label'] . ' exceeds the maximum allowed length.';
                        break;
                    }
                    if (isset($validation['pattern']) && @preg_match('/' . $validation['pattern'] . '/u', '') !== false && !preg_match('/' . $validation['pattern'] . '/u', $value)) {
                        $errors['field_' . $fieldId] = $field['label'] . ' does not match the expected format.';
                        break;
                    }
                }
                $normalized[(int) $fieldId] = $value;
                break;
        }
    }

    if ($enforceRequired) {
        foreach ($fieldRows as $field) {
            if ((int) $field['is_required'] === 1) {
                $fieldId = (int) $field['id'];
                $fieldType = (string) $field['field_type'];
                if (in_array($fieldType, ['photo', 'signature'], true)) {
                    continue;
                }
                if (!isset($normalized[$fieldId]) || $normalized[$fieldId] === '') {
                    $errors['field_' . $fieldId] = $field['label'] . ' is required.';
                }
            }
        }
    }

    return [$errors, $normalized];
}

if (defined('JUVA_INSPECTION_VALIDATION_ONLY') && JUVA_INSPECTION_VALIDATION_ONLY) {
    return;
}

require_methods(['GET', 'POST']);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = require_permission('inspections.view');
    [$scopeSql, $scopeParams] = inspection_scope_clause($user, 'i');
    $sql = 'SELECT i.*, c.name AS client_name, c.short_code AS client_short_code, e.asset_code, e.name AS equipment_name, cat.name AS category_name, cat.short_code AS category_short_code, u.name AS inspector_name FROM inspections i JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id JOIN users u ON u.id = i.inspector_id WHERE 1 = 1' . $scopeSql . ' ORDER BY i.created_at DESC LIMIT 300';
    $stmt = db()->prepare($sql);
    $stmt->execute($scopeParams);
    respond(['inspections' => $stmt->fetchAll()]);
}

$user = require_permission('inspections.create');
require_csrf();
$input = json_input();
$draftOnly = !empty($input['draft_only']);
$inspectionId = isset($input['id']) ? (int) $input['id'] : 0;
$isUpdate = $inspectionId > 0;
$errors = validate_required($input, ['client_id', 'equipment_id', 'category_id', 'inspection_date']);
if (!$draftOnly && empty($input['next_due_date'])) { $errors['next_due_date'] = 'This field is required before submission.'; }

if (isset($input['inspection_date']) && !valid_iso_date((string) $input['inspection_date'])) {
    $errors['inspection_date'] = 'Use a valid inspection date.';
}
if (isset($input['next_due_date']) && $input['next_due_date'] !== '' && !valid_iso_date((string) $input['next_due_date'])) {
    $errors['next_due_date'] = 'Use a valid next due date.';
}
if (!empty($input['next_due_date']) && !empty($input['inspection_date']) && valid_iso_date((string) $input['inspection_date']) && valid_iso_date((string) $input['next_due_date']) && (string) $input['next_due_date'] < (string) $input['inspection_date']) {
    $errors['next_due_date'] = 'Next due date cannot be earlier than inspection date.';
}

$clientId = isset($input['client_id']) ? (int) $input['client_id'] : 0;
$equipmentId = isset($input['equipment_id']) ? (int) $input['equipment_id'] : 0;
$categoryId = isset($input['category_id']) ? (int) $input['category_id'] : 0;
$result = isset($input['result']) ? (string) $input['result'] : 'pending';
if (!in_array($result, ['pending', 'safe', 'unsafe', 'conditional'], true)) {
    $errors['result'] = 'Invalid inspection result.';
}
$location = isset($input['location']) ? normalize_text_input((string) $input['location']) : null;
$remarks = isset($input['remarks']) ? normalize_text_input((string) $input['remarks'], true) : null;
if ($location !== null && $location !== '' && (text_length($location) > 255 || contains_markup($location))) {
    $errors['location'] = 'Use plain text only.';
}
if ($remarks !== null && $remarks !== '' && (text_length($remarks) > 5000 || contains_markup($remarks))) {
    $errors['remarks'] = 'Use plain text only.';
}

$clientStmt = db()->prepare('SELECT id, short_code, name FROM clients WHERE id = ? LIMIT 1');
$clientStmt->execute([$clientId]);
$client = $clientStmt->fetch();
if (!$client) {
    $errors['client_id'] = 'Selected client was not found.';
} elseif (!valid_company_short_code((string) ($client['short_code'] ?? ''))) {
    $errors['client_id'] = 'Selected client is missing a valid company short name. Update the client record before creating certificates.';
}

$equipmentStmt = db()->prepare('SELECT id, client_id FROM equipment WHERE id = ? LIMIT 1');
$equipmentStmt->execute([$equipmentId]);
$equipment = $equipmentStmt->fetch();
if (!$equipment) {
    $errors['equipment_id'] = 'Selected equipment was not found.';
} elseif ((int) $equipment['client_id'] !== $clientId) {
    $errors['equipment_id'] = 'Selected equipment does not belong to the selected client.';
}

$templateStmt = db()->prepare('SELECT t.id, t.category_id, c.short_code, c.name, c.status AS category_status FROM form_templates t JOIN certification_categories c ON c.id = t.category_id WHERE t.category_id = ? AND t.status = ? ORDER BY t.version DESC LIMIT 1');
$templateStmt->execute([$categoryId, 'active']);
$template = $templateStmt->fetch();
if (!$template) {
    $errors['category_id'] = 'Selected category has no active form template.';
} elseif (!$isUpdate && (string) ($template['category_status'] ?? '') !== 'active') {
    $errors['category_id'] = 'Selected category is not available for new inspections.';
} elseif (empty($template['short_code'])) {
    $errors['category_id'] = 'Selected category is missing a short code. Update the category before creating certificates.';
}

$existingInspection = null;
$reference = null;
$sequenceNumber = null;
$inspectorId = (int) $user['id'];
$authenticatorId = null;
if (!empty($input['authenticator_id'])) {
    $requestedAuthenticatorId = (int) $input['authenticator_id'];
    $requestedAuthenticator = fetch_user($requestedAuthenticatorId);
    if (!$requestedAuthenticator || $requestedAuthenticator['status'] !== 'active' || !in_array($requestedAuthenticator['role_slug'], ['super-admin', 'operations-admin', 'reviewer'], true)) {
        $errors['authenticator_id'] = 'Select an active eligible authenticator.';
    } else {
        $authenticatorId = $requestedAuthenticatorId;
    }
}
if (!empty($input['inspector_id'])) {
    $requestedInspectorId = (int) $input['inspector_id'];
    $requestedInspector = fetch_user($requestedInspectorId);
    if (!$requestedInspector || $requestedInspector['status'] !== 'active' || !in_array($requestedInspector['role_slug'], ['super-admin', 'operations-admin', 'inspector'], true)) {
        $errors['inspector_id'] = 'Select an active eligible inspector.';
    } else {
        $inspectorId = $requestedInspectorId;
    }
}
if ($isUpdate) {
    $existingInspection = fetch_inspection_for_user($inspectionId, $user);
    if (!$existingInspection) {
        api_error('Inspection not found.', 404);
    }
    if (!in_array((string) $existingInspection['status'], ['draft', 'correction'], true)) {
        api_error('Only draft or returned inspections can be edited.', 422, ['id' => 'This inspection can no longer be edited.']);
    }
    if ((int) $existingInspection['client_id'] !== $clientId) {
        $errors['client_id'] = 'Create a new draft to change the client on an allocated inspection.';
    }
    if ((int) $existingInspection['category_id'] !== $categoryId) {
        $errors['category_id'] = 'Create a new draft to change the category on an allocated inspection.';
    }
    $reference = (string) $existingInspection['reference'];
    $sequenceNumber = $existingInspection['sequence_number'] !== null ? (int) $existingInspection['sequence_number'] : null;
    $inspectorId = !empty($input['inspector_id']) ? $inspectorId : (int) $existingInspection['inspector_id'];
    $authenticatorId = !empty($input['authenticator_id']) ? $authenticatorId : (!empty($existingInspection['authenticator_id']) ? (int) $existingInspection['authenticator_id'] : null);
}

if (!$draftOnly) {
    if (empty($input['inspector_id']) && (!$existingInspection || empty($existingInspection['inspector_id']))) {
        $errors['inspector_id'] = 'Select the Inspector (Prepared by) before submission.';
    }
    if ($authenticatorId === null) {
        $errors['authenticator_id'] = 'Select the Authenticator (Authorized by) before submission.';
    }
}
$fieldStmt = db()->prepare('SELECT * FROM form_fields WHERE template_id = ? ORDER BY sort_order, id');
$fieldStmt->execute([$template['id'] ?? 0]);
$fieldRows = $fieldStmt->fetchAll();
$sectionRows = !empty($template['id']) ? fetch_repeatable_schema_for_template((int) $template['id']) : [];
$valuesInput = isset($input['values']) && is_array($input['values']) ? $input['values'] : [];
$itemsInput = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
$jsonPayload = json_encode(['values' => $valuesInput, 'items' => $itemsInput], JSON_UNESCAPED_SLASHES);
if ($jsonPayload !== false && strlen((string) $jsonPayload) > 200000) {
    $errors['values'] = 'Inspection form payload is too large.';
}
[$valueErrors, $validatedValues] = validate_inspection_values($valuesInput, $fieldRows, !$draftOnly);
[$itemErrors, $validatedItems] = validate_repeatable_items($itemsInput, $sectionRows, !$draftOnly);
$errors = array_merge($errors, $valueErrors, $itemErrors);

if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$pdo = db();
$pdo->beginTransaction();
try {
    if ($isUpdate) {
        $stmt = $pdo->prepare('UPDATE inspections SET equipment_id = ?, form_template_id = ?, inspector_id = ?, authenticator_id = ?, inspection_date = ?, next_due_date = ?, location = ?, result = ?, remarks = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([
            $equipmentId,
            (int) $template['id'],
            $inspectorId,
            $authenticatorId,
            (string) $input['inspection_date'],
            isset($input['next_due_date']) && $input['next_due_date'] !== '' ? (string) $input['next_due_date'] : null,
            $location !== '' ? $location : null,
            $result,
            $remarks !== '' ? $remarks : null,
            now_sql(),
            $inspectionId,
        ]);
        $id = $inspectionId;
    } else {
        $allocation = allocate_scoped_reference($pdo, $clientId, $categoryId, (string) $client['short_code'], (string) $template['short_code']);
        $reference = $allocation['reference'];
        $sequenceNumber = (int) $allocation['sequence_number'];
        $stmt = $pdo->prepare('INSERT INTO inspections (reference, sequence_number, client_id, equipment_id, category_id, form_template_id, inspector_id, authenticator_id, inspection_date, next_due_date, location, result, status, remarks, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $reference,
            $sequenceNumber,
            $clientId,
            $equipmentId,
            $categoryId,
            (int) $template['id'],
            $inspectorId,
            $authenticatorId,
            (string) $input['inspection_date'],
            isset($input['next_due_date']) && $input['next_due_date'] !== '' ? (string) $input['next_due_date'] : null,
            $location !== '' ? $location : null,
            $result,
            'draft',
            $remarks !== '' ? $remarks : null,
            now_sql(),
            now_sql(),
        ]);
        $id = (int) $pdo->lastInsertId();
    }

    if ($fieldRows) {
        $fieldIds = array_map(static function (array $row): int { return (int) $row['id']; }, $fieldRows);
        $deleteSql = 'DELETE FROM inspection_values WHERE inspection_id = ?';
        $deleteParams = [$id];
        if ($fieldIds) {
            $deleteSql .= ' AND field_id NOT IN (' . implode(',', array_fill(0, count($fieldIds), '?')) . ')';
            $deleteParams = array_merge($deleteParams, $fieldIds);
        }
        $pdo->prepare($deleteSql)->execute($deleteParams);
    }

    if ($validatedValues) {
        $valueStmt = $pdo->prepare('INSERT INTO inspection_values (inspection_id, field_id, value_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), updated_at = VALUES(updated_at)');
        foreach ($validatedValues as $fieldId => $value) {
            $valueStmt->execute([$id, (int) $fieldId, $value, now_sql(), now_sql()]);
        }
    }

    $pdo->prepare('DELETE FROM inspection_items WHERE inspection_id = ?')->execute([$id]);
    if ($validatedItems) {
        $itemStmt = $pdo->prepare('INSERT INTO inspection_items (inspection_id, section_key, row_index, column_key, value_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($validatedItems as $sectionKey => $rows) {
            foreach (array_values($rows) as $rowIndex => $row) {
                foreach ($row as $columnKey => $value) {
                    if ($value === '') {
                        continue;
                    }
                    $itemStmt->execute([$id, $sectionKey, $rowIndex, $columnKey, $value, now_sql(), now_sql()]);
                }
            }
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $reference = strtoupper(bin2hex(random_bytes(6)));
    error_log("Inspection persistence failure {$reference}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
    api_error($isUpdate ? "Unable to update inspection. Reference log {$reference}." : "Unable to create inspection. Reference log {$reference}.", 422);
}

audit_log((int) $user['id'], $isUpdate ? 'inspections.updated' : 'inspections.created', 'inspection', $id, ['reference' => $reference, 'sequence_number' => $sequenceNumber, 'client_id' => $clientId, 'category_id' => $categoryId, 'updated_existing' => $isUpdate]);
$stmt = db()->prepare('SELECT i.*, c.name AS client_name, c.short_code AS client_short_code, e.asset_code, e.name AS equipment_name, cat.name AS category_name, cat.short_code AS category_short_code, u.name AS inspector_name FROM inspections i JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id JOIN users u ON u.id = i.inspector_id WHERE i.id = ?');
$stmt->execute([$id]);
respond(['inspection' => $stmt->fetch()], $isUpdate ? 200 : 201);




