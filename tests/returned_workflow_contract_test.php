<?php
$_SERVER['REQUEST_METHOD'] = 'CLI';

$root = dirname(__DIR__);
$statusSource = file_get_contents($root . '/api/inspections/status.php');
$previewSource = file_get_contents($root . '/api/certificates/preview.php');
$previewDownloadSource = file_get_contents($root . '/api/certificates/preview-download.php');
$uploadSource = file_get_contents($root . '/api/inspections/upload.php');

$checks = array(
    'RETURNED CREATES REVISION' => strpos($statusSource, '$createRevision =') !== false && strpos($statusSource, "\$current === 'correction'") !== false,
    'PREVIEW RETURNS GUARDED URL' => strpos($previewSource, "'preview_url' => api_url('certificates/preview-download.php?token='") !== false,
    'PREVIEW DOWNLOAD REQUIRES AUTH' => strpos($previewDownloadSource, 'require_auth();') !== false,
    'PREVIEW PATH IS CONFINED' => strpos($previewDownloadSource, 'private_storage_root()') !== false,
    'UPLOAD TYPE IS VALIDATED' => strpos($uploadSource, "['evidence', 'signature']") !== false,
    'UPLOAD RETURNS STORED METADATA' => strpos($uploadSource, "'attachment' => ['id' => \$attachmentId") !== false,
);

$failed = 0;
foreach ($checks as $name => $passed) {
    echo $name . ': ' . ($passed ? 'PASS' : 'FAIL') . PHP_EOL;
    if (!$passed) $failed++;
}
exit($failed > 0 ? 1 : 0);
