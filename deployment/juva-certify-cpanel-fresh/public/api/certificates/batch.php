<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_archive.php';
require_once __DIR__ . '/../lib/certificate_download_renderer.php';
require_once __DIR__ . '/../lib/certificate_pdf_batch.php';

require_method('POST');
$user = require_auth();
if (!has_permission($user, 'certificates.view') && !has_permission($user, 'certificates.own.view')) {
    api_error('You do not have permission to view certificates.', 403);
}
require_csrf();
$input = json_input();
try {
    $ids = certificate_batch_ids($input['certificate_ids'] ?? null);
} catch (InvalidArgumentException $error) {
    api_error($error->getMessage(), 422, ['certificate_ids' => $error->getMessage()]);
}
$disposition = strtolower(trim((string) ($input['disposition'] ?? 'attachment')));
if (!in_array($disposition, ['attachment', 'inline'], true)) api_error('Invalid batch disposition.', 422);
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
@set_time_limit(300);

$placeholders = implode(',', array_fill(0, count($ids), '?'));
[$scopeSql, $scopeParams] = certificate_archive_scope($user);
$where = 'cert.id IN (' . $placeholders . ')';
$params = $ids;
if ($scopeSql !== '') { $where .= ' AND ' . $scopeSql; array_push($params, ...$scopeParams); }
$stmt = db()->prepare('SELECT cert.*, i.client_id FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id WHERE ' . $where);
$stmt->execute($params);
$found = [];
foreach ($stmt->fetchAll() ?: [] as $certificate) $found[(int) $certificate['id']] = $certificate;
$missing = array_values(array_filter($ids, static function (int $id) use ($found): bool { return !isset($found[$id]); }));
if ($missing) api_error('Some selected certificates are unavailable or unauthorized: ' . implode(', ', $missing) . '.', 404);
$certificates = array_map(static function (int $id) use ($found): array { return $found[$id]; }, $ids);

$batchRoot = ensure_private_storage_dir('tmp/certificate-batches');
$workspace = $batchRoot . DIRECTORY_SEPARATOR . 'batch-' . random_token(12);
if (!mkdir($workspace, 0775) && !is_dir($workspace)) api_error('Unable to create an isolated batch workspace.', 500);
$outputPath = $workspace . DIRECTORY_SEPARATOR . 'combined-certificates.pdf';
$dynamicFiles = [];
register_shutdown_function(static function () use ($workspace, &$dynamicFiles): void {
    certificate_batch_cleanup($workspace, $dynamicFiles);
});

try {
    $sourcePaths = [];
    $failures = [];
    foreach ($certificates as $certificate) {
        $number = (string) $certificate['certificate_number'];
        $archivePath = resolve_storage_path((string) $certificate['pdf_path']);
        if (!$archivePath) { $failures[] = $number . ' - certificate file unavailable'; continue; }
        $path = $archivePath;
        try {
            $dynamicPath = certificate_render_existing_download($certificate);
            if ($dynamicPath) { $path = $dynamicPath; $dynamicFiles[] = $dynamicPath; }
        } catch (Throwable $renderError) {
            error_log('Batch dynamic render failed for certificate ' . (int) $certificate['id'] . ': ' . $renderError->getMessage());
        }
        if (!is_file($path)) { $failures[] = $number . ' - certificate PDF unavailable'; continue; }
        $sourcePaths[] = $path;
    }
    if ($failures) throw new RuntimeException("Unable to prepare batch:\n" . implode("\n", $failures));
    certificate_batch_merge_pdfs($sourcePaths, $outputPath);
    foreach ($dynamicFiles as $file) if (is_file($file)) @unlink($file);
    $dynamicFiles = [];
    [$auditFilters] = certificate_archive_filters(is_array($input['filters'] ?? null) ? $input['filters'] : []);
    audit_log((int) $user['id'], $disposition === 'inline' ? 'certificate_batch_print' : 'certificate_batch_download', 'certificate_batch', null, [
        'count' => count($certificates),
        'certificate_ids' => $ids,
        'certificate_numbers' => array_column($certificates, 'certificate_number'),
        'filters' => $auditFilters,
    ]);
    stream_download($outputPath, 'JUVA-certificates-' . gmdate('Ymd-His') . '.pdf', 'application/pdf', $disposition === 'inline');
} catch (Throwable $error) {
    certificate_batch_cleanup($workspace, $dynamicFiles);
    api_error($error->getMessage(), 422);
}