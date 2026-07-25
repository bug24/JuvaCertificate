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
        $upperCode = strtoupper($registrationCode);
        $stmt = db()->prepare('SELECT id FROM clients WHERE registration_code = ? AND (? IS NULL OR id <> ?) LIMIT 1');
        $stmt->execute([$upperCode, $existingId, $existingId]);
        if ($stmt->fetch()) {
            $errors['registration_code'] = 'This registration code already exists.';
        }

        $shortStmt = db()->prepare('SELECT id FROM clients WHERE short_code = ? AND (? IS NULL OR id <> ?) LIMIT 1');
        $shortStmt->execute([$shortCode, $existingId, $existingId]);
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

require_methods(['GET', 'POST']);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_permission('clients.view');
    $search = isset($_GET['search']) ? normalize_text_input((string) $_GET['search']) : '';
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(c.name LIKE ? OR c.registration_code LIKE ? OR c.short_code LIKE ? OR c.contact_person LIKE ? OR c.email LIKE ?)';
        $needle = '%' . $search . '%';
        $params = [$needle, $needle, $needle, $needle, $needle];
    }
    $sql = 'SELECT c.*, (SELECT COUNT(*) FROM equipment e WHERE e.client_id = c.id) AS equipment_count FROM clients c';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY c.name LIMIT 300';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    respond(['clients' => $stmt->fetchAll()]);
}

$user = require_permission('clients.manage');
require_csrf();
$input = json_input();
[$errors, $payload] = validate_client_payload($input);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}
$stmt = db()->prepare('INSERT INTO clients (registration_code, short_code, name, contact_person, email, phone, address, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    $payload['registration_code'],
    $payload['short_code'],
    $payload['name'],
    $payload['contact_person'],
    $payload['email'],
    $payload['phone'],
    $payload['address'],
    $payload['status'],
    now_sql(),
    now_sql(),
]);
$id = (int) db()->lastInsertId();
audit_log((int) $user['id'], 'clients.created', 'client', $id, ['short_code' => $payload['short_code']]);
$stmt = db()->prepare('SELECT * FROM clients WHERE id = ?');
$stmt->execute([$id]);
respond(['client' => $stmt->fetch()], 201);
