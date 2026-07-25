<?php

declare(strict_types=1);

function mailer_autoload(): bool
{
    static $loaded = null;
    if ($loaded !== null) return $loaded;
    $path = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (is_file($path)) require_once $path;
    return $loaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

function send_app_mail_detailed(string $to, string $subject, string $textBody, ?string $htmlBody = null): array
{
    global $config;
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return ['sent' => false, 'error' => 'Invalid recipient address.'];
    $transport = strtolower((string) ($config['mail_transport'] ?? 'mail'));
    if ($transport === 'log') {
        $dir = private_storage_root() . DIRECTORY_SEPARATOR . 'mail-log';
        if (!is_dir($dir)) mkdir($dir, 0770, true);
        $content = "To: {$to}\nSubject: {$subject}\n\n{$textBody}";
        $ok = file_put_contents($dir . DIRECTORY_SEPARATOR . gmdate('Ymd-His') . '-' . random_token(3) . '.txt', $content) !== false;
        return ['sent' => $ok, 'error' => $ok ? null : 'Unable to write mail log.'];
    }
    if ($transport === 'smtp') {
        if (!mailer_autoload()) return ['sent' => false, 'error' => 'PHPMailer is not installed. Run composer install.'];
        try {
            $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = (string) ($config['smtp_host'] ?? '');
            $mailer->Port = (int) ($config['smtp_port'] ?? 587);
            $mailer->SMTPAuth = true;
            $mailer->Username = (string) ($config['smtp_username'] ?? '');
            $mailer->Password = (string) ($config['smtp_password'] ?? '');
            $encryption = strtolower((string) ($config['smtp_encryption'] ?? 'tls'));
            if ($encryption === 'ssl' || $encryption === 'smtps') $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            elseif ($encryption === 'tls' || $encryption === 'starttls') $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            else $mailer->SMTPSecure = '';
            $mailer->Timeout = max(5, min(60, (int) ($config['smtp_timeout'] ?? 15)));
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom((string) $config['mail_from'], (string) $config['mail_from_name']);
            $mailer->addReplyTo((string) ($config['mail_reply_to'] ?? $config['mail_from']));
            $mailer->addAddress($to);
            $mailer->Subject = $subject;
            $mailer->Body = $htmlBody ?: nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8'));
            $mailer->AltBody = $textBody;
            $mailer->isHTML(true);
            $mailer->send();
            return ['sent' => true, 'error' => null];
        } catch (Throwable $error) {
            return ['sent' => false, 'error' => substr($error->getMessage(), 0, 500)];
        }
    }
    $headers = [
        'From: ' . (string) $config['mail_from_name'] . ' <' . (string) $config['mail_from'] . '>',
        'Reply-To: ' . (string) ($config['mail_reply_to'] ?? $config['mail_from']),
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $sent = mail($to, $subject, $textBody, implode("\r\n", $headers));
    return ['sent' => $sent, 'error' => $sent ? null : 'PHP mail() rejected the message.'];
}

function notification_recipients(int $clientId): array
{
    $stmt = db()->prepare("SELECT email, name FROM clients WHERE id = ? UNION SELECT email, name FROM users WHERE client_id = ? AND status = 'active' AND email IS NOT NULL");
    $stmt->execute([$clientId, $clientId]);
    $recipients = [];
    foreach ($stmt->fetchAll() ?: [] as $row) {
        $email = strtolower(trim((string) ($row['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) $recipients[$email] = ['email' => $email, 'name' => (string) ($row['name'] ?? '')];
    }
    return array_values($recipients);
}

function queue_certificate_notifications(int $certificateId, int $revision, string $eventType): array
{
    global $config;
    if (empty($config['certificate_notifications_enabled'])) return ['state' => 'disabled', 'ids' => []];
    $stmt = db()->prepare('SELECT cert.id, cert.certificate_number, i.client_id FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id WHERE cert.id = ?');
    $stmt->execute([$certificateId]);
    $certificate = $stmt->fetch();
    if (!$certificate) return ['state' => 'not_found', 'ids' => []];
    $recipients = notification_recipients((int) $certificate['client_id']);
    if (!$recipients) return ['state' => 'no_recipient', 'ids' => []];
    $insert = db()->prepare("INSERT INTO email_notifications (certificate_id, revision, event_type, recipient_email, recipient_name, status, next_attempt_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
    $ids = [];
    foreach ($recipients as $recipient) {
        $now = now_sql();
        $insert->execute([$certificateId, $revision, $eventType, $recipient['email'], $recipient['name'], $now, $now, $now]);
        $ids[] = (int) db()->lastInsertId();
    }
    return ['state' => 'queued', 'ids' => array_values(array_unique(array_filter($ids)))];
}

function notification_message(array $row): array
{
    $verify = app_url('/verify/' . (string) $row['verification_token']);
    $search = app_url('/verify');
    $download = api_url('certificates/download.php?token=' . (string) $row['verification_token']);
    $subject = 'JUVA certificate issued: ' . (string) $row['certificate_number'];
    $text = "A JUVA Oil inspection certificate has been issued.\n\nCertificate: {$row['certificate_number']}\nClient: {$row['client_name']}\nEquipment: {$row['asset_code']} - {$row['equipment_name']}\nCategory: {$row['category_name']}\nIssue date: {$row['issued_at']}\nExpiry date: {$row['expires_at']}\n\nView/download: {$download}\nVerify this certificate: {$verify}\nVerification search: {$search}\n\nVerify authenticity before acceptance or reliance.";
    $html = '<div style="font-family:Arial,sans-serif;max-width:640px;color:#171717"><h2 style="margin-bottom:4px">Certificate issued</h2><p>JUVA Oil Services has issued the certificate below.</p><table style="border-collapse:collapse;width:100%"><tr><td><b>Certificate</b></td><td>' . htmlspecialchars((string) $row['certificate_number']) . '</td></tr><tr><td><b>Client</b></td><td>' . htmlspecialchars((string) $row['client_name']) . '</td></tr><tr><td><b>Equipment</b></td><td>' . htmlspecialchars((string) $row['asset_code'] . ' - ' . (string) $row['equipment_name']) . '</td></tr><tr><td><b>Category</b></td><td>' . htmlspecialchars((string) $row['category_name']) . '</td></tr><tr><td><b>Issue / Expiry</b></td><td>' . htmlspecialchars((string) $row['issued_at'] . ' / ' . (string) $row['expires_at']) . '</td></tr></table><p><a href="' . htmlspecialchars($download) . '">View or download certificate</a> &nbsp; <a href="' . htmlspecialchars($verify) . '">Verify authenticity</a></p><p>Certificate ID: <b>' . htmlspecialchars((string) $row['certificate_number']) . '</b>. You may also enter it at <a href="' . htmlspecialchars($search) . '">the verification portal</a>.</p><p style="color:#8b1d1d"><b>Verify authenticity before acceptance or reliance.</b></p></div>';
    return [$subject, $text, $html];
}

function send_notification(int $id, ?int $actorId = null): array
{
    $claim = db()->prepare("UPDATE email_notifications SET status = 'sending', attempts = attempts + 1, updated_at = ? WHERE id = ? AND status IN ('pending','failed')");
    $claim->execute([now_sql(), $id]);
    if ($claim->rowCount() !== 1) return ['sent' => false, 'skipped' => true];
    $stmt = db()->prepare('SELECT n.*, cert.certificate_number, cert.verification_token, cert.issued_at, cert.expires_at, c.name client_name, e.asset_code, e.name equipment_name, cat.name category_name FROM email_notifications n JOIN certificates cert ON cert.id=n.certificate_id JOIN inspections i ON i.id=cert.inspection_id JOIN clients c ON c.id=i.client_id JOIN equipment e ON e.id=i.equipment_id JOIN certification_categories cat ON cat.id=i.category_id WHERE n.id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return ['sent' => false, 'skipped' => true];
    [$subject, $text, $html] = notification_message($row);
    $result = send_app_mail_detailed((string) $row['recipient_email'], $subject, $text, $html);
    if ($result['sent']) {
        db()->prepare("UPDATE email_notifications SET status='sent', sent_at=?, last_error=NULL, next_attempt_at=NULL, updated_at=? WHERE id=?")->execute([now_sql(), now_sql(), $id]);
        audit_log($actorId, 'notifications.sent', 'certificate', (int) $row['certificate_id'], ['notification_id' => $id, 'recipient' => mask_email((string) $row['recipient_email'])]);
    } else {
        $delayMinutes = min(1440, (int) pow(2, min(10, (int) $row['attempts'])) * 5);
        $terminal = (int) $row['attempts'] >= (int) $row['max_attempts'];
        db()->prepare("UPDATE email_notifications SET status='failed', last_error=?, next_attempt_at=?, updated_at=? WHERE id=?")->execute([(string) $result['error'], $terminal ? null : gmdate('Y-m-d H:i:s', time() + $delayMinutes * 60), now_sql(), $id]);
        audit_log($actorId, 'notifications.failed', 'certificate', (int) $row['certificate_id'], ['notification_id' => $id, 'error' => (string) $result['error']]);
    }
    return $result;
}

function mask_email(string $email): string
{
    $parts = explode('@', $email, 2);
    return substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '');
}
