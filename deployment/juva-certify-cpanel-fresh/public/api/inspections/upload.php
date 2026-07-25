<?php
require_once __DIR__ . '/../_bootstrap.php';

function detect_upload_type(string $tmpPath): ?array
{
    $bytes = (string) file_get_contents($tmpPath, false, null, 0, 16);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpPath);

    if (strncmp($bytes, "\xFF\xD8\xFF", 3) === 0 && $mime === 'image/jpeg') {
        return ['mime' => 'image/jpeg', 'extension' => 'jpg'];
    }
    if (strncmp($bytes, "\x89PNG\r\n\x1A\n", 8) === 0 && $mime === 'image/png') {
        return ['mime' => 'image/png', 'extension' => 'png'];
    }
    if (substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP' && $mime === 'image/webp') {
        return ['mime' => 'image/webp', 'extension' => 'webp'];
    }
    if (strncmp($bytes, '%PDF-', 5) === 0 && $mime === 'application/pdf') {
        return ['mime' => 'application/pdf', 'extension' => 'pdf'];
    }
    return null;
}

require_method('POST');
$user = require_any_permission(['inspections.edit', 'inspections.review']);
require_csrf();
$id = isset($_POST['inspection_id']) ? (int) $_POST['inspection_id'] : 0;
$attachmentType = isset($_POST['attachment_type']) ? (string) $_POST['attachment_type'] : 'evidence';
if (!in_array($attachmentType, ['evidence', 'signature'], true)) {
    api_error('Invalid attachment type.', 422, ['attachment_type' => 'Use evidence or signature.']);
}
if ($id <= 0) {
    api_error('Inspection id is required.', 422, ['inspection_id' => 'Required.']);
}
$inspection = fetch_inspection_for_user($id, $user);
if (!$inspection) {
    api_error('Inspection not found.', 404);
}
if (in_array((string) $inspection['status'], ['issued', 'revoked', 'expired'], true)) {
    api_error('Evidence uploads are locked for this inspection status.', 422, ['inspection_id' => 'Uploads are locked after issuance or closure.']);
}
if (isset($_FILES['file']['error']) && (int) $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $code = (int) $_FILES['file']['error'];
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'The file exceeds the server upload_max_filesize limit (' . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_FORM_SIZE => 'The file exceeds the form upload limit.',
        UPLOAD_ERR_PARTIAL => 'The file upload was interrupted. Select the file and retry.',
        UPLOAD_ERR_NO_FILE => 'No file was selected.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server temporary upload directory is unavailable.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
        UPLOAD_ERR_EXTENSION => 'A server extension blocked the uploaded file.',
    ];
    api_error($messages[$code] ?? 'The upload failed before validation.', 422, ['file' => $messages[$code] ?? 'Upload failed.', 'upload_error_code' => $code]);
}
if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    api_error('No file was uploaded.', 422, ['file' => 'Required.']);
}
$file = $_FILES['file'];
if ($file['size'] > 8 * 1024 * 1024) {
    api_error('Evidence file is too large. Maximum size is 8MB.', 422, ['file' => 'Maximum 8MB.']);
}
$type = detect_upload_type($file['tmp_name']);
if (!$type) {
    api_error('Unsupported or unsafe evidence file type.', 422, ['file' => 'Use valid JPG, PNG, WEBP or PDF files only.']);
}
if (strpos((string) $type['mime'], 'image/') === 0 && !validate_image_dimensions($file['tmp_name'])) {
    api_error('Image dimensions are unsafe or too large.', 422, ['file' => 'Use an image no larger than 40 megapixels.']);
}
if ($attachmentType === 'signature' && strpos((string) $type['mime'], 'image/') !== 0) {
    api_error('Inspection signatures must be PNG, JPG or WEBP images.', 422, ['file' => 'Use a valid image signature.']);
}
if ($attachmentType === 'evidence') {
    $rules = db()->prepare('SELECT ft.allowed_evidence_types FROM inspections i JOIN form_templates ft ON ft.id=i.form_template_id WHERE i.id=? LIMIT 1');
    $rules->execute([$id]);
    $allowed = array_filter(array_map('trim', explode(',', (string) ($rules->fetchColumn() ?: 'image/jpeg,image/png,image/webp,application/pdf'))));
    if (!in_array((string) $type['mime'], $allowed, true)) {
        api_error('This evidence file type is not allowed by the active certificate template.', 422, ['file' => 'Allowed: ' . implode(', ', $allowed)]);
    }
}
$dir = ensure_private_storage_dir('evidence/' . $id);
$safeName = gmdate('YmdHis') . '-' . random_token(4) . '.' . $type['extension'];
$target = $dir . DIRECTORY_SEPARATOR . $safeName;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    api_error('Unable to save evidence file.', 500);
}
$hash = hash_file('sha256', $target);
$key = storage_key('evidence/' . $id, $safeName);
$originalName = normalize_text_input((string) $file['name']);
if ($originalName === '' || text_length($originalName) > 255 || contains_markup($originalName)) {
    $originalName = basename($safeName);
}
$stmt = db()->prepare('INSERT INTO inspection_attachments (inspection_id, uploaded_by, attachment_type, file_name, file_path, mime_type, file_size, file_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$id, (int) $user['id'], $attachmentType, $originalName, $key, $type['mime'], (int) $file['size'], $hash, now_sql()]);
$attachmentId = (int) db()->lastInsertId();
audit_log((int) $user['id'], 'inspections.evidence_uploaded', 'inspection', $id, ['attachment_id' => $attachmentId]);
respond(['attachment' => ['id' => $attachmentId, 'attachment_type' => $attachmentType, 'file_name' => $originalName, 'mime_type' => $type['mime'], 'file_size' => (int) $file['size'], 'download_url' => api_url('inspections/attachment.php?id=' . $attachmentId)]], 201);

