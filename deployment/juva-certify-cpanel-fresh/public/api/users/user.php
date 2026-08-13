<?php
require_once __DIR__ . '/../_bootstrap.php';

require_methods(['GET', 'PATCH']);
$user = require_permission('users.manage');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    api_error('User id is required.', 422, ['id' => 'Required.']);
}
$target = fetch_user($id);
if (!$target) {
    api_error('User not found.', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    respond(['user' => public_user($target)]);
}

require_csrf();
$input = json_input();
$roleId = isset($input['role_id']) ? (int) $input['role_id'] : (int) $target['role_id'];
$roleStmt = db()->prepare('SELECT * FROM roles WHERE id = ? LIMIT 1');
$roleStmt->execute([$roleId]);
$role = $roleStmt->fetch();
if (!$role) {
    api_error('Selected role does not exist.', 422, ['role_id' => 'Invalid role.']);
}
if ($target['role_slug'] === 'super-admin' && $role['slug'] !== 'super-admin') {
    $stmt = db()->prepare("SELECT COUNT(*) AS total FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'super-admin' AND u.status = 'active' AND u.id <> ?");
    $stmt->execute([$id]);
    if ((int) $stmt->fetch()['total'] === 0) {
        api_error('The final active Super Admin cannot be demoted.', 403);
    }
}
if ($role['slug'] === 'client' && empty($input['client_id']) && empty($target['client_id'])) {
    api_error('Client users must be linked to a client.', 422, ['client_id' => 'Select a client.']);
}

$stmt = db()->prepare('UPDATE users SET role_id = ?, client_id = ?, name = ?, email = ?, username = ?, phone = ?, qualification = ?, job_title = ?, professional_memberships = ?, certificate_signing_role = ?, updated_at = ? WHERE id = ?');
$stmt->execute([
    $roleId,
    array_key_exists('client_id', $input) && $input['client_id'] !== null && $input['client_id'] !== '' ? (int) $input['client_id'] : $target['client_id'],
    isset($input['name']) ? trim((string) $input['name']) : $target['name'],
    isset($input['email']) ? strtolower(trim((string) $input['email'])) : $target['email'],
    isset($input['username']) ? trim((string) $input['username']) : $target['username'],
    array_key_exists('phone', $input) ? trim((string) $input['phone']) : $target['phone'],
    array_key_exists('qualification', $input) ? trim((string) $input['qualification']) : $target['qualification'],
    array_key_exists('job_title', $input) ? trim((string) $input['job_title']) : $target['job_title'],
    array_key_exists('professional_memberships', $input) ? trim((string) $input['professional_memberships']) : $target['professional_memberships'],
    array_key_exists('certificate_signing_role', $input) ? trim((string) $input['certificate_signing_role']) : $target['certificate_signing_role'],
    now_sql(),
    $id,
]);
audit_log((int) $user['id'], 'users.updated', 'user', $id);
respond(['user' => public_user(fetch_user($id))]);
