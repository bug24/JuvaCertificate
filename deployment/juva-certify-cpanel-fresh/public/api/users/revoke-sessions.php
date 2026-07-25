<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$actor = require_permission('users.manage');
require_csrf();
$input = json_input();
$errors = validate_required($input, ['id']);
if ($errors) {
    api_error('User id is required.', 422, $errors);
}
$id = (int) $input['id'];
$stmt = db()->prepare('UPDATE remember_tokens SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL');
$now = now_sql();
$stmt->execute([$now, $id]);
$revokeActive = db()->prepare('UPDATE users SET sessions_revoked_at = ?, updated_at = ? WHERE id = ?');
$revokeActive->execute([$now, $now, $id]);
audit_log((int) $actor['id'], 'users.sessions_revoked', 'user', $id);
respond(['message' => 'Remembered sessions revoked.']);
