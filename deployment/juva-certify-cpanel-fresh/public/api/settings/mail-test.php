<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$user = require_auth();
if ((string) $user['role']['slug'] !== 'super-admin') api_error('Only a Super Administrator can test mail settings.', 403);
require_csrf();
consume_rate_limit('mail_test', (string) $user['id'], 5, 600);
$input = json_input();
$to = strtolower(trim((string) ($input['email'] ?? $user['email'] ?? '')));
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) api_error('Enter a valid test recipient.', 422, ['email' => 'Enter a valid email address.']);
$result = send_app_mail_detailed($to, 'JUVA Certify Manager SMTP test', 'This test confirms that JUVA Certify Manager can deliver authenticated email. No action is required.', '<p>This test confirms that <b>JUVA Certify Manager</b> can deliver authenticated email.</p><p>No action is required.</p>');
audit_log((int) $user['id'], $result['sent'] ? 'settings.mail_test_sent' : 'settings.mail_test_failed', 'system', null, ['recipient' => mask_email($to), 'error' => $result['error']]);
if (!$result['sent']) api_error('Test email failed: ' . (string) $result['error'], 502);
respond(['sent' => true, 'recipient' => mask_email($to)]);
