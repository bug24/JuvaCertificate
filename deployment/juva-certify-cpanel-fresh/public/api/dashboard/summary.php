<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
require_auth();
$pdo = db();
$counts = [];
foreach ([
    'total_certificates' => 'SELECT COUNT(*) AS total FROM certificates',
    'pending_inspections' => "SELECT COUNT(*) AS total FROM inspections WHERE status IN ('draft','submitted','correction')",
    'expiring_soon' => "SELECT COUNT(*) AS total FROM certificates WHERE status = 'valid' AND expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
    'expired' => "SELECT COUNT(*) AS total FROM certificates WHERE status = 'expired' OR (status = 'valid' AND expires_at < CURDATE())",
    'revoked' => "SELECT COUNT(*) AS total FROM certificates WHERE status = 'revoked'",
    'active_clients' => "SELECT COUNT(*) AS total FROM clients WHERE status = 'active'",
    'registered_equipment' => 'SELECT COUNT(*) AS total FROM equipment',
    'verification_scans' => 'SELECT COUNT(*) AS total FROM verification_logs',
] as $key => $sql) {
    $row = $pdo->query($sql)->fetch();
    $counts[$key] = (int) $row['total'];
}
$recent = $pdo->query('SELECT i.id, i.reference, c.name AS client_name, cat.name AS category_name, u.name AS inspector_name, i.status, i.inspection_date FROM inspections i JOIN clients c ON c.id = i.client_id JOIN certification_categories cat ON cat.id = i.category_id JOIN users u ON u.id = i.inspector_id ORDER BY i.created_at DESC LIMIT 8')->fetchAll();
$activity = $pdo->query('SELECT action, entity_type, COUNT(*) AS total FROM audit_logs GROUP BY action, entity_type ORDER BY total DESC LIMIT 8')->fetchAll();
respond(['counts' => $counts, 'recent_inspections' => $recent, 'activity' => $activity]);
