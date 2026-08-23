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
        $dup = db()->prepare('SELECT id FROM equipment WHERE client_id = ? AND asset_code = ? AND (? IS NULL OR id <> ?) LIMIT 1');
        $dup->execute([$clientId, $assetCode, $existingId, $existingId]);
        if ($dup->fetch()) {
            $errors['asset_code'] = 'This asset code already exists for the selected client.';
        }

        if ($serialNumber !== null) {
            $serial = db()->prepare('SELECT id FROM equipment WHERE serial_number = ? AND (? IS NULL OR id <> ?) LIMIT 1');
            $serial->execute([$serialNumber, $existingId, $existingId]);
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

require_methods(['GET', 'POST']);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_permission('equipment.view');
    $search = isset($_GET['search']) ? normalize_text_input((string) $_GET['search']) : '';
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(e.asset_code LIKE ? OR e.name LIKE ? OR e.serial_number LIKE ? OR c.name LIKE ?)';
        $needle = '%' . $search . '%';
        $params = [$needle, $needle, $needle, $needle];
    }
    $sql = 'SELECT e.*, c.name AS client_name, (SELECT MAX(i.next_due_date) FROM inspections i WHERE i.equipment_id = e.id) AS next_due_date, (SELECT i.inspection_date FROM inspections i WHERE i.equipment_id = e.id AND i.status IN (\'approved\',\'issued\',\'expired\',\'revoked\') ORDER BY i.inspection_date DESC, i.id DESC LIMIT 1) AS previous_examination_date FROM equipment e JOIN clients c ON c.id = e.client_id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY e.updated_at DESC LIMIT 300';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    respond(['equipment' => $stmt->fetchAll()]);
}

$user = require_permission('equipment.manage');
require_csrf();
$input = json_input();
[$errors, $payload] = validate_equipment_payload($input);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}
$stmt = db()->prepare('INSERT INTO equipment (client_id, asset_code, name, manufacturer, model, serial_number, safe_working_load, location, manufacture_date, manufacture_date_value, manufacture_date_precision, reference_standard, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    $payload['client_id'],
    $payload['asset_code'],
    $payload['name'],
    $payload['manufacturer'],
    $payload['model'],
    $payload['serial_number'],
    $payload['safe_working_load'],
    $payload['location'],
    $payload['manufacture_date'],
    $payload['manufacture_date_value'],
    $payload['manufacture_date_precision'],
    $payload['reference_standard'],
    $payload['status'],
    now_sql(),
    now_sql(),
]);
$id = (int) db()->lastInsertId();
audit_log((int) $user['id'], 'equipment.created', 'equipment', $id);
$stmt = db()->prepare('SELECT e.*, c.name AS client_name FROM equipment e JOIN clients c ON c.id = e.client_id WHERE e.id = ?');
$stmt->execute([$id]);
respond(['equipment' => $stmt->fetch()], 201);
