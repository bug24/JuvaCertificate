<?php

declare(strict_types=1);
function has_permission(array $user, string $permission): bool { return in_array('*', $user['permissions'] ?? [], true) || in_array($permission, $user['permissions'] ?? [], true); }
require_once dirname(__DIR__) . '/api/lib/certificate_archive.php';
function private_storage_root(): string { return sys_get_temp_dir(); }
require_once dirname(__DIR__) . '/api/lib/certificate_pdf_batch.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void { if (!$condition) $failures[] = $message; };

$fixtures = ['A' => '2026-02-22', 'B' => '2026-02-23', 'C' => '2026-02-23', 'D' => '2026-02-24'];
$exact = array_keys(array_filter($fixtures, static function (string $date): bool { return certificate_archive_date_matches($date, '2026-02-23', '2026-02-23'); }));
$range = array_keys(array_filter($fixtures, static function (string $date): bool { return certificate_archive_date_matches($date, '2026-02-22', '2026-02-24'); }));
$assert($exact === ['B', 'C'], 'Exact examination-date filtering must return B and C only.');
$assert($range === ['A', 'B', 'C', 'D'], 'Inclusive examination-date filtering must return A through D.');
[$filters, $errors] = certificate_archive_filters(['inspection_from' => '2026-02-23', 'inspection_to' => '2026-02-23']);
$assert(!$errors, 'Valid exact date filters must pass validation.');
[$where, $params] = certificate_archive_where($filters, ['role' => ['permissions' => ['certificates.view']]]);
$assert(strpos($where, 'i.inspection_date >= ?') !== false && strpos($where, 'i.inspection_date <= ?') !== false, 'Archive SQL must filter on inspections.inspection_date inclusively.');
$assert($params === ['2026-02-23', '2026-02-23'], 'Exact date parameters must remain local ISO dates without timezone conversion.');

$assert(certificate_batch_ids([2, 5, 9]) === [2, 5, 9], 'Manual certificate selection order must be preserved.');
try { certificate_batch_ids([2, 2]); $failures[] = 'Duplicate certificate IDs must be rejected.'; } catch (InvalidArgumentException $expected) {}
try { certificate_batch_ids(range(1, 251)); $failures[] = 'Oversized certificate ID payloads must be rejected.'; } catch (InvalidArgumentException $expected) {}

$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'juva-batch-test-' . bin2hex(random_bytes(6));
mkdir($tmp, 0775, true);
$sources = [];
$specs = [
    ['Shackles', 'L', [297, 210]],
    ['Chain Block', 'P', [210, 297]],
    ['Flat Webbing Sling', 'L', [280, 200]],
    ['CCU Visual Inspection', 'P', [180, 260]],
];
try {
    foreach ($specs as $index => $spec) {
        $path = $tmp . DIRECTORY_SEPARATOR . 'category-' . ($index + 1) . '.pdf';
        $pdf = new FPDF($spec[1], 'mm', $spec[2]);
        $pdf->AddPage($spec[1], $spec[2]);
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 20, $spec[0] . ' Certificate', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        $pdf->MultiCell(0, 7, 'JUVA test certificate. Native vector text and category layout are preserved.');
        $pdf->Rect(12, 45, 35, 35);
        $pdf->Output('F', $path);
        $sources[] = $path;
    }
    $combined = $tmp . DIRECTORY_SEPARATOR . 'mixed-category-combined.pdf';
    certificate_batch_merge_pdfs($sources, $combined);
    $reader = new setasign\Fpdi\Fpdi();
    $count = $reader->setSourceFile($combined);
    $assert($count === 4, 'Mixed-category combined PDF must contain all four certificate pages.');
    foreach ($specs as $index => $spec) {
        $template = $reader->importPage($index + 1);
        $size = $reader->getTemplateSize($template);
        $expectedLandscape = $spec[1] === 'L';
        $assert(($size['width'] > $size['height']) === $expectedLandscape, 'Page ' . ($index + 1) . ' orientation must be preserved.');
        $assert(abs($size['width'] - $spec[2][0]) < 1 && abs($size['height'] - $spec[2][1]) < 1, 'Page ' . ($index + 1) . ' dimensions must be preserved.');
    }    $largeCombined = $tmp . DIRECTORY_SEPARATOR . 'large-batch-combined.pdf';
    certificate_batch_merge_pdfs(array_fill(0, 105, $sources[0]), $largeCombined);
    $largeReader = new setasign\Fpdi\Fpdi();
    $assert($largeReader->setSourceFile($largeCombined) === 105, 'Batch merger must support real workloads above 100 certificates.');
    if (getenv('JUVA_BATCH_KEEP_FIXTURE') === '1') {
        $outputDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'pdf';
        if (!is_dir($outputDir)) mkdir($outputDir, 0775, true);
        copy($combined, $outputDir . DIRECTORY_SEPARATOR . 'mixed-category-batch-preview.pdf');
    }
} finally {
    foreach (glob($tmp . DIRECTORY_SEPARATOR . '*') ?: [] as $file) @unlink($file);
    @rmdir($tmp);
}

$endpoint = file_get_contents(dirname(__DIR__) . '/api/certificates/batch.php');
$indexSource = file_get_contents(dirname(__DIR__) . '/api/certificates/index.php');
$uiSource = file_get_contents(dirname(__DIR__) . '/src/CertificateRegisterPage.tsx');
$assert(strpos($endpoint, 'require_auth()') !== false && strpos($endpoint, 'require_csrf()') !== false, 'Batch endpoint must enforce authentication and CSRF.');
$assert(strpos($endpoint, 'certificate_archive_scope') !== false, 'Batch endpoint must enforce the same certificate access scope.');
$assert(strpos($endpoint, 'certificate_render_existing_download') !== false && strpos($endpoint, 'resolve_storage_path') !== false, 'Batch endpoint must use the trusted individual render/archive path.');
$assert(strpos($endpoint, 'audit_log') !== false, 'Batch endpoint must record an audit event.');
$assert(!preg_match('/\b(?:UPDATE|DELETE\s+FROM|INSERT\s+INTO)\s+(?:certificates|certificate_revisions|inspections)\b/i', $endpoint), 'Batch endpoint must not mutate certificate lifecycle records.');
$assert(strpos($indexSource, 'i.inspection_date') !== false, 'Archive response must expose the examination date.');
$assert(strpos($uiSource, 'Select all visible results') !== false && strpos($uiSource, 'Clear selection') !== false, 'Archive UI must expose visible-page selection and clear selection.');
$assert(strpos($uiSource, 'Download Combined PDF') !== false && strpos($uiSource, 'Print Selected') !== false, 'Archive UI must expose both combined-PDF actions.');$assert(strpos($uiSource, '/inspections/clone.php') !== false && strpos($uiSource, 'Create revision') !== false, 'Clone/Renew and Create Revision workflows must remain available.');

if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "Certificate batch tests passed: date filters, selection validation, mixed page sizes/orientations, authorization and no-mutation contracts." . PHP_EOL;