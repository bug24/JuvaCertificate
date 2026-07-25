<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$actor = require_permission('users.manage');
require_csrf();
$input = json_input();
$errors = validate_required($input, ['id']);
if ($errors) {
    api_error('User id is required.', 422, $errors);
}
$target = fetch_user((int) $input['id']);
if (!$target) {
    api_error('User not found.', 404);
}
$stmt = db()->prepare("UPDATE auth_tokens SET revoked_at = ? WHERE user_id = ? AND type = 'invite' AND used_at IS NULL AND revoked_at IS NULL");
$stmt->execute([now_sql(), $target['id']]);
$token = create_auth_token((int) $target['id'], 'invite', 7 * 86400, (int) $actor['id']);
$link = app_url('/reset-password?token=' . urlencode($token));
send_app_mail($target['email'], 'Your JUVA Certify Manager invitation', "Set your JUVA Certify Manager password with this link within 7 days.\n\n{$link}");
$mark = db()->prepare('UPDATE users SET status = ?, invited_at = ?, updated_at = ? WHERE id = ? AND status <> ?');
$mark->execute(['invited', now_sql(), now_sql(), $target['id'], 'active']);
audit_log((int) $actor['id'], 'users.invitation_resent', 'user', (int) $target['id']);
respond(['message' => 'Invitation resent.']);
