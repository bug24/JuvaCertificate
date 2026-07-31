<?php

declare(strict_types=1);
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$user = require_auth();
if (!has_permission($user, 'system.manage') && ($user['role']['slug'] ?? '') !== 'super-admin') api_error('Forbidden.', 403);

$root = null;
$cursor = __DIR__;
for ($depth = 0; $depth <= 6; $depth++) {
    if (is_file($cursor . DIRECTORY_SEPARATOR . 'deployment_manifest.json')) { $root = $cursor; break; }
    $parent = dirname($cursor);
    if ($parent === $cursor) break;
    $cursor = $parent;
}
if ($root === null) api_error('Deployment manifest is unavailable.', 503);
$public = $root . DIRECTORY_SEPARATOR . 'deployment' . DIRECTORY_SEPARATOR . 'juva-certify-cpanel-fresh' . DIRECTORY_SEPARATOR . 'public';
$manifestPath = $root . DIRECTORY_SEPARATOR . 'deployment_manifest.json';
if (!is_file($manifestPath)) api_error('Deployment manifest is unavailable.', 503);
$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (!is_array($manifest) || !is_array($manifest['files'] ?? null)) api_error('Deployment manifest is invalid.', 503);
$missing = [];
$mismatched = [];
$present = 0;
foreach ($manifest['files'] as $file) {
    $relative = (string) ($file['relative_path'] ?? '');
    if ($relative === '' || str_contains($relative, '..')) continue;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($path)) { $missing[] = $relative; continue; }
    $present++;
    if (strtolower((string) ($file['sha256'] ?? '')) !== strtolower((string) hash_file('sha256', $path))) $mismatched[] = $relative;
}
$commit = 'unknown';
$head = $root . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'HEAD';
if (is_file($head)) {
    $headValue = trim((string) file_get_contents($head));
    if (str_starts_with($headValue, 'ref: ')) {
        $refPath = $root . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, substr($headValue, 5));
        $commit = is_file($refPath) ? trim((string) file_get_contents($refPath)) : 'unknown';
    } elseif ($headValue !== '') $commit = $headValue;
}
respond(['current_commit' => $commit, 'manifest_version' => (string) ($manifest['manifest_version'] ?? ''), 'total_expected_files' => count($manifest['files']), 'total_present_files' => $present, 'missing_files' => $missing, 'mismatched_files' => $mismatched, 'deployment_complete' => count($missing) === 0 && count($mismatched) === 0]);