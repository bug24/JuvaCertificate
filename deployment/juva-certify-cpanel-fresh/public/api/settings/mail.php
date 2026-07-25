<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$user = require_auth();
if ((string) $user['role']['slug'] !== 'super-admin') api_error('Only a Super Administrator can view mail settings.', 403);
respond(['mail' => [
    'transport' => (string) ($config['mail_transport'] ?? 'mail'),
    'from' => (string) ($config['mail_from'] ?? ''),
    'reply_to' => (string) ($config['mail_reply_to'] ?? ''),
    'smtp_host_configured' => trim((string) ($config['smtp_host'] ?? '')) !== '',
    'smtp_username_configured' => trim((string) ($config['smtp_username'] ?? '')) !== '',
    'smtp_password_configured' => trim((string) ($config['smtp_password'] ?? '')) !== '',
    'smtp_port' => (int) ($config['smtp_port'] ?? 587),
    'smtp_encryption' => (string) ($config['smtp_encryption'] ?? 'tls'),
    'notifications_enabled' => !empty($config['certificate_notifications_enabled']),
    'phpmailer_available' => mailer_autoload(),
]]);
