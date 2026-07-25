<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$actor = require_permission('users.manage');
require_csrf();
$input = json_input();
$errors = validate_required($input, ['id', 'status']);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}
$id = (int) $input['id'];
$status = (string) $input['status'];
if (!in_array($status, ['active', 'suspended', 'disabled'], true)) {
    api_error('Invalid account status.', 422, ['status' => 'Invalid status.']);
}
$target = fetch_user($id);
if (!$target) {
    api_error('User not found.', 404);
}
if ($target['role_slug'] === 'super-admin' && $target['status'] === 'active' && $status !== 'active') {
    $stmt = db()->prepare("SELECT COUNT(*) AS total FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'super-admin' AND u.status = 'active' AND u.id <> ?");
    $stmt->execute([$id]);
    if ((int) $stmt->fetch()['total'] === 0) {
        api_error('The final active Super Admin cannot be suspended or disabled.', 403);
    }
}
$stmt = db()->prepare('UPDATE users SET status = ?, updated_at = ? WHERE id = ?');
$stmt->execute([$status, now_sql(), $id]);
if ($status !== 'active') {
    $revoke = db()->prepare('UPDATE remember_tokens SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL');
    $revoke->execute([now_sql(), $id]);
}
audit_log((int) $actor['id'], 'users.status_changed', 'user', $id, ['status' => $status]);
respond(['user' => public_user(fetch_user($id))]);
