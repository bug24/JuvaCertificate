<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$input = json_input();
$errors = validate_required($input, ['token', 'password']);
$password = isset($input['password']) ? (string) $input['password'] : '';
if ($password !== '' && strlen($password) < 10) {
    $errors['password'] = 'Use at least 10 characters.';
}
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$token = find_auth_token((string) $input['token'], 'password_reset');
if (!$token) {
    $token = find_auth_token((string) $input['token'], 'invite');
}
if (!$token) {
    api_error('This link is invalid or expired.', 401);
}

$stmt = db()->prepare('UPDATE users SET password_hash = ?, status = ?, email_verified_at = COALESCE(email_verified_at, ?), sessions_revoked_at = ?, updated_at = ? WHERE id = ?');
$now = now_sql();
$stmt->execute([password_hash($password, PASSWORD_DEFAULT), 'active', $now, $now, $now, $token['user_id']]);
$revoke = db()->prepare('UPDATE remember_tokens SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL');
$revoke->execute([$now, $token['user_id']]);
consume_auth_token((int) $token['id']);
audit_log((int) $token['user_id'], $token['type'] === 'invite' ? 'auth.invitation_accepted' : 'auth.password_reset_completed', 'user', (int) $token['user_id']);
respond(['message' => 'Password updated. You can now sign in.']);
