<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This reconciliation tool is CLI-only.\n");
    exit(1);
}

$options = getopt('', ['apply', 'actor-user-id:', 'confirm:', 'config:', 'help']);
if (isset($options['help'])) {
    echo "Dry-run (default): php reconcile-certificate-numbers.php --config=/absolute/path/config.php\n";
    echo "Apply: php reconcile-certificate-numbers.php --apply --actor-user-id=123 --confirm=RECONCILE-CERTIFICATE-NUMBERS --config=/absolute/path/config.php\n";
    exit(0);
}

$apiRoot = getenv('JUVA_API_ROOT');
if (!is_string($apiRoot) || trim($apiRoot) === '') {
    $sourceCandidate = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'api';
    $deploymentCandidate = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'api';
    $apiRoot = is_dir($sourceCandidate) ? $sourceCandidate : $deploymentCandidate;
}
$library = rtrim($apiRoot, "\\/") . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'certificate_number_reconciliation.php';
if (!is_file($library)) {
    fwrite(STDERR, "Certificate-number reconciliation library was not found.\n");
    exit(1);
}
require_once $library;

$configPath = isset($options['config']) ? (string) $options['config'] : (string) (getenv('JUVA_CONFIG_PATH') ?: rtrim($apiRoot, "\\/") . DIRECTORY_SEPARATOR . 'config.local.php');
if (!is_file($configPath)) {
    fwrite(STDERR, "Configuration file not found. Pass --config or set JUVA_CONFIG_PATH.\n");
    exit(1);
}
$config = require $configPath;
foreach (['db_host', 'db_port', 'db_name', 'db_user', 'db_password'] as $key) {
    if (!is_array($config) || !array_key_exists($key, $config)) {
        fwrite(STDERR, "Configuration is missing {$key}.\n");
        exit(1);
    }
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_port'], $config['db_name']),
        (string) $config['db_user'],
        (string) $config['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    if (!isset($options['apply'])) {
        $report = certificate_number_reconciliation_audit($pdo);
        echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
        exit($report['ready_to_apply'] ? 0 : 2);
    }
    $actorUserId = isset($options['actor-user-id']) ? (int) $options['actor-user-id'] : 0;
    $confirmation = isset($options['confirm']) ? (string) $options['confirm'] : '';
    if ($actorUserId < 1 || !hash_equals(CERTIFICATE_NUMBER_RECONCILIATION_CONFIRMATION, $confirmation)) {
        fwrite(STDERR, "Apply requires --actor-user-id and --confirm=" . CERTIFICATE_NUMBER_RECONCILIATION_CONFIRMATION . ".\n");
        exit(1);
    }
    $result = certificate_number_reconciliation_apply($pdo, $actorUserId);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Reconciliation failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
