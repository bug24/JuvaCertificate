<?php

declare(strict_types=1);
$root = dirname(__DIR__);
$sourceRoot = realpath($root . DIRECTORY_SEPARATOR . 'api');
$deployment = $root . DIRECTORY_SEPARATOR . 'deployment' . DIRECTORY_SEPARATOR . 'juva-certify-cpanel-fresh' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'api';
$failures = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS)) as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') continue;
    $relative = ltrim(substr($file->getPathname(), strlen($sourceRoot)), DIRECTORY_SEPARATOR);
    if (in_array(strtolower($relative), ['config.local.php', 'config.example.php'], true)) continue;
    $target = $deployment . DIRECTORY_SEPARATOR . $relative;
    if (!is_file($target)) { $failures[] = "Missing deployment file: {$relative}"; continue; }
    if (hash_file('sha256', $file->getPathname()) !== hash_file('sha256', $target)) $failures[] = "Deployment file differs: {$relative}";
}
foreach (['certificates/readiness.php', 'certificates/preview.php', 'certificates/generate.php', 'certificates/verify.php', 'auth/login.php', 'auth/verify-otp.php', 'dashboard/summary.php', 'inspections/index.php'] as $entry) {
    $path = $deployment . DIRECTORY_SEPARATOR . $entry;
    if (!is_file($path)) { $failures[] = "Missing runtime entry: {$entry}"; continue; }
}
if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "Deployment integrity passed: source and cPanel API trees are complete and synchronized." . PHP_EOL;