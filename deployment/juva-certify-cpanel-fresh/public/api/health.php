<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function health_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['data' => $payload, 'error' => null, 'validation' => null], JSON_UNESCAPED_SLASHES);
    exit;
}

$configPath = getenv('JUVA_CONFIG_PATH') ?: __DIR__ . '/config.local.php';
$result = [
    'ok' => false,
    'php_version' => PHP_VERSION,
    'config_present' => is_file($configPath),
    'database_connected' => false,
    'private_storage_exists' => false,
    'private_storage_writable' => false,
    'session_directory_exists' => false,
    'session_directory_writable' => false,
    'session_start_successful' => false,
    'session_persistence_successful' => false,
    'certificate_service_present' => is_file(__DIR__ . '/lib/certificate_engine.php'),
    'deployment_dependencies_complete' => is_file(__DIR__ . '/lib/certificate_engine.php'),
];

if (!$result['config_present']) health_response($result + ['message' => 'Application configuration is missing.'], 503);

try {
    $config = require $configPath;
    $private = trim((string) ($config['private_storage_path'] ?? ''));
    if ($private === '') $private = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'juva-certify-private';
    $result['private_storage_exists'] = is_dir($private);
    $result['private_storage_writable'] = is_dir($private) && is_writable($private);
    $sessions = trim((string) ($config['session_save_path'] ?? ''));
    if ($sessions === '') $sessions = rtrim($private, "\\/") . DIRECTORY_SEPARATOR . 'sessions';
    $result['session_directory_exists'] = is_dir($sessions);
    $result['session_directory_writable'] = is_dir($sessions) && is_writable($sessions);
    if (!$result['session_directory_exists']) @mkdir($sessions, 0775, true);
    $result['session_directory_exists'] = is_dir($sessions);
    $result['session_directory_writable'] = is_dir($sessions) && is_writable($sessions);
    if ($result['session_directory_writable']) {
        session_save_path($sessions);
        session_name((string) ($config['session_name'] ?? 'juva_certify_session'));
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
        $result['session_start_successful'] = session_start();
        if ($result['session_start_successful']) {
            $_SESSION['_health_probe'] = bin2hex(random_bytes(8));
            $probe = $_SESSION['_health_probe'];
            session_write_close();
            session_start();
            $result['session_persistence_successful'] = isset($_SESSION['_health_probe']) && hash_equals($probe, (string) $_SESSION['_health_probe']);
            unset($_SESSION['_health_probe']);
            session_write_close();
        }
    }
    $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['db_host'] ?? 'localhost', $config['db_port'] ?? '3306', $config['db_name'] ?? ''), (string) ($config['db_user'] ?? ''), (string) ($config['db_password'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    $pdo->query('SELECT 1');
    $result['database_connected'] = true;
    $result['deployment_dependencies_complete'] = $result['certificate_service_present'];
    $result['ok'] = $result['database_connected'] && $result['private_storage_writable'] && $result['session_persistence_successful'] && $result['deployment_dependencies_complete'];
    health_response($result + ['message' => $result['ok'] ? 'JUVA API health check passed.' : 'One or more runtime checks failed.'], $result['ok'] ? 200 : 503);
} catch (Throwable $e) {
    error_log('JUVA health check failed: ' . $e->getMessage());
    health_response($result + ['message' => 'A runtime health check failed.'], 503);
}