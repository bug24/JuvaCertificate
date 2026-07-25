<?php
require_once __DIR__ . '/../_bootstrap.php';

function signature_image_type(string $path): ?array {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    $map = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    return isset($map[$mime]) ? ['mime' => $mime, 'extension' => $map[$mime]] : null;
}

require_methods(['POST']);
$actor = require_auth();
require_csrf();
$targetId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int) $actor['id'];
$canManage = has_permission($actor, 'users.manage') || has_permission($actor, 'users.manage_signatures');
if ($targetId !== (int) $actor['id'] && !$canManage) api_error('You cannot manage this signature.', 403);
$target = fetch_user($targetId);
if (!$target) api_error('User not found.', 404);

$action = isset($_POST['action']) ? (string) $_POST['action'] : 'upload';
if ($action === 'remove' || $action === 'deactivate') {
    $active = $action === 'deactivate' ? 0 : 0;
    if ($action === 'remove' && !empty($target['signature_path'])) {
        $used = db()->prepare('SELECT COUNT(*) FROM certificate_revisions WHERE inspector_signature_path_snapshot=? OR authenticator_signature_path_snapshot=?');
        $used->execute([(string) $target['signature_path'], (string) $target['signature_path']]);
        if ((int) $used->fetchColumn() === 0) {
            $path = resolve_storage_path((string) $target['signature_path']);
            if ($path) @unlink($path);
        }
    }
    $sql = $action === 'remove'
        ? 'UPDATE users SET signature_path=NULL,signature_original_name=NULL,signature_mime_type=NULL,signature_uploaded_at=NULL,signature_uploaded_by=NULL,signature_is_active=0,updated_at=? WHERE id=?'
        : 'UPDATE users SET signature_is_active=?,updated_at=? WHERE id=?';
    $stmt = db()->prepare($sql);
    $action === 'remove' ? $stmt->execute([now_sql(), $targetId]) : $stmt->execute([$active, now_sql(), $targetId]);
    audit_log((int) $actor['id'], 'users.signature_' . $action, 'user', $targetId);
    respond(['message' => $action === 'remove' ? 'Signature removed.' : 'Signature deactivated.']);
}
if ($action === 'activate') {
    db()->prepare('UPDATE users SET signature_is_active=1,updated_at=? WHERE id=? AND signature_path IS NOT NULL')->execute([now_sql(), $targetId]);
    audit_log((int) $actor['id'], 'users.signature_activated', 'user', $targetId);
    respond(['message' => 'Signature activated.']);
}
if (empty($_FILES['file']) || (int) $_FILES['file']['error'] !== UPLOAD_ERR_OK) api_error('Select a signature image to upload.', 422, ['file' => 'PNG, JPG or WEBP required.']);
$file = $_FILES['file'];
if ((int) $file['size'] > 3 * 1024 * 1024) api_error('Signature image must not exceed 3MB.', 422);
$type = signature_image_type($file['tmp_name']);
if (!$type) api_error('Use a valid PNG, JPG or WEBP signature image.', 422);
if (!validate_image_dimensions($file['tmp_name'])) api_error('Image dimensions are unsafe or too large.', 422);
$dir = ensure_private_storage_dir('user-signatures/' . $targetId);
$name = gmdate('YmdHis') . '-' . random_token(4) . '.' . $type['extension'];
$path = $dir . DIRECTORY_SEPARATOR . $name;
if (!move_uploaded_file($file['tmp_name'], $path)) api_error('Unable to store signature image.', 500);
$key = storage_key('user-signatures/' . $targetId, $name);
$original = normalize_text_input((string) $file['name']);
db()->prepare('UPDATE users SET signature_path=?,signature_original_name=?,signature_mime_type=?,signature_uploaded_at=?,signature_uploaded_by=?,signature_is_active=1,updated_at=? WHERE id=?')->execute([$key,$original,$type['mime'],now_sql(),(int)$actor['id'],now_sql(),$targetId]);
audit_log((int) $actor['id'], 'users.signature_uploaded', 'user', $targetId, ['mime_type' => $type['mime']]);
respond(['message' => 'Certificate signature uploaded.', 'signature_url' => api_url('users/signature-download.php?id=' . $targetId)]);
