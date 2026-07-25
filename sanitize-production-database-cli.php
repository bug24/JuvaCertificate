<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/deployment/cpanel/database-sanitization-lib.php';

function sanitize_cli_option(array $argv, $name, $default = null)
{
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

function sanitize_cli_has_option(array $argv, $name)
{
    return in_array('--' . $name, $argv, true);
}

function sanitize_cli_output_json($path, array $data)
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create report directory: ' . $directory);
    }
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

$configPath = sanitize_cli_option($argv, 'config', __DIR__ . '/api/config.local.php');
$databaseOverride = sanitize_cli_option($argv, 'database');
$reportDir = sanitize_cli_option($argv, 'report-dir', __DIR__ . '/tmp/database-sanitization');
$execute = sanitize_cli_has_option($argv, 'execute');
$dryRun = !$execute || sanitize_cli_has_option($argv, 'dry-run');
$confirmation = sanitize_cli_option($argv, 'confirm', '');
$allowProduction = sanitize_cli_has_option($argv, 'allow-production');

if (!is_file($configPath)) {
    fwrite(STDERR, "Configuration file not found: {$configPath}\n");
    exit(2);
}

$config = require $configPath;
$environment = strtolower((string) ($config['app_env'] ?? 'production'));
$database = $databaseOverride ?: (string) ($config['db_name'] ?? '');
if ($database === '') {
    fwrite(STDERR, "Database name is missing.\n");
    exit(2);
}
if ($execute && $environment === 'production' && !$allowProduction) {
    fwrite(STDERR, "Refusing execute mode in production without --allow-production.\n");
    exit(3);
}
if ($execute && $confirmation !== 'PURGE TEST DATA') {
    fwrite(STDERR, "Execute mode requires --confirm=\"PURGE TEST DATA\".\n");
    exit(3);
}

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['db_host'] ?? 'localhost',
        $config['db_port'] ?? '3306',
        $database
    ),
    $config['db_user'] ?? '',
    $config['db_password'] ?? '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$detected = $pdo->query('SELECT DATABASE()')->fetchColumn();
if ($detected !== $database) {
    fwrite(STDERR, "Connected database mismatch.\n");
    exit(4);
}

$policy = juva_sanitization_table_policy();
$before = juva_exact_row_counts($pdo);
$unknown = juva_unknown_tables($before, $policy);
$startedAt = gmdate('c');

echo "JUVA production database sanitizer\n";
echo "Environment: {$environment}\n";
echo "Database: {$detected}\n";
echo "Mode: " . ($dryRun ? 'DRY RUN' : 'EXECUTE') . "\n";
echo "Tables: " . count($before) . "\n";

if ($unknown) {
    fwrite(STDERR, 'Refusing to continue; unclassified tables: ' . implode(', ', $unknown) . PHP_EOL);
    exit(5);
}

$affected = [];
foreach (juva_sanitization_delete_order() as $table) {
    if (array_key_exists($table, $before)) {
        $affected[$table] = $before[$table];
        echo sprintf("%-42s %10d row(s)\n", $table, $before[$table]);
    }
}

$report = [
    'started_at_utc' => $startedAt,
    'environment' => $environment,
    'database' => $database,
    'mode' => $dryRun ? 'dry-run' : 'execute',
    'row_counts_before' => $before,
    'planned_deletions' => $affected,
    'unknown_tables' => $unknown,
];

if ($dryRun) {
    $report['completed_at_utc'] = gmdate('c');
    sanitize_cli_output_json($reportDir . '/sanitization-dry-run.json', $report);
    echo "Dry run complete. No rows were changed.\n";
    exit(0);
}

try {
    $pdo->beginTransaction();
    foreach (juva_sanitization_delete_order() as $table) {
        if (!array_key_exists($table, $before)) {
            continue;
        }
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $pdo->exec("DELETE FROM {$quoted}");
    }

    if (array_key_exists('certification_categories', $before)) {
        $pdo->exec("UPDATE certification_categories SET source_sample = NULL");
    }
    if (array_key_exists('certificate_branding_settings', $before)) {
        $pdo->exec(
            "UPDATE certificate_branding_settings
             SET company_stamp_path = NULL,
                 company_stamp_original_name = NULL,
                 company_stamp_mime_type = NULL,
                 company_stamp_uploaded_at = NULL,
                 company_stamp_uploaded_by = NULL,
                 company_stamp_is_active = 0"
        );
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Sanitization failed and was rolled back: ' . $exception->getMessage() . PHP_EOL);
    exit(6);
}

$after = juva_exact_row_counts($pdo);
$nonEmptyPurged = [];
foreach ($policy as $table => $rule) {
    if ($rule['purge'] && (($after[$table] ?? 0) !== 0)) {
        $nonEmptyPurged[$table] = $after[$table];
    }
}

$activeTemplateIssues = $pdo->query(
    "SELECT category_id, COUNT(*) AS active_count
     FROM form_templates
     WHERE status = 'active'
     GROUP BY category_id
     HAVING COUNT(*) <> 1"
)->fetchAll();
$orphanChecks = [];
$orphanSql = [
    'form_templates_without_category' => 'SELECT COUNT(*) FROM form_templates ft LEFT JOIN certification_categories c ON c.id = ft.category_id WHERE c.id IS NULL',
    'form_fields_without_template' => 'SELECT COUNT(*) FROM form_fields ff LEFT JOIN form_templates ft ON ft.id = ff.template_id WHERE ft.id IS NULL',
    'repeatable_sections_without_template' => 'SELECT COUNT(*) FROM form_repeatable_sections rs LEFT JOIN form_templates ft ON ft.id = rs.template_id WHERE ft.id IS NULL',
    'repeatable_columns_without_section' => 'SELECT COUNT(*) FROM form_repeatable_columns rc LEFT JOIN form_repeatable_sections rs ON rs.id = rc.section_id WHERE rs.id IS NULL',
];
foreach ($orphanSql as $name => $sql) {
    $orphanChecks[$name] = (int) $pdo->query($sql)->fetchColumn();
}

$report['row_counts_after'] = $after;
$report['non_empty_purged_tables'] = $nonEmptyPurged;
$report['active_template_issues'] = $activeTemplateIssues;
$report['orphan_checks'] = $orphanChecks;
$report['integrity_pass'] = !$nonEmptyPurged && !$activeTemplateIssues && !array_filter($orphanChecks);
$report['completed_at_utc'] = gmdate('c');
sanitize_cli_output_json($reportDir . '/sanitization-execute.json', $report);

echo 'Sanitization complete. Integrity: ' . ($report['integrity_pass'] ? 'PASS' : 'FAIL') . PHP_EOL;
exit($report['integrity_pass'] ? 0 : 7);

