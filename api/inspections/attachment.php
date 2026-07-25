<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$user = require_permission('inspections.view');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    api_error('Attachment id is required.', 422, ['id' => 'Required.']);
}

$stmt = db()->prepare('SELECT a.*, i.id AS inspection_id FROM inspection_attachments a JOIN inspections i ON i.id = a.inspection_id WHERE a.id = ? LIMIT 1');
$stmt->execute([$id]);
$attachment = $stmt->fetch();
if (!$attachment) {
    api_error('File not found.', 404);
}

$inspection = fetch_inspection_for_user((int) $attachment['inspection_id'], $user, 'i.id');
if (!$inspection) {
    api_error('File not found.', 404);
}

$path = resolve_storage_path((string) $attachment['file_path']);
if (!$path) {
    api_error('File not found.', 404);
}

$downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $attachment['file_name']);
stream_download($path, $downloadName !== '' ? $downloadName : ('attachment-' . $id), (string) $attachment['mime_type'], true);
