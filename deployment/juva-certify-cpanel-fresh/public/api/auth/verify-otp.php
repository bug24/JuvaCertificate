<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$input = json_input();
$errors = validate_required($input, ['challenge', 'code']);
if ($errors) {
    api_error('Please enter the verification code.', 422, $errors);
}

$challenge = trim((string) $input['challenge']);
$code = trim((string) $input['code']);
$remember = !empty($input['remember']);

rate_limit_or_fail('otp', $challenge, 6, 900);
$stmt = db()->prepare('SELECT * FROM otp_challenges WHERE challenge_token_hash = ? AND consumed_at IS NULL AND expires_at > ? LIMIT 1');
$stmt->execute([hash_secret($challenge), now_sql()]);
$row = $stmt->fetch();

if (!$row || !password_verify($code, $row['code_hash'])) {
    record_auth_attempt('otp', $challenge, false, 'invalid_code');
    if ($row) {
        $update = db()->prepare('UPDATE otp_challenges SET attempts = attempts + 1 WHERE id = ?');
        $update->execute([$row['id']]);
        audit_log((int) $row['user_id'], 'auth.otp_failed', 'user', (int) $row['user_id']);
    }
    api_error('Invalid or expired verification code.', 401);
}

record_auth_attempt('otp', $challenge, true, null);
$consume = db()->prepare('UPDATE otp_challenges SET consumed_at = ? WHERE id = ?');
$consume->execute([now_sql(), $row['id']]);
$user = fetch_user((int) $row['user_id']);
if (!$user || $user['status'] !== 'active') {
    api_error('This account is not active. Contact an administrator.', 403);
}

start_auth_session((int) $user['id']);
if ($remember) {
    set_remember_cookie((int) $user['id']);
}
$stmt = db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?');
$stmt->execute([now_sql(), $user['id']]);
audit_log((int) $user['id'], 'auth.otp_success', 'user', (int) $user['id']);
audit_log((int) $user['id'], 'auth.login_success', 'user', (int) $user['id'], ['otp' => true]);
respond(['user' => public_user($user), 'csrf_token' => csrf_token()]);
