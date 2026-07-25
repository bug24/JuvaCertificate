<?php
require_once __DIR__ . '/../_bootstrap.php';

function validate_client_payload(array $input, ?int $existingId = null): array
{
    $errors = [];

    $registrationCode = normalize_text_input($input['registration_code'] ?? '');
    $shortCode = strtoupper(preg_replace('/[^A-Z0-9]+/', '', normalize_text_input($input['short_code'] ?? '')) ?? '');
    $name = normalize_text_input($input['name'] ?? '');
    $contactPerson = array_key_exists('contact_person', $input) ? normalize_text_input((string) $input['contact_person']) : null;
    $email = array_key_exists('email', $input) ? strtolower(normalize_text_input((string) $input['email'])) : null;
    $phone = array_key_exists('phone', $input) ? normalize_text_input((string) $input['phone']) : null;
    $address = array_key_exists('address', $input) ? normalize_text_input((string) $input['address'], true) : null;
    $status = isset($input['status']) ? (string) $input['status'] : 'active';

    if ($registrationCode === '') {
        $errors['registration_code'] = 'This field is required.';
    } elseif (!preg_match('/^[A-Z0-9][A-Z0-9._\/-]{1,99}$/', strtoupper($registrationCode))) {
        $errors['registration_code'] = 'Use 2-100 letters, numbers, dots, slashes, underscores or dashes.';
    }

    if ($shortCode === '') {
        $errors['short_code'] = 'Client short code is required.';
    } elseif (!preg_match('/^[A-Z0-9]{2,12}$/', $shortCode)) {
        $errors['short_code'] = 'Use 2-12 uppercase letters or numbers.';
    }

    if ($name === '') {
        $errors['name'] = 'This field is required.';
    } elseif (text_length($name) > 200 || contains_markup($name)) {
        $errors['name'] = 'Use plain text up to 200 characters.';
    }

    foreach ([
        'contact_person' => [$contactPerson, 160],
        'phone' => [$phone, 50],
    ] as $field => [$value, $max]) {
        if ($value !== null && $value !== '') {
            if (text_length($value) > $max || contains_markup($value)) {
                $errors[$field] = 'Use plain text only.';
            }
        }
    }

    if ($email !== null && $email !== '') {
        if (text_length($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
    } else {
        $email = null;
    }

    if ($address !== null && $address !== '') {
        if (text_length($address) > 1000 || contains_markup($address)) {
            $errors['address'] = 'Use plain text only.';
        }
    } else {
        $address = null;
    }

    if (!in_array($status, ['active', 'review', 'inactive'], true)) {
        $errors['status'] = 'Invalid client status.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM clients WHERE registration_code = ? AND id <> ? LIMIT 1');
        $stmt->execute([strtoupper($registrationCode), $existingId]);
        if ($stmt->fetch()) {
            $errors['registration_code'] = 'This registration code already exists.';
        }

        $shortStmt = db()->prepare('SELECT id FROM clients WHERE short_code = ? AND id <> ? LIMIT 1');
        $shortStmt->execute([$shortCode, $existingId]);
        if ($shortStmt->fetch()) {
            $errors['short_code'] = 'This short code already exists.';
        }
    }

    return [$errors, [
        'registration_code' => strtoupper($registrationCode),
        'short_code' => $shortCode,
        'name' => $name,
        'contact_person' => $contactPerson !== '' ? $contactPerson : null,
        'email' => $email,
        'phone' => $phone !== '' ? $phone : null,
        'address' => $address !== '' ? $address : null,
        'status' => $status,
    ]];
}

require_methods(['GET', 'PATCH', 'DELETE']);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    api_error('Client id is required.', 422, ['id' => 'Required.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_permission('clients.view');
} else {
    $user = require_permission('clients.manage');
    require_csrf();
}

$stmt = db()->prepare('SELECT * FROM clients WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$client = $stmt->fetch();
if (!$client) {
    api_error('Client not found.', 404);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $counts = [];
    foreach ([
        'equipment' => 'SELECT COUNT(*) FROM equipment WHERE client_id = ?',
        'users' => 'SELECT COUNT(*) FROM users WHERE client_id = ?',
        'inspections' => 'SELECT COUNT(*) FROM inspections WHERE client_id = ?',
        'sequences' => 'SELECT COUNT(*) FROM certificate_sequences WHERE client_id = ?',
    ] as $key => $sql) {
        $count = db()->prepare($sql); $count->execute([$id]); $counts[$key] = (int) $count->fetchColumn();
    }
    respond(['client' => $client, 'dependencies' => $counts]);
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $counts = [];
    foreach ([
        'equipment' => 'SELECT COUNT(*) FROM equipment WHERE client_id = ?',
        'users' => 'SELECT COUNT(*) FROM users WHERE client_id = ?',
        'inspections' => 'SELECT COUNT(*) FROM inspections WHERE client_id = ?',
        'sequences' => 'SELECT COUNT(*) FROM certificate_sequences WHERE client_id = ?',
    ] as $key => $sql) {
        $count = db()->prepare($sql); $count->execute([$id]); $counts[$key] = (int) $count->fetchColumn();
    }
    if (array_sum($counts) > 0) {
        db()->prepare("UPDATE clients SET status = 'inactive', updated_at = ? WHERE id = ?")->execute([now_sql(), $id]);
        audit_log((int) $user['id'], 'clients.archived', 'client', $id, ['dependencies' => $counts]);
        respond(['action' => 'archived', 'message' => 'Client has linked history and was deactivated.', 'dependencies' => $counts]);
    }
    db()->prepare('DELETE FROM clients WHERE id = ?')->execute([$id]);
    audit_log((int) $user['id'], 'clients.deleted', 'client', $id);
    respond(['action' => 'deleted', 'message' => 'Unused client was permanently deleted.']);
}

$input = json_input();
$payload = [
    'registration_code' => $input['registration_code'] ?? $client['registration_code'],
    'short_code' => $input['short_code'] ?? $client['short_code'],
    'name' => $input['name'] ?? $client['name'],
    'contact_person' => array_key_exists('contact_person', $input) ? $input['contact_person'] : $client['contact_person'],
    'email' => array_key_exists('email', $input) ? $input['email'] : $client['email'],
    'phone' => array_key_exists('phone', $input) ? $input['phone'] : $client['phone'],
    'address' => array_key_exists('address', $input) ? $input['address'] : $client['address'],
    'status' => $input['status'] ?? $client['status'],
];
[$errors, $validated] = validate_client_payload($payload, $id);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$stmt = db()->prepare('UPDATE clients SET registration_code = ?, short_code = ?, name = ?, contact_person = ?, email = ?, phone = ?, address = ?, status = ?, updated_at = ? WHERE id = ?');
$stmt->execute([
    $validated['registration_code'],
    $validated['short_code'],
    $validated['name'],
    $validated['contact_person'],
    $validated['email'],
    $validated['phone'],
    $validated['address'],
    $validated['status'],
    now_sql(),
    $id,
]);
audit_log((int) $user['id'], 'clients.updated', 'client', $id, ['short_code' => $validated['short_code']]);
$stmt = db()->prepare('SELECT * FROM clients WHERE id = ?');
$stmt->execute([$id]);
respond(['client' => $stmt->fetch()]);
