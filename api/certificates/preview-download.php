<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('GET');
require_auth();
$token = isset($_GET['token']) ? strtolower(trim((string) $_GET['token'])) : '';
if (!preg_match('/^[a-f0-9]{48}$/', $token)) api_error('Certificate preview was not found or has expired.', 404);
$preview = $_SESSION['certificate_previews'][$token] ?? null;
if (!is_array($preview) || (int) ($preview['expires_at'] ?? 0) < time()) {
    unset($_SESSION['certificate_previews'][$token]);
    api_error('Certificate preview was not found or has expired.', 404);
}
$previewRoot = realpath(private_storage_root() . DIRECTORY_SEPARATOR . 'previews');
$resolved = realpath((string) ($preview['path'] ?? ''));
if ($previewRoot === false || $resolved === false || strpos($resolved, $previewRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($resolved)) {
    unset($_SESSION['certificate_previews'][$token]);
    api_error('Certificate preview file is unavailable. Generate a new preview.', 404);
}
stream_download($resolved, (string) ($preview['download_name'] ?? 'certificate-preview.pdf'), 'application/pdf', true);
