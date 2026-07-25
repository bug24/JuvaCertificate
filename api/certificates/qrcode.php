<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
rate_limit_or_fail('qrcode_public', client_ip(), 60, 600);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$token = isset($_GET['token']) ? strtolower(normalize_text_input((string) $_GET['token'])) : '';
$certificate = null;

if ($token !== '') {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        api_error('File not found.', 404);
    }
    $stmt = db()->prepare('SELECT cert.* FROM certificates cert WHERE cert.verification_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $certificate = $stmt->fetch();
} elseif ($id > 0) {
    $user = require_auth();
    if (!has_permission($user, 'certificates.view') && !has_permission($user, 'certificates.own.view')) {
        api_error('You do not have permission to view certificates.', 403);
    }
    $stmt = db()->prepare('SELECT cert.*, i.client_id FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id WHERE cert.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $certificate = $stmt->fetch();
    if ($certificate && has_permission($user, 'certificates.own.view') && !has_permission($user, 'certificates.view') && !empty($user['client_id']) && (int) $certificate['client_id'] !== (int) $user['client_id']) {
        api_error('File not found.', 404);
    }
} else {
    api_error('Certificate id or token is required.', 422, ['id' => 'Required.']);
}

if (!$certificate) {
    api_error('File not found.', 404);
}

$path = resolve_storage_path((string) $certificate['barcode_path']);
if (!$path) {
    api_error('File not found.', 404);
}

$downloadName = preg_replace('/[^A-Z0-9._-]/', '_', (string) $certificate['certificate_number']) . '-rev-' . (int) $certificate['revision'] . '-qr.svg';
stream_download($path, $downloadName, 'image/svg+xml', true);
