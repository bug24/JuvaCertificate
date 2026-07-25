<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$user = require_permission('certificates.issue');
require_csrf();
$input = json_input();
$id = isset($input['id']) ? (int) $input['id'] : 0;
if ($id <= 0) {
    api_error('Certificate id is required.', 422, ['id' => 'Required.']);
}
$reason = isset($input['reason']) ? normalize_text_input((string) $input['reason'], true) : 'Revoked by authorized user.';
if ($reason === '' || contains_markup($reason) || text_length($reason) > 3000) {
    api_error('Please correct the highlighted fields.', 422, ['reason' => 'Provide a plain-text reason up to 3000 characters.']);
}
$stmt = db()->prepare('SELECT cert.*, i.status AS inspection_status, i.inspector_id FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id WHERE cert.id = ? LIMIT 1');
$stmt->execute([$id]);
$certificate = $stmt->fetch();
if (!$certificate) {
    api_error('Certificate not found.', 404);
}
$currentStatus = (string) $certificate['status'];
if ($currentStatus === 'revoked') {
    api_error('Certificate is already revoked.', 422, ['id' => 'Certificate already revoked.']);
}
if ((int) $certificate['inspector_id'] === (int) $user['id']) {
    api_error('Inspectors cannot revoke certificates for their own inspections.', 403);
}
$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('UPDATE certificates SET status = ?, revoked_at = ?, revocation_reason = ?, updated_at = ? WHERE id = ?');
    $stmt->execute(['revoked', now_sql(), $reason, now_sql(), $id]);
    $inspection = $pdo->prepare('UPDATE inspections SET status = ?, updated_at = ? WHERE id = ?');
    $inspection->execute(['revoked', now_sql(), (int) $certificate['inspection_id']]);
    $comment = $pdo->prepare('INSERT INTO inspection_comments (inspection_id, user_id, comment_type, comment_text, created_at) VALUES (?, ?, ?, ?, ?)');
    $comment->execute([(int) $certificate['inspection_id'], (int) $user['id'], 'status', 'Certificate revoked: ' . $reason, now_sql()]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    api_error('Unable to revoke certificate.', 500);
}
audit_log((int) $user['id'], 'certificates.revoked', 'certificate', $id, ['reason' => $reason]);
respond(['message' => 'Certificate revoked.']);
