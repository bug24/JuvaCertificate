<?php

declare(strict_types=1);

ob_start();

final class LoginMailException extends RuntimeException {}

function login_reference_id(): string
{
    try { return strtoupper(substr(bin2hex(random_bytes(8)), 0, 12)); }
    catch (Throwable $error) { return strtoupper(substr(hash('sha256', uniqid('login', true)), 0, 12)); }
}

function login_log_failure(Throwable $error, string $referenceId, ?int $userId): void
{
    $line = sprintf("[%s] login %s user=%s %s: %s in %s:%d\n%s\n", gmdate('c'), $referenceId, $userId === null ? 'unknown' : (string) $userId, get_class($error), $error->getMessage(), $error->getFile(), $error->getLine(), $error->getTraceAsString());
    error_log($line);
    try {
        $directory = function_exists('ensure_private_storage_dir') ? ensure_private_storage_dir('logs') : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage-private' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($directory)) @mkdir($directory, 0770, true);
        if (is_dir($directory) && is_writable($directory)) file_put_contents($directory . DIRECTORY_SEPARATOR . 'login-error.log', $line, FILE_APPEND | LOCK_EX);
    } catch (Throwable $loggingError) { error_log('Unable to write login log ' . $referenceId . ': ' . $loggingError->getMessage()); }
}

function login_json_error(string $message, string $referenceId, int $status = 500): void
{
    while (ob_get_level() > 0) ob_end_clean();
    if (!headers_sent()) { header('Content-Type: application/json; charset=utf-8'); header('X-Content-Type-Options: nosniff'); http_response_code($status); }
    echo json_encode(['data' => null, 'error' => $message, 'validation' => null, 'reference_id' => $referenceId], JSON_UNESCAPED_SLASHES);
    exit;
}

$loginUserId = null;
$previousErrorHandler = set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once __DIR__ . '/../_bootstrap.php';
    require_method('POST');
    $input = json_input();
    $errors = validate_required($input, ['identifier', 'password']);
    if ($errors) api_error('Please correct the highlighted fields.', 422, $errors);

    $identifier = trim((string) $input['identifier']);
    $password = (string) $input['password'];
    $remember = !empty($input['remember']);
    rate_limit_or_fail('login', $identifier, 8, 900);
    $user = fetch_user_by_identifier($identifier);
    $loginUserId = $user ? (int) $user['id'] : null;

    if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        record_auth_attempt('login', $identifier, false, 'invalid_credentials');
        audit_log($loginUserId, 'auth.login_failed', 'user', $loginUserId, ['reason' => 'invalid_credentials']);
        api_error('Invalid login details.', 401);
    }
    if ((string) $user['status'] !== 'active') {
        record_auth_attempt('login', $identifier, false, 'inactive_account');
        audit_log($loginUserId, 'auth.login_failed', 'user', $loginUserId, ['reason' => 'inactive_account']);
        api_error('This account is not active. Contact an administrator.', 403);
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $statement = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $statement->execute([password_hash($password, PASSWORD_DEFAULT), $loginUserId]);
    }
    $password = '';
    record_auth_attempt('login', $identifier, true, null);

    if (privileged_role((string) $user['role_slug'])) {
        $challengeAndCode = create_otp_challenge($loginUserId);
        list($challenge, $code) = explode(':', $challengeAndCode, 2);
        try {
            $mailResult = send_app_mail_detailed((string) $user['email'], 'JUVA Certify Manager login code', "Your JUVA Certify Manager login code is {$code}. It expires in 10 minutes. If this was not you, contact the system administrator immediately.");
            if (empty($mailResult['sent'])) {
                throw new LoginMailException((string) ($mailResult['error'] ?? 'Unknown mail delivery failure.'));
            }
        } catch (Throwable $mailError) {
            $consume = db()->prepare('UPDATE otp_challenges SET consumed_at = ? WHERE user_id = ? AND challenge_token_hash = ? AND consumed_at IS NULL');
            $consume->execute([now_sql(), $loginUserId, hash_secret($challenge)]);
            if ($mailError instanceof LoginMailException) throw $mailError;
            throw new LoginMailException($mailError->getMessage(), 0, $mailError);
        } finally {
            $code = '';
        }
        audit_log($loginUserId, 'auth.otp_sent', 'user', $loginUserId);
        while (ob_get_level() > 0) ob_end_clean();
        respond(['otp_required' => true, 'challenge' => $challenge, 'message' => 'A verification code has been sent to your email.']);
    }

    start_auth_session($loginUserId);
    if ($remember) set_remember_cookie($loginUserId);
    $statement = db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?');
    $statement->execute([now_sql(), $loginUserId]);
    audit_log($loginUserId, 'auth.login_success', 'user', $loginUserId);
    while (ob_get_level() > 0) ob_end_clean();
    respond(['user' => public_user(fetch_user($loginUserId)), 'csrf_token' => csrf_token()]);
} catch (Throwable $error) {
    restore_error_handler();
    $previousErrorHandler = null;
    $referenceId = login_reference_id();
    login_log_failure($error, $referenceId, $loginUserId);
    login_json_error($error instanceof LoginMailException ? 'Login code could not be sent.' : 'The server could not complete login.', $referenceId, $error instanceof LoginMailException ? 503 : 500);
} finally {
    if ($previousErrorHandler !== null) restore_error_handler();
}