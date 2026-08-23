<?php
require_once __DIR__ . '/../_bootstrap.php';

function validate_equipment_payload(array $input, ?int $existingId = null): array
{
    $errors = [];

    $clientId = isset($input['client_id']) ? (int) $input['client_id'] : 0;
    $assetCode = strtoupper(normalize_text_input($input['asset_code'] ?? ''));
    $name = normalize_text_input($input['name'] ?? '');
    $manufacturer = array_key_exists('manufacturer', $input) ? normalize_text_input((string) $input['manufacturer']) : null;
    $model = array_key_exists('model', $input) ? normalize_text_input((string) $input['model']) : null;
    $serialNumber = array_key_exists('serial_number', $input) ? strtoupper(normalize_text_input((string) $input['serial_number'])) : null;
    $safeWorkingLoad = array_key_exists('safe_working_load', $input) ? normalize_text_input((string) $input['safe_working_load']) : null;
    $location = array_key_exists('location', $input) ? normalize_text_input((string) $input['location']) : null;
    $referenceStandard = array_key_exists('reference_standard', $input) ? normalize_text_input((string) $input['reference_standard']) : null;
    $manufactureDateValue = array_key_exists('manufacture_date_value', $input) ? trim((string) $input['manufacture_date_value']) : (array_key_exists('manufacture_date', $input) ? trim((string) $input['manufacture_date']) : null);
    $manufactureDatePrecision = $manufactureDateValue !== null && $manufactureDateValue !== '' ? partial_date_precision($manufactureDateValue) : null;
    $manufactureDate = $manufactureDatePrecision === 'day' ? $manufactureDateValue : null;
    $status = isset($input['status']) ? (string) $input['status'] : 'active';

    if ($clientId <= 0) {
        $errors['client_id'] = 'Select a valid client.';
    } else {
        $clientStmt = db()->prepare('SELECT id FROM clients WHERE id = ? LIMIT 1');
        $clientStmt->execute([$clientId]);
        if (!$clientStmt->fetch()) {
            $errors['client_id'] = 'Selected client was not found.';
        }
    }

    if ($assetCode === '') {
        $errors['asset_code'] = 'This field is required.';
    } elseif (!preg_match('/^[A-Z0-9][A-Z0-9._\/-]{1,119}$/', $assetCode)) {
        $errors['asset_code'] = 'Use 2-120 letters, numbers, dots, slashes, underscores or dashes.';
    }

    if ($name === '') {
        $errors['name'] = 'This field is required.';
    } elseif (text_length($name) > 200 || contains_markup($name)) {
        $errors['name'] = 'Use plain text up to 200 characters.';
    }

    foreach ([
        'manufacturer' => [$manufacturer, 160],
        'model' => [$model, 120],
        'location' => [$location, 255],
        'reference_standard' => [$referenceStandard, 200],
    ] as $field => [$value, $max]) {
        if ($value !== null && $value !== '') {
            if (text_length($value) > $max || contains_markup($value)) {
                $errors[$field] = 'Use plain text only.';
            }
        }
    }

    if ($serialNumber !== null && $serialNumber !== '') {
        if (!preg_match('/^[A-Z0-9][A-Z0-9._\/-]{1,119}$/', $serialNumber)) {
            $errors['serial_number'] = 'Use 2-120 letters, numbers, dots, slashes, underscores or dashes.';
        }
    } else {
        $serialNumber = null;
    }

    if ($safeWorkingLoad !== null && $safeWorkingLoad !== '') {
        if (text_length($safeWorkingLoad) > 100 || contains_markup($safeWorkingLoad) || !preg_match('/^(?=.*\d)[A-Z0-9 .,:()\/-]+$/i', $safeWorkingLoad)) {
            $errors['safe_working_load'] = 'Enter a valid SWL or capacity value.';
        }
    } else {
        $safeWorkingLoad = null;
    }

    if ($manufactureDateValue !== null && $manufactureDateValue !== '') {
        if (!valid_partial_date($manufactureDateValue)) {
            $errors['manufacture_date_value'] = 'Use a valid full date, month and year, or year.';
        } elseif (isset($input['manufacture_date_precision']) && (string) $input['manufacture_date_precision'] !== $manufactureDatePrecision) {
            $errors['manufacture_date_precision'] = 'Date precision does not match the entered value.';
        }
    } else {
        $manufactureDateValue = null;
        $manufactureDatePrecision = null;
        $manufactureDate = null;
    }

    if (!in_array($status, ['active', 'out_of_service', 'retired'], true)) {
        $errors['status'] = 'Invalid equipment status.';
    }

    if (!$errors) {
        $dup = db()->prepare('SELECT id FROM equipment WHERE client_id = ? AND asset_code = ? AND id <> ? LIMIT 1');
        $dup->execute([$clientId, $assetCode, $existingId]);
        if ($dup->fetch()) {
            $errors['asset_code'] = 'This asset code already exists for the selected client.';
        }

        if ($serialNumber !== null) {
            $serial = db()->prepare('SELECT id FROM equipment WHERE serial_number = ? AND id <> ? LIMIT 1');
            $serial->execute([$serialNumber, $existingId]);
            if ($serial->fetch()) {
                $errors['serial_number'] = 'This serial number is already assigned to another equipment record.';
            }
        }
    }

    return [$errors, [
        'client_id' => $clientId,
        'asset_code' => $assetCode,
        'name' => $name,
        'manufacturer' => $manufacturer !== '' ? $manufacturer : null,
        'model' => $model !== '' ? $model : null,
        'serial_number' => $serialNumber,
        'safe_working_load' => $safeWorkingLoad,
        'location' => $location !== '' ? $location : null,
        'reference_standard' => $referenceStandard !== '' ? $referenceStandard : null,
        'manufacture_date' => $manufactureDate,
        'manufacture_date_value' => $manufactureDateValue,
        'manufacture_date_precision' => $manufactureDatePrecision,
        'status' => $status,
    ]];
}

require_methods(['GET', 'PATCH', 'DELETE']);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    api_error('Equipment id is required.', 422, ['id' => 'Required.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_permission('equipment.view');
} else {
    $user = require_permission('equipment.manage');
    require_csrf();
}

$stmt = db()->prepare('SELECT e.*, c.name AS client_name FROM equipment e JOIN clients c ON c.id = e.client_id WHERE e.id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    api_error('Equipment not found.', 404);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $count = db()->prepare('SELECT COUNT(*) FROM inspections WHERE equipment_id = ?');
    $count->execute([$id]);
    respond(['equipment' => $row, 'dependencies' => ['inspections' => (int) $count->fetchColumn()]]);
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $count = db()->prepare('SELECT COUNT(*) FROM inspections WHERE equipment_id = ?'); $count->execute([$id]);
    $inspections = (int) $count->fetchColumn();
    if ($inspections > 0) {
        db()->prepare("UPDATE equipment SET status = 'retired', updated_at = ? WHERE id = ?")->execute([now_sql(), $id]);
        audit_log((int) $user['id'], 'equipment.retired', 'equipment', $id, ['inspections' => $inspections]);
        respond(['action' => 'retired', 'message' => 'Equipment has inspection history and was retired.', 'dependencies' => ['inspections' => $inspections]]);
    }
    db()->prepare('DELETE FROM equipment WHERE id = ?')->execute([$id]);
    audit_log((int) $user['id'], 'equipment.deleted', 'equipment', $id);
    respond(['action' => 'deleted', 'message' => 'Unused equipment was permanently deleted.']);
}

$input = json_input();
$payload = [
    'client_id' => $input['client_id'] ?? $row['client_id'],
    'asset_code' => $input['asset_code'] ?? $row['asset_code'],
    'name' => $input['name'] ?? $row['name'],
    'manufacturer' => array_key_exists('manufacturer', $input) ? $input['manufacturer'] : $row['manufacturer'],
    'model' => array_key_exists('model', $input) ? $input['model'] : $row['model'],
    'serial_number' => array_key_exists('serial_number', $input) ? $input['serial_number'] : $row['serial_number'],
    'safe_working_load' => array_key_exists('safe_working_load', $input) ? $input['safe_working_load'] : $row['safe_working_load'],
    'location' => array_key_exists('location', $input) ? $input['location'] : $row['location'],
    'manufacture_date' => array_key_exists('manufacture_date', $input) ? $input['manufacture_date'] : $row['manufacture_date'],
    'manufacture_date_value' => array_key_exists('manufacture_date_value', $input) ? $input['manufacture_date_value'] : ($row['manufacture_date_value'] ?? $row['manufacture_date']),
    'manufacture_date_precision' => array_key_exists('manufacture_date_precision', $input) ? $input['manufacture_date_precision'] : ($row['manufacture_date_precision'] ?? null),
    'reference_standard' => array_key_exists('reference_standard', $input) ? $input['reference_standard'] : $row['reference_standard'],
    'status' => $input['status'] ?? $row['status'],
];
if ((int) $payload['client_id'] !== (int) $row['client_id']) {
    $history = db()->prepare('SELECT COUNT(*) FROM inspections WHERE equipment_id = ?'); $history->execute([$id]);
    if ((int) $history->fetchColumn() > 0) api_error('Equipment with inspection history cannot be reassigned to another client.', 422, ['client_id' => 'Retain the original client to preserve certificate history.']);
}
[$errors, $validated] = validate_equipment_payload($payload, $id);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$stmt = db()->prepare('UPDATE equipment SET client_id = ?, asset_code = ?, name = ?, manufacturer = ?, model = ?, serial_number = ?, safe_working_load = ?, location = ?, manufacture_date = ?, manufacture_date_value = ?, manufacture_date_precision = ?, reference_standard = ?, status = ?, updated_at = ? WHERE id = ?');
$stmt->execute([
    $validated['client_id'],
    $validated['asset_code'],
    $validated['name'],
    $validated['manufacturer'],
    $validated['model'],
    $validated['serial_number'],
    $validated['safe_working_load'],
    $validated['location'],
    $validated['manufacture_date'],
    $validated['manufacture_date_value'],
    $validated['manufacture_date_precision'],
    $validated['reference_standard'],
    $validated['status'],
    now_sql(),
    $id,
]);
audit_log((int) $user['id'], 'equipment.updated', 'equipment', $id);
$stmt = db()->prepare('SELECT e.*, c.name AS client_name FROM equipment e JOIN clients c ON c.id = e.client_id WHERE e.id = ?');
$stmt->execute([$id]);
respond(['equipment' => $stmt->fetch()]);
