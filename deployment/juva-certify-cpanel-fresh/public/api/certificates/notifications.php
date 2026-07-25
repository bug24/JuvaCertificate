<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$user = require_auth();
if (!has_permission($user, 'certificates.view') && !has_permission($user, 'certificates.own.view')) api_error('You do not have permission to view notifications.', 403);
$certificateId = (int) ($_GET['certificate_id'] ?? 0);
if ($certificateId <= 0) api_error('Certificate id is required.', 422, ['certificate_id' => 'Required.']);
$scope = db()->prepare('SELECT i.client_id FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id WHERE cert.id=?');
$scope->execute([$certificateId]);
$clientId = $scope->fetchColumn();
if (!$clientId) api_error('Certificate not found.', 404);
if (has_permission($user, 'certificates.own.view') && !has_permission($user, 'certificates.view') && (int) ($user['client_id'] ?? 0) !== (int) $clientId) api_error('Certificate not found.', 404);
$stmt = db()->prepare('SELECT id, revision, event_type, recipient_email, recipient_name, status, attempts, max_attempts, last_error, next_attempt_at, sent_at, created_at FROM email_notifications WHERE certificate_id=? ORDER BY id DESC');
$stmt->execute([$certificateId]);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) { $row['recipient_email'] = mask_email((string) $row['recipient_email']); if (!has_permission($user, 'certificates.view')) $row['last_error'] = null; }
unset($row);
respond(['notifications' => $rows]);
