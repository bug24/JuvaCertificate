<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$configPath = getenv('JUVA_CONFIG_PATH') ?: __DIR__ . '/config.local.php';
if (!is_file($configPath)) {
    api_error('Application configuration is missing.', 500);
}

$config = require $configPath;
$config = array_merge([
    'app_env' => 'production',
    'app_url' => 'https://cert.juvaoil.com',
    'session_name' => 'juva_certify_session',
    'session_save_path' => '',
    'allowed_origin' => 'https://cert.juvaoil.com',
    'session_hours' => 8,
    'remember_days' => 30,
    'security_salt' => 'replace-this-with-a-long-random-secret',
    'mail_transport' => 'mail',
    'mail_from' => 'no-reply@cert.juvaoil.com',
    'mail_from_name' => 'JUVA Certify Manager',
    'mail_reply_to' => 'juvaoil@gmail.com',
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_encryption' => 'tls',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_timeout' => 15,
    'certificate_notifications_enabled' => false,
    'trusted_proxies' => [],
    'max_json_bytes' => 1048576,
    'company_name' => 'JUVA-OIL SERVICES (NIG) LIMITED',
    'company_registered_address' => '52 Rumuolumeni Road, P.O Box 1997 Mile 1, Obio/Akpor, Port Harcourt',
    'company_operational_address' => 'Plot 127A Trans Amadi, Ind. Layout, Port Harcourt',
    'company_phone' => '08065164945',
    'company_email' => 'juvaoil@gmail.com',
    'company_website' => 'www.juvaoil.com',
], $config);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin !== '' && isset($config['allowed_origin']) && hash_equals((string) $config['allowed_origin'], $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} elseif ($origin !== '') {
    api_error('Origin not allowed.', 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    http_response_code(204);
    exit;
}

$sessionStorageRoot = trim((string) ($config['session_save_path'] ?? ''));
if ($sessionStorageRoot === '') {
    $privateStorageRoot = trim((string) ($config['private_storage_path'] ?? ''));
    if ($privateStorageRoot === '') {
        $privateStorageRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'juva-certify-private';
    }
    $sessionStorageRoot = rtrim($privateStorageRoot, "\\/") . DIRECTORY_SEPARATOR . 'sessions';
}
function prepare_runtime_directory(string $path): bool
{
    if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) return false;
    if (!is_writable($path)) return false;
    $probe = rtrim($path, "\\/") . DIRECTORY_SEPARATOR . '.juva-write-' . bin2hex(random_bytes(6));
    $written = @file_put_contents($probe, 'ok', LOCK_EX);
    $removed = $written !== false && @unlink($probe);
    return $written !== false && $removed;
}

$runtimeDirectories = [
    'sessions' => $sessionStorageRoot,
    'logs' => rtrim($privateStorageRoot, "\\/") . DIRECTORY_SEPARATOR . 'logs',
    'cache' => rtrim($privateStorageRoot, "\\/") . DIRECTORY_SEPARATOR . 'cache',
    'uploads' => rtrim($privateStorageRoot, "\\/") . DIRECTORY_SEPARATOR . 'uploads',
];
foreach ($runtimeDirectories as $runtimeDirectory) {
    prepare_runtime_directory($runtimeDirectory);
}

if (!prepare_runtime_directory($sessionStorageRoot)) {
    $defaultSessionPath = trim((string) ini_get('session.save_path'));
    if ($defaultSessionPath !== '' && prepare_runtime_directory($defaultSessionPath)) {
        $sessionStorageRoot = $defaultSessionPath;
    } else {
        error_log('JUVA session storage unavailable; custom and PHP default paths failed runtime write probe.');
        api_error('Secure session could not be started.', 503);
    }
}

session_save_path($sessionStorageRoot);
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');

session_name((string) $config['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => is_https() || stripos((string) ($config['app_url'] ?? ''), 'https://') === 0,
    'httponly' => true,
    'samesite' => 'Strict',
]);
if (!session_start()) {
    api_error('Secure session could not be started.', 500);
}

function db(): PDO
{
    static $pdo = null;
    global $config;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_port'],
        $config['db_name']
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function api_error(string $message, int $status = 400, array $validation = []): void
{
    http_response_code($status);
    echo json_encode(['data' => null, 'error' => $message, 'validation' => $validation], JSON_UNESCAPED_SLASHES);
    exit;
}

function respond(array $data = [], int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['data' => $data, 'error' => null, 'validation' => null], JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    global $config;
    $maxBytes = max(1024, (int) ($config['max_json_bytes'] ?? 1048576));
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($contentLength > $maxBytes) api_error('Request body is too large.', 413);
    $raw = (string) file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if (strlen($raw) > $maxBytes) api_error('Request body is too large.', 413);
    $payload = json_decode($raw, true, 32);
    if ($raw !== '' && json_last_error() !== JSON_ERROR_NONE) api_error('Invalid JSON request.', 400);
    return is_array($payload) ? $payload : [];
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        api_error('Method not allowed.', 405);
    }
}

function require_methods(array $methods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        api_error('Method not allowed.', 405);
    }
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    return is_trusted_proxy_request() && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https';
}

function now_sql(): string
{
    return gmdate('Y-m-d H:i:s');
}

function future_sql(int $seconds): string
{
    return gmdate('Y-m-d H:i:s', time() + $seconds);
}

function client_ip(): string
{
    if (is_trusted_proxy_request() && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($parts[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) return $candidate;
    }
    $remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : 'unknown';
}

function is_trusted_proxy_request(): bool
{
    global $config;
    $remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    if (!filter_var($remote, FILTER_VALIDATE_IP)) return false;
    $trusted = $config['trusted_proxies'] ?? [];
    if (is_string($trusted)) $trusted = array_filter(array_map('trim', explode(',', $trusted)));
    foreach ((array) $trusted as $network) if (ip_in_cidr($remote, (string) $network)) return true;
    return false;
}

function ip_in_cidr(string $ip, string $cidr): bool
{
    if (strpos($cidr, '/') === false) return hash_equals($cidr, $ip);
    list($network, $prefix) = explode('/', $cidr, 2);
    $ipBytes = @inet_pton($ip); $networkBytes = @inet_pton($network);
    if ($ipBytes === false || $networkBytes === false || strlen($ipBytes) !== strlen($networkBytes)) return false;
    $bits = (int) $prefix;
    if ($bits < 0 || $bits > strlen($ipBytes) * 8) return false;
    $whole = intdiv($bits, 8); $remaining = $bits % 8;
    if ($whole > 0 && substr($ipBytes, 0, $whole) !== substr($networkBytes, 0, $whole)) return false;
    if ($remaining === 0) return true;
    $mask = (0xFF << (8 - $remaining)) & 0xFF;
    return (ord($ipBytes[$whole]) & $mask) === (ord($networkBytes[$whole]) & $mask);
}

function client_ip_hash(): string
{
    global $config;
    return hash('sha256', client_ip() . '|' . (string) $config['security_salt']);
}

function user_agent_hash(): string
{
    global $config;
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    return hash('sha256', $ua . '|' . (string) $config['security_salt']);
}

function validate_image_dimensions(string $path, int $maxPixels = 40000000): bool
{
    $size = @getimagesize($path);
    if (!$size || empty($size[0]) || empty($size[1])) return false;
    $width = (int) $size[0]; $height = (int) $size[1];
    return $width <= 10000 && $height <= 10000 && ($width * $height) <= $maxPixels;
}
function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function hash_secret(string $secret): string
{
    global $config;
    return hash('sha256', $secret . '|' . (string) $config['security_salt']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = random_token(24);
    }
    return (string) $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS') {
        return;
    }
    $header = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? (string) $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    if ($header === '' || empty($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $header)) {
        api_error('Security token is missing or expired.', 403);
    }
}

function decode_permissions(?string $json): array
{
    $permissions = json_decode((string) $json, true);
    return is_array($permissions) ? $permissions : [];
}

function public_user(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'username' => $row['username'],
        'phone' => $row['phone'],
        'qualification' => $row['qualification'],
        'job_title' => $row['job_title'] ?? null,
        'professional_memberships' => $row['professional_memberships'] ?? null,
        'certificate_signing_role' => $row['certificate_signing_role'] ?? null,
        'signature_original_name' => $row['signature_original_name'] ?? null,
        'signature_is_active' => (int) ($row['signature_is_active'] ?? 0),
        'signature_url' => !empty($row['signature_path']) ? api_url('users/signature-download.php?id=' . (int) $row['id']) : null,
        'status' => $row['status'],
        'client_id' => $row['client_id'] !== null ? (int) $row['client_id'] : null,
        'role' => [
            'id' => (int) $row['role_id'],
            'name' => $row['role_name'],
            'slug' => $row['role_slug'],
            'permissions' => decode_permissions($row['permissions_json']),
        ],
        'last_login_at' => $row['last_login_at'],
        'created_at' => $row['created_at'],
    ];
}

function fetch_user(int $id): ?array
{
    $stmt = db()->prepare('SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.permissions_json FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function fetch_user_by_identifier(string $identifier): ?array
{
    $stmt = db()->prepare('SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.permissions_json FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ? OR u.username = ? LIMIT 1');
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function current_user(): ?array
{
    global $config;

    if (!empty($_SESSION['user_id']) && !empty($_SESSION['authenticated_at'])) {
        $expires = (int) $_SESSION['authenticated_at'] + ((int) $config['session_hours'] * 3600);
        if (time() > $expires) {
            clear_auth_session();
            return null;
        }
        $user = fetch_user((int) $_SESSION['user_id']);
        $revokedAt = $user && !empty($user['sessions_revoked_at']) ? strtotime((string) $user['sessions_revoked_at'] . ' UTC') : false;
        if ($user && $user['status'] === 'active' && ($revokedAt === false || $revokedAt <= (int) $_SESSION['authenticated_at'])) {
            return public_user($user);
        }
        clear_auth_session();
        return null;
    }

    return restore_from_remember_token();
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        log_session_diagnostic('authentication_required');
        api_error('Authentication required.', 401);
    }
    return $user;
}

function session_diagnostic_context(string $event): array
{
    global $config;
    $savePath = (string) ini_get('session.save_path');
    $cookieParams = session_get_cookie_params();
    return [
        'event' => $event,
        'request_path' => isset($_SERVER['REQUEST_URI']) ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '',
        'session_name' => session_name(),
        'session_status' => session_status(),
        'session_id_present' => session_id() !== '',
        'session_cookie_present' => isset($_COOKIE[session_name()]),
        'authenticated_user_id_present' => !empty($_SESSION['user_id']),
        'authenticated_at_present' => !empty($_SESSION['authenticated_at']),
        'session_save_path_configured' => $savePath !== '',
        'session_save_path_exists' => $savePath !== '' && is_dir($savePath),
        'session_save_path_writable' => $savePath !== '' && is_dir($savePath) && is_writable($savePath),
        'cookie_path' => $cookieParams['path'] ?? '',
        'cookie_secure' => (bool) ($cookieParams['secure'] ?? false),
        'cookie_httponly' => (bool) ($cookieParams['httponly'] ?? false),
        'cookie_samesite' => $cookieParams['samesite'] ?? '',
        'configured_session_name_matches' => hash_equals((string) ($config['session_name'] ?? ''), session_name()),
    ];
}

function log_session_diagnostic(string $event): void
{
    try {
        $directory = ensure_private_storage_dir('logs');
        $line = '[' . gmdate('c') . '] ' . json_encode(session_diagnostic_context($event), JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($directory . DIRECTORY_SEPARATOR . 'session-diagnostic.log', $line, FILE_APPEND | LOCK_EX);
    } catch (Throwable $error) {
        error_log('Unable to write session diagnostic: ' . $error->getMessage());
    }
}

function has_permission(array $user, string $permission): bool
{
    $permissions = $user['role']['permissions'];
    return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
}

function require_permission(string $permission): array
{
    $user = require_auth();
    if (!has_permission($user, $permission)) {
        api_error('You do not have permission to perform this action.', 403);
    }
    return $user;
}

function require_any_permission(array $permissions): array
{
    $user = require_auth();
    foreach ($permissions as $permission) {
        if (has_permission($user, (string) $permission)) {
            return $user;
        }
    }
    api_error('You do not have permission to perform this action.', 403);
}

function audit_log(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
{
    $stmt = db()->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $userId,
        $action,
        $entityType,
        $entityId,
        $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : null,
        client_ip(),
        client_ip_hash(),
        now_sql(),
    ]);
}

function start_auth_session(int $userId): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('PHP session is not active.');
    }
    if (!session_regenerate_id(true)) {
        throw new RuntimeException('PHP session ID could not be regenerated.');
    }
    $_SESSION['user_id'] = $userId;
    $_SESSION['authenticated_at'] = time();
    $_SESSION['csrf_token'] = random_token(24);
    session_write_close();
    if (!session_start()) {
        throw new RuntimeException('PHP session could not be reopened after authentication.');
    }
    if (empty($_SESSION['user_id']) || (int) $_SESSION['user_id'] !== $userId) {
        throw new RuntimeException('Authenticated PHP session could not be persisted.');
    }
    log_session_diagnostic('session_started');
}

function clear_auth_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function remember_cookie_name(): string
{
    return 'juva_certify_remember';
}

function set_remember_cookie(int $userId): void
{
    global $config;
    $selector = random_token(9);
    $validator = random_token(32);
    $expires = time() + ((int) $config['remember_days'] * 86400);

    $stmt = db()->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, user_agent_hash, ip_hash, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $selector, hash_secret($validator), user_agent_hash(), client_ip_hash(), gmdate('Y-m-d H:i:s', $expires), now_sql()]);

    setcookie(remember_cookie_name(), $selector . ':' . $validator, [
        'expires' => $expires,
        'path' => '/',
        'domain' => '',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function clear_remember_cookie(): void
{
    if (!empty($_COOKIE[remember_cookie_name()])) {
        $parts = explode(':', (string) $_COOKIE[remember_cookie_name()], 2);
        if (count($parts) === 2) {
            $stmt = db()->prepare('UPDATE remember_tokens SET revoked_at = ? WHERE selector = ?');
            $stmt->execute([now_sql(), $parts[0]]);
        }
    }
    setcookie(remember_cookie_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function restore_from_remember_token(): ?array
{
    if (empty($_COOKIE[remember_cookie_name()])) {
        return null;
    }
    $parts = explode(':', (string) $_COOKIE[remember_cookie_name()], 2);
    if (count($parts) !== 2) {
        clear_remember_cookie();
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM remember_tokens WHERE selector = ? AND revoked_at IS NULL AND expires_at > ? LIMIT 1');
    $stmt->execute([$parts[0], now_sql()]);
    $token = $stmt->fetch();
    if (!$token || !hash_equals($token['validator_hash'], hash_secret($parts[1]))) {
        clear_remember_cookie();
        return null;
    }

    $user = fetch_user((int) $token['user_id']);
    if (!$user || $user['status'] !== 'active') {
        clear_remember_cookie();
        return null;
    }

    $update = db()->prepare('UPDATE remember_tokens SET revoked_at = ? WHERE id = ?');
    $update->execute([now_sql(), $token['id']]);
    start_auth_session((int) $user['id']);
    set_remember_cookie((int) $user['id']);
    audit_log((int) $user['id'], 'auth.remember_login', 'user', (int) $user['id']);
    return public_user($user);
}

function privileged_role(string $slug): bool
{
    return in_array($slug, ['super-admin', 'operations-admin'], true);
}

function create_otp_challenge(int $userId): string
{
    $code = (string) random_int(100000, 999999);
    $challenge = random_token(18);
    $stmt = db()->prepare('INSERT INTO otp_challenges (user_id, challenge_token_hash, code_hash, purpose, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, hash_secret($challenge), password_hash($code, PASSWORD_DEFAULT), 'login', future_sql(600), now_sql()]);
    return $challenge . ':' . $code;
}

function create_auth_token(int $userId, string $type, int $seconds, ?int $createdBy = null): string
{
    $token = random_token(32);
    $stmt = db()->prepare('INSERT INTO auth_tokens (user_id, created_by, type, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $createdBy, $type, hash_secret($token), future_sql($seconds), now_sql()]);
    return $token;
}

function find_auth_token(string $token, string $type): ?array
{
    $stmt = db()->prepare('SELECT t.*, u.email, u.name FROM auth_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = ? AND t.type = ? AND t.used_at IS NULL AND t.revoked_at IS NULL AND t.expires_at > ? LIMIT 1');
    $stmt->execute([hash_secret($token), $type, now_sql()]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function consume_auth_token(int $id): void
{
    $stmt = db()->prepare('UPDATE auth_tokens SET used_at = ? WHERE id = ?');
    $stmt->execute([now_sql(), $id]);
}

require_once __DIR__ . '/lib/mail_service.php';

function send_app_mail(string $to, string $subject, string $body): bool
{
    $result = send_app_mail_detailed($to, $subject, $body);
    return (bool) $result['sent'];
}
function rate_limit_or_fail(string $scope, string $identifier, int $maxAttempts, int $windowSeconds): void
{
    $stmt = db()->prepare('SELECT COUNT(*) AS attempts FROM auth_attempts WHERE scope = ? AND identifier_hash = ? AND created_at > ?');
    $stmt->execute([$scope, hash_secret(strtolower($identifier)), gmdate('Y-m-d H:i:s', time() - $windowSeconds)]);
    $row = $stmt->fetch();
    if ($row && (int) $row['attempts'] >= $maxAttempts) {
        api_error('Too many attempts. Please wait and try again.', 429);
    }
}

function consume_rate_limit(string $scope, string $identifier, int $maxAttempts, int $windowSeconds): void
{
    rate_limit_or_fail($scope, $identifier, $maxAttempts, $windowSeconds);
    record_auth_attempt($scope, $identifier, false, 'request');
}
function record_auth_attempt(string $scope, string $identifier, bool $success, ?string $reason = null): void
{
    $stmt = db()->prepare('INSERT INTO auth_attempts (scope, identifier_hash, success, failure_reason, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$scope, hash_secret(strtolower($identifier)), $success ? 1 : 0, $reason, client_ip_hash(), now_sql()]);
}

function validate_required(array $input, array $fields): array
{
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
            $errors[$field] = 'This field is required.';
        }
    }
    return $errors;
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? (int) mb_strlen($value, 'UTF-8') : strlen($value);
}

function normalize_text_input(?string $value, bool $allowNewlines = false): string
{
    $value = trim((string) $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $value) ?? '';
    if ($allowNewlines) {
        $value = preg_replace("/\r\n?/", "\n", $value) ?? '';
    } else {
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    }
    return trim($value);
}

function contains_markup(string $value): bool
{
    return (bool) preg_match('/<[^>]*>/u', $value);
}

function valid_iso_date(?string $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
}

function inspection_scope_clause(array $user, string $alias = 'i'): array
{
    if (has_permission($user, '*') || has_permission($user, 'inspections.review') || has_permission($user, 'certificates.issue') || has_permission($user, 'users.manage')) {
        return ['', []];
    }

    if (($user['role']['slug'] ?? '') === 'client') {
        if (!empty($user['client_id'])) {
            return [" AND {$alias}.client_id = ?", [(int) $user['client_id']]];
        }
        return [' AND 1 = 0', []];
    }

    if (has_permission($user, 'inspections.create') || has_permission($user, 'inspections.edit')) {
        return [" AND {$alias}.inspector_id = ?", [(int) $user['id']]];
    }

    return [' AND 1 = 0', []];
}

function fetch_inspection_for_user(int $inspectionId, array $user, string $select = 'i.*'): ?array
{
    [$scopeSql, $scopeParams] = inspection_scope_clause($user, 'i');
    $stmt = db()->prepare("SELECT {$select} FROM inspections i WHERE i.id = ?{$scopeSql} LIMIT 1");
    $stmt->execute(array_merge([$inspectionId], $scopeParams));
    $inspection = $stmt->fetch();
    return $inspection ?: null;
}

function format_scoped_reference(string $clientShortCode, string $categoryShortCode, int $sequenceNumber): string
{
    return sprintf('JUVA/%s/%s/%03d', $clientShortCode, $categoryShortCode, $sequenceNumber);
}

function valid_company_short_code(string $shortCode): bool
{
    return preg_match('/^(?=.*[A-Z])[A-Z0-9]{2,12}$/', strtoupper(trim($shortCode))) === 1;
}

function next_scoped_sequence_number(PDO $pdo, int $clientId, int $categoryId): int
{
    $stmt = $pdo->prepare('SELECT last_number FROM certificate_sequences WHERE client_id = ? AND category_id = ? LIMIT 1');
    $stmt->execute([$clientId, $categoryId]);
    $row = $stmt->fetch();
    $inspectionStmt = $pdo->prepare('SELECT COALESCE(MAX(sequence_number), 0) FROM inspections WHERE client_id = ? AND category_id = ?');
    $inspectionStmt->execute([$clientId, $categoryId]);
    $highestUsed = (int) $inspectionStmt->fetchColumn();
    return max((int) ($row['last_number'] ?? 0), $highestUsed) + 1;
}

function preview_scoped_reference(PDO $pdo, int $clientId, int $categoryId, string $clientShortCode, string $categoryShortCode): array
{
    $nextNumber = next_scoped_sequence_number($pdo, $clientId, $categoryId);
    return [
        'reference' => format_scoped_reference($clientShortCode, $categoryShortCode, $nextNumber),
        'sequence_number' => $nextNumber,
        'client_short_code' => $clientShortCode,
        'category_short_code' => $categoryShortCode,
    ];
}

function allocate_scoped_reference(PDO $pdo, int $clientId, int $categoryId, string $clientShortCode, string $categoryShortCode): array
{
    $timestamp = now_sql();
    $seed = $pdo->prepare('INSERT INTO certificate_sequences (client_id, category_id, last_number, created_at, updated_at) VALUES (?, ?, 0, ?, ?) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)');
    $seed->execute([$clientId, $categoryId, $timestamp, $timestamp]);

    $lock = $pdo->prepare('SELECT last_number FROM certificate_sequences WHERE client_id = ? AND category_id = ? FOR UPDATE');
    $lock->execute([$clientId, $categoryId]);
    $row = $lock->fetch();
    $inspectionStmt = $pdo->prepare('SELECT COALESCE(MAX(sequence_number), 0) FROM inspections WHERE client_id = ? AND category_id = ?');
    $inspectionStmt->execute([$clientId, $categoryId]);
    $highestUsed = (int) $inspectionStmt->fetchColumn();
    $nextNumber = max((int) ($row['last_number'] ?? 0), $highestUsed) + 1;

    $update = $pdo->prepare('UPDATE certificate_sequences SET last_number = ?, updated_at = ? WHERE client_id = ? AND category_id = ?');
    $update->execute([$nextNumber, $timestamp, $clientId, $categoryId]);

    return [
        'reference' => format_scoped_reference($clientShortCode, $categoryShortCode, $nextNumber),
        'sequence_number' => $nextNumber,
        'client_short_code' => $clientShortCode,
        'category_short_code' => $categoryShortCode,
    ];
}
function app_url(string $path): string
{
    global $config;
    return rtrim((string) $config['app_url'], '/') . '/' . ltrim($path, '/');
}

function api_url(string $path): string
{
    global $config;
    $base = isset($config['api_url']) && (string) $config['api_url'] !== ''
        ? (string) $config['api_url']
        : (rtrim((string) $config['app_url'], '/') . '/api');
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}




function private_storage_root(): string
{
    global $config;
    $configured = isset($config['private_storage_path']) ? (string) $config['private_storage_path'] : (dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'juva-certify-private');
    return rtrim($configured, "\\/");
}

function ensure_private_storage_dir(string $relativeDir): string
{
    $relativeDir = trim(str_replace(['..\\', '../'], '', $relativeDir), "\\/");
    $path = private_storage_root() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Unable to create private storage directory.');
    }
    if (!is_writable($path)) throw new RuntimeException('Private storage directory is not writable.');
    return $path;
}

function storage_key(string $group, string $fileName): string
{
    $group = trim(str_replace(['..\\', '../'], '', $group), "\\/");
    $fileName = ltrim(str_replace(['..\\', '../'], '', $fileName), "\\/");
    return $group . '/' . $fileName;
}

function resolve_storage_path(string $key): ?string
{
    $normalized = trim(str_replace(['..\\', '../'], '', $key), "\\/");
    if ($normalized === '') {
        return null;
    }

    $privatePath = private_storage_root() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalized);
    if (is_file($privatePath)) {
        return $privatePath;
    }

    $legacyPath = dirname(__DIR__) . '/../' . str_replace(['\\'], '/', $normalized);
    if (is_file($legacyPath)) {
        return $legacyPath;
    }

    return null;
}

function stream_download(string $absolutePath, string $downloadName, string $contentType, bool $inline = true): void
{
    if (!is_file($absolutePath)) {
        api_error('File not found.', 404);
    }

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . (string) filesize($absolutePath));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($downloadName) . '"');
    header('Cache-Control: private, max-age=60');
    readfile($absolutePath);
    exit;
}



