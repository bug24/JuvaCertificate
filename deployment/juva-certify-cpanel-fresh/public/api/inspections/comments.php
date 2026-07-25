<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$user = require_permission('inspections.view');
require_csrf();
$input = json_input();
$errors = validate_required($input, ['inspection_id', 'comment_text']);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}
$inspectionId = (int) $input['inspection_id'];
$inspection = fetch_inspection_for_user($inspectionId, $user);
if (!$inspection) {
    api_error('Inspection not found.', 404);
}
$type = isset($input['comment_type']) ? (string) $input['comment_type'] : 'comment';
if (!in_array($type, ['comment', 'correction', 'review', 'status'], true)) {
    $type = 'comment';
}
if ($type === 'review' && !has_permission($user, 'inspections.review')) {
    api_error('You do not have permission to add review comments.', 403);
}
$commentText = normalize_text_input((string) $input['comment_text'], true);
if ($commentText === '' || contains_markup($commentText) || text_length($commentText) > 3000) {
    api_error('Please correct the highlighted fields.', 422, ['comment_text' => 'Comment must be plain text up to 3000 characters.']);
}
$stmt = db()->prepare('INSERT INTO inspection_comments (inspection_id, user_id, comment_type, comment_text, created_at) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$inspectionId, (int) $user['id'], $type, $commentText, now_sql()]);
$id = (int) db()->lastInsertId();
audit_log((int) $user['id'], 'inspections.comment_added', 'inspection', $inspectionId, ['comment_type' => $type]);
$stmt = db()->prepare('SELECT c.*, u.name AS user_name, r.name AS role_name FROM inspection_comments c JOIN users u ON u.id = c.user_id JOIN roles r ON r.id = u.role_id WHERE c.id = ?');
$stmt->execute([$id]);
respond(['comment' => $stmt->fetch()], 201);
