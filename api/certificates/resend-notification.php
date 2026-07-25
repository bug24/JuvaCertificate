<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$user = require_auth();
if (!has_permission($user, 'certificates.issue') && !privileged_role((string) $user['role']['slug'])) api_error('You do not have permission to resend certificate email.', 403);
require_csrf();
consume_rate_limit('notification_resend', (string) $user['id'], 20, 600);
$input = json_input();
$id = (int) ($input['notification_id'] ?? 0);
if ($id <= 0) api_error('Notification id is required.', 422, ['notification_id' => 'Required.']);
$stmt = db()->prepare("UPDATE email_notifications SET status='pending', attempts=0, last_error=NULL, next_attempt_at=?, updated_at=? WHERE id=?");
$stmt->execute([now_sql(), now_sql(), $id]);
if ($stmt->rowCount() !== 1) api_error('Notification not found.', 404);
audit_log((int) $user['id'], 'notifications.manual_resend', 'notification', $id);
$result = send_notification($id, (int) $user['id']);
respond(['notification_id' => $id, 'sent' => (bool) ($result['sent'] ?? false), 'error' => $result['error'] ?? null]);
