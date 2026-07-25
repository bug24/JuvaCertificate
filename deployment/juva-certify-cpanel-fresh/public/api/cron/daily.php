<?php
require_once __DIR__ . '/../_bootstrap.php';

function cron_key_from_request(): string
{
    if (PHP_SAPI === 'cli') {
        global $argv;
        foreach ((array) $argv as $arg) {
            if (strpos($arg, '--key=') === 0) {
                return substr($arg, 6);
            }
        }
        return '';
    }

    return isset($_GET['key']) ? (string) $_GET['key'] : '';
}

$expectedKey = isset($config['cron_key']) ? (string) $config['cron_key'] : '';
if ($expectedKey === '' || !hash_equals($expectedKey, cron_key_from_request())) {
    api_error('Cron key is missing or invalid.', 403);
}

$pdo = db();
$today = gmdate('Y-m-d');
$reminderDays = isset($config['reminder_days']) ? max(1, (int) $config['reminder_days']) : 30;

$expiredCertificates = $pdo->prepare("UPDATE certificates SET status = 'expired', updated_at = ? WHERE status = 'valid' AND expires_at IS NOT NULL AND expires_at < ?");
$expiredCertificates->execute([now_sql(), $today]);

$expiredInspections = $pdo->prepare("UPDATE inspections SET status = 'expired', updated_at = ? WHERE status IN ('approved','issued') AND next_due_date IS NOT NULL AND next_due_date < ?");
$expiredInspections->execute([now_sql(), $today]);

$reminders = $pdo->prepare("SELECT cert.certificate_number, cert.expires_at, c.name AS client_name, c.email AS client_email, e.asset_code, e.name AS equipment_name FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id WHERE cert.status = 'valid' AND cert.expires_at BETWEEN ? AND DATE_ADD(?, INTERVAL ? DAY) ORDER BY cert.expires_at ASC LIMIT 200");
$reminders->execute([$today, $today, $reminderDays]);
$rows = $reminders->fetchAll();

$summaryLines = [
    'JUVA Certify Manager daily cron summary',
    'Date: ' . $today,
    'Certificates marked expired: ' . $expiredCertificates->rowCount(),
    'Inspections marked expired: ' . $expiredInspections->rowCount(),
    'Certificates expiring within ' . $reminderDays . ' days: ' . count($rows),
];
foreach ($rows as $row) {
    $summaryLines[] = '- ' . $row['certificate_number'] . ' | ' . $row['client_name'] . ' | ' . $row['asset_code'] . ' ' . $row['equipment_name'] . ' | expires ' . $row['expires_at'];
}

$noticeEmail = isset($config['admin_notice_email']) ? trim((string) $config['admin_notice_email']) : '';
$mailSent = false;
if ($noticeEmail !== '' && (count($rows) > 0 || $expiredCertificates->rowCount() > 0 || $expiredInspections->rowCount() > 0)) {
    $mailSent = send_app_mail($noticeEmail, 'JUVA Certify daily expiry summary', implode("\n", $summaryLines));
}

$notificationStmt = $pdo->prepare("SELECT id FROM email_notifications WHERE status IN ('pending','failed') AND attempts < max_attempts AND (next_attempt_at IS NULL OR next_attempt_at <= ?) ORDER BY id LIMIT 50");
$notificationStmt->execute([now_sql()]);
$notificationResults = ['sent' => 0, 'failed' => 0];
foreach ($notificationStmt->fetchAll() ?: [] as $notificationRow) {
    $delivery = send_notification((int) $notificationRow['id']);
    if (!empty($delivery['sent'])) $notificationResults['sent']++; else $notificationResults['failed']++;
}

audit_log(null, 'cron.daily', 'system', null, [
    'certificates_expired' => $expiredCertificates->rowCount(),
    'inspections_expired' => $expiredInspections->rowCount(),
    'reminders' => count($rows),
    'mail_sent' => $mailSent,
]);

respond([
    'date' => $today,
    'certificates_expired' => $expiredCertificates->rowCount(),
    'inspections_expired' => $expiredInspections->rowCount(),
    'reminders' => count($rows),
    'mail_sent' => $mailSent,
]);
