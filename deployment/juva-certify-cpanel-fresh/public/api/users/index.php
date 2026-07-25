<?php
require_once __DIR__ . '/../_bootstrap.php';

require_methods(['GET', 'POST']);
$user = require_permission('users.manage');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    $role = isset($_GET['role']) ? trim((string) $_GET['role']) : '';
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
        $needle = '%' . $search . '%';
        $params[] = $needle;
        $params[] = $needle;
        $params[] = $needle;
    }
    if ($status !== '') {
        $where[] = 'u.status = ?';
        $params[] = $status;
    }
    if ($role !== '') {
        $where[] = 'r.slug = ?';
        $params[] = $role;
    }
    $sql = 'SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.permissions_json, c.name AS client_name FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN clients c ON c.id = u.client_id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY u.created_at DESC LIMIT 200';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $users = [];
    foreach ($stmt->fetchAll() as $row) {
        $item = public_user($row);
        $item['client_name'] = $row['client_name'];
        $users[] = $item;
    }
    respond(['users' => $users]);
}

require_csrf();
$input = json_input();
$errors = validate_required($input, ['name', 'email', 'username', 'role_id']);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$roleStmt = db()->prepare('SELECT * FROM roles WHERE id = ? LIMIT 1');
$roleStmt->execute([(int) $input['role_id']]);
$role = $roleStmt->fetch();
if (!$role) {
    api_error('Selected role does not exist.', 422, ['role_id' => 'Invalid role.']);
}
if ($role['slug'] === 'client' && empty($input['client_id'])) {
    api_error('Client users must be linked to a client.', 422, ['client_id' => 'Select a client.']);
}

$stmt = db()->prepare('INSERT INTO users (role_id, client_id, name, email, username, password_hash, phone, qualification, status, invited_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    (int) $input['role_id'],
    !empty($input['client_id']) ? (int) $input['client_id'] : null,
    trim((string) $input['name']),
    strtolower(trim((string) $input['email'])),
    trim((string) $input['username']),
    '',
    isset($input['phone']) ? trim((string) $input['phone']) : null,
    isset($input['qualification']) ? trim((string) $input['qualification']) : null,
    'invited',
    now_sql(),
    now_sql(),
    now_sql(),
]);
$newId = (int) db()->lastInsertId();
$token = create_auth_token($newId, 'invite', 7 * 86400, (int) $user['id']);
$link = app_url('/reset-password?token=' . urlencode($token));
send_app_mail(strtolower(trim((string) $input['email'])), 'Your JUVA Certify Manager invitation', "You have been invited to JUVA Certify Manager. Set your password with this link within 7 days.\n\n{$link}");
audit_log((int) $user['id'], 'users.invited', 'user', $newId, ['role' => $role['slug']]);
respond(['user' => public_user(fetch_user($newId)), 'message' => 'Invitation created and sent.'], 201);
