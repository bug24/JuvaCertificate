<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$input = json_input();
$errors = validate_required($input, ['identifier', 'password']);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$identifier = trim((string) $input['identifier']);
$password = (string) $input['password'];
$remember = !empty($input['remember']);

rate_limit_or_fail('login', $identifier, 8, 900);
$user = fetch_user_by_identifier($identifier);

if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    record_auth_attempt('login', $identifier, false, 'invalid_credentials');
    audit_log($user ? (int) $user['id'] : null, 'auth.login_failed', 'user', $user ? (int) $user['id'] : null, ['reason' => 'invalid_credentials']);
    api_error('Invalid login details.', 401);
}

if ($user['status'] !== 'active') {
    record_auth_attempt('login', $identifier, false, 'inactive_account');
    audit_log((int) $user['id'], 'auth.login_failed', 'user', (int) $user['id'], ['reason' => 'inactive_account']);
    api_error('This account is not active. Contact an administrator.', 403);
}

if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
}

record_auth_attempt('login', $identifier, true, null);

if (privileged_role($user['role_slug'])) {
    $challengeAndCode = create_otp_challenge((int) $user['id']);
    list($challenge, $code) = explode(':', $challengeAndCode, 2);
    send_app_mail($user['email'], 'JUVA Certify Manager login code', "Your JUVA Certify Manager login code is {$code}. It expires in 10 minutes. If this was not you, contact the system administrator immediately.");
    audit_log((int) $user['id'], 'auth.otp_sent', 'user', (int) $user['id']);
    respond(['otp_required' => true, 'challenge' => $challenge, 'message' => 'A verification code has been sent to your email.']);
}

start_auth_session((int) $user['id']);
if ($remember) {
    set_remember_cookie((int) $user['id']);
}
$stmt = db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?');
$stmt->execute([now_sql(), $user['id']]);
audit_log((int) $user['id'], 'auth.login_success', 'user', (int) $user['id']);
respond(['user' => public_user(fetch_user((int) $user['id'])), 'csrf_token' => csrf_token()]);
