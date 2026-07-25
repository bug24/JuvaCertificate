<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$user = require_auth();
if (!has_permission($user, '*') && !has_permission($user, 'reports.view') && $user['role']['slug'] !== 'operations-admin') {
    api_error('You do not have permission to view reports.', 403);
}

$type = isset($_GET['type']) ? (string) $_GET['type'] : 'certificates_issued';
$queries = [
    'certificates_issued' => "SELECT cert.certificate_number, cert.revision, cert.status, cert.is_legacy, cert.legacy_mapping_status, cert.issued_at, cert.expires_at, c.name AS client, e.asset_code, e.name AS equipment, cat.name AS inspection_type FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id ORDER BY cert.issued_at DESC LIMIT 500",
    'expiring_certificates' => "SELECT cert.certificate_number, cert.expires_at, cert.is_legacy, c.name AS client, e.asset_code, e.name AS equipment, cat.name AS inspection_type FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id WHERE cert.status = 'valid' AND cert.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY cert.expires_at LIMIT 500",
    'expired_certificates' => "SELECT cert.certificate_number, cert.expires_at, cert.status, cert.is_legacy, c.name AS client, e.asset_code, e.name AS equipment FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id WHERE cert.status = 'expired' OR (cert.status = 'valid' AND cert.expires_at < CURDATE()) ORDER BY cert.expires_at DESC LIMIT 500",
    'revoked_certificates' => "SELECT cert.certificate_number, cert.revoked_at, cert.revocation_reason, cert.is_legacy, c.name AS client, e.asset_code, e.name AS equipment FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id WHERE cert.status = 'revoked' ORDER BY cert.revoked_at DESC LIMIT 500",
    'inspection_history' => "SELECT i.reference, i.status, i.result, i.is_legacy, i.legacy_mapping_status, i.inspection_date, i.next_due_date, c.name AS client, e.asset_code, e.name AS equipment, cat.name AS inspection_type, u.name AS inspector FROM inspections i JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id JOIN users u ON u.id = i.inspector_id ORDER BY i.inspection_date DESC LIMIT 500",
    'client_compliance' => "SELECT c.name AS client, COUNT(DISTINCT e.id) AS equipment_count, COUNT(cert.id) AS certificates, SUM(CASE WHEN cert.status = 'valid' AND (cert.expires_at IS NULL OR cert.expires_at >= CURDATE()) THEN 1 ELSE 0 END) AS valid_certificates, SUM(CASE WHEN cert.status = 'revoked' THEN 1 ELSE 0 END) AS revoked_certificates, SUM(CASE WHEN cert.expires_at < CURDATE() THEN 1 ELSE 0 END) AS expired_certificates, SUM(CASE WHEN cert.is_legacy = 1 THEN 1 ELSE 0 END) AS legacy_certificates FROM clients c LEFT JOIN equipment e ON e.client_id = c.id LEFT JOIN inspections i ON i.client_id = c.id LEFT JOIN certificates cert ON cert.inspection_id = i.id GROUP BY c.id, c.name ORDER BY c.name LIMIT 500",
    'equipment_history' => "SELECT e.asset_code, e.name AS equipment, c.name AS client, e.legacy_id, COUNT(i.id) AS inspections, MAX(i.inspection_date) AS last_inspection, MAX(i.next_due_date) AS next_due, MAX(i.status) AS latest_status FROM equipment e JOIN clients c ON c.id = e.client_id LEFT JOIN inspections i ON i.equipment_id = e.id GROUP BY e.id, e.asset_code, e.name, c.name, e.legacy_id ORDER BY e.asset_code LIMIT 500",
    'inspector_activity' => "SELECT u.name AS inspector, u.legacy_reg_id, COUNT(i.id) AS inspections, SUM(CASE WHEN i.status = 'approved' THEN 1 ELSE 0 END) AS approved, SUM(CASE WHEN i.status = 'issued' THEN 1 ELSE 0 END) AS issued, MAX(i.inspection_date) AS last_activity FROM users u LEFT JOIN inspections i ON i.inspector_id = u.id GROUP BY u.id, u.name, u.legacy_reg_id ORDER BY inspections DESC LIMIT 500",
    'verification_logs' => "SELECT searched_reference, result_status, ip_hash, verified_at FROM verification_logs ORDER BY verified_at DESC LIMIT 500",
    'legacy_migration' => "SELECT source_name, clients_imported, equipment_imported, users_imported, inspections_imported, certificates_imported, details_imported, audit_imported, created_at, notes FROM legacy_migration_runs ORDER BY created_at DESC LIMIT 50",
    'legacy_detail_archive' => "SELECT i.reference, cert.certificate_number, d.legacy_table, d.legacy_row_id, d.mapping_status, d.created_at FROM legacy_inspection_details d JOIN inspections i ON i.id = d.inspection_id LEFT JOIN certificates cert ON cert.inspection_id = i.id ORDER BY d.created_at DESC LIMIT 500",
];

if (!isset($queries[$type])) {
    api_error('Unknown report type.', 422, ['type' => 'Unknown report type.']);
}

$stmt = db()->query($queries[$type]);
respond(['type' => $type, 'rows' => $stmt->fetchAll()]);