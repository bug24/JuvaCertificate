<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$user = require_any_permission(['inspections.edit', 'inspections.review']);
require_csrf();
$body = json_body();
$attachmentId = isset($body['attachment_id']) ? (int) $body['attachment_id'] : 0;
if ($attachmentId < 1) {
    api_error('Attachment id is required.', 422, ['attachment_id' => 'Required.']);
}

$stmt = db()->prepare('SELECT a.*, i.status FROM inspection_attachments a JOIN inspections i ON i.id=a.inspection_id WHERE a.id=? LIMIT 1');
$stmt->execute([$attachmentId]);
$attachment = $stmt->fetch();
if (!$attachment || !fetch_inspection_for_user((int) $attachment['inspection_id'], $user)) {
    api_error('Attachment not found.', 404);
}
if (!in_array((string) $attachment['status'], ['draft', 'correction'], true)) {
    api_error('Attachments can only be removed from draft or returned inspections.', 422);
}

$delete = db()->prepare('DELETE FROM inspection_attachments WHERE id=? AND inspection_id=?');
$delete->execute([$attachmentId, (int) $attachment['inspection_id']]);
$path = resolve_storage_path((string) $attachment['file_path']);
if ($path && is_file($path)) {
    @unlink($path);
}
audit_log((int) $user['id'], 'inspections.attachment_removed', 'inspection', (int) $attachment['inspection_id'], [
    'attachment_id' => $attachmentId,
    'attachment_type' => (string) $attachment['attachment_type'],
    'file_name' => (string) $attachment['file_name'],
]);
respond(['removed' => true, 'attachment_id' => $attachmentId]);
