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
];

if (!$result['config_present']) {
    health_response($result + ['message' => 'Missing api/config.local.php.'], 503);
}

try {
    $config = require $configPath;
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['db_host'] ?? 'localhost',
            $config['db_port'] ?? '3306',
            $config['db_name'] ?? ''
        ),
        (string) ($config['db_user'] ?? ''),
        (string) ($config['db_password'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->query('SELECT 1');
    $result['database_connected'] = true;
    $result['ok'] = true;
    health_response($result + ['message' => 'JUVA API health check passed.']);
} catch (Throwable $e) {
    error_log('JUVA health check failed: ' . $e->getMessage());
    health_response($result + ['message' => 'Database connection failed. Check cPanel database name, user, password and privileges.'], 503);
}