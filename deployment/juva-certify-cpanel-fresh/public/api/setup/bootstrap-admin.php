<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$input = json_input();
global $config;
if (empty($config['setup_key'])) {
    api_error('Setup is disabled.', 403);
}
if (empty($input['setup_key']) || !hash_equals((string) $config['setup_key'], (string) $input['setup_key'])) {
    api_error('Invalid setup key.', 403);
}
$count = db()->query('SELECT COUNT(*) AS total FROM users')->fetch();
if ((int) $count['total'] > 0) {
    api_error('Setup has already been completed.', 403);
}
$errors = validate_required($input, ['name', 'email', 'username', 'password']);
if (!empty($input['password']) && strlen((string) $input['password']) < 10) {
    $errors['password'] = 'Use at least 10 characters.';
}
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}
$stmt = db()->prepare("SELECT id FROM roles WHERE slug = 'super-admin' LIMIT 1");
$stmt->execute();
$role = $stmt->fetch();
if (!$role) {
    api_error('Super Admin role is missing. Import the latest schema first.', 500);
}
$insert = db()->prepare('INSERT INTO users (role_id, name, email, username, password_hash, status, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$insert->execute([
    (int) $role['id'],
    trim((string) $input['name']),
    strtolower(trim((string) $input['email'])),
    trim((string) $input['username']),
    password_hash((string) $input['password'], PASSWORD_DEFAULT),
    'active',
    now_sql(),
    now_sql(),
    now_sql(),
]);
$id = (int) db()->lastInsertId();
audit_log($id, 'setup.super_admin_created', 'user', $id);
respond(['user' => public_user(fetch_user($id)), 'message' => 'First Super Admin created. Remove setup_key from config.local.php after confirming login.'], 201);
