<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_status.php';

require_method('GET');
rate_limit_or_fail('verify_public', client_ip(), 60, 600);
$rawRef = isset($_GET['ref']) ? normalize_text_input((string) $_GET['ref']) : '';
$normalizedRef = strtoupper($rawRef);
$tokenRef = strtolower($rawRef);
$tokenPattern = '/^[a-f0-9]{64}$/';
$validToken = (bool) preg_match($tokenPattern, $tokenRef);

if ($rawRef === '') {
    record_auth_attempt('verify_public', client_ip(), false, 'invalid_reference');
    $log = db()->prepare('INSERT INTO verification_logs (certificate_id, searched_reference, result_status, ip_hash, user_agent_hash, verified_at) VALUES (?, ?, ?, ?, ?, ?)');
    $log->execute([null, '[empty]', 'not_found', client_ip_hash(), user_agent_hash(), now_sql()]);
    respond(['status' => 'not_found', 'certificate' => null]);
}

if ($validToken) {
    $stmt = db()->prepare('SELECT cert.*, i.reference AS inspection_reference, i.status AS inspection_status, i.is_legacy, c.name AS client_name, e.asset_code, e.name AS equipment_name, cat.name AS category_name, u.name AS inspector_name FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id JOIN users u ON u.id = i.inspector_id WHERE cert.verification_token = ? LIMIT 1');
    $stmt->execute([$tokenRef]);
} else {
    $stmt = db()->prepare('SELECT cert.*, i.reference AS inspection_reference, i.status AS inspection_status, i.is_legacy, c.name AS client_name, e.asset_code, e.name AS equipment_name, cat.name AS category_name, u.name AS inspector_name FROM certificates cert JOIN inspections i ON i.id = cert.inspection_id JOIN clients c ON c.id = i.client_id JOIN equipment e ON e.id = i.equipment_id JOIN certification_categories cat ON cat.id = i.category_id JOIN users u ON u.id = i.inspector_id WHERE i.is_legacy = 1 AND (cert.certificate_number = ? OR i.reference = ?) LIMIT 1');
    $stmt->execute([$normalizedRef, $normalizedRef]);
}

$row = $stmt->fetch();
$result = 'not_found';
if ($row) {
    $result = certificate_effective_status($row);
}

$log = db()->prepare('INSERT INTO verification_logs (certificate_id, searched_reference, result_status, ip_hash, user_agent_hash, verified_at) VALUES (?, ?, ?, ?, ?, ?)');
$loggedReference = $validToken ? substr($tokenRef, 0, 12) . '...' : substr($rawRef, 0, 120);
$log->execute([$row ? (int) $row['id'] : null, $loggedReference, $result, client_ip_hash(), user_agent_hash(), now_sql()]);
record_auth_attempt('verify_public', client_ip(), $row !== false, $row ? null : 'not_found');

if (!$row) {
    respond(['status' => 'not_found', 'certificate' => null]);
}

if ($result === 'expired' && (string) $row['status'] === 'valid') {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $updateCert = $pdo->prepare('UPDATE certificates SET status = ?, updated_at = ? WHERE id = ? AND status = ?');
        $updateCert->execute(['expired', now_sql(), (int) $row['id'], 'valid']);
        $updateInspection = $pdo->prepare('UPDATE inspections SET status = ?, updated_at = ? WHERE id = ? AND status = ?');
        $updateInspection->execute(['expired', now_sql(), (int) $row['inspection_id'], 'issued']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

$verificationUrl = app_url('/verify/' . $row['verification_token']);
$pdfUrl = in_array($result, ['valid', 'expired'], true) ? api_url('certificates/download.php?token=' . $row['verification_token']) : null;
$barcodeUrl = !empty($row['barcode_path']) ? api_url('certificates/qrcode.php?token=' . $row['verification_token']) : null;
respond(['status' => $result, 'certificate' => [
    'certificate_number' => $row['certificate_number'],
    'verification_token' => $row['verification_token'],
    'verification_url' => $verificationUrl,
    'revision' => (int) $row['revision'],
    'client' => $row['client_name'],
    'equipment' => $row['asset_code'] . ' - ' . $row['equipment_name'],
    'inspection_type' => $row['category_name'],
    'inspection_reference' => $row['inspection_reference'],
    'issue_date' => $row['issued_at'],
    'expiry_date' => $row['expires_at'],
    'inspector' => $row['inspector_name'],
    'pdf_url' => $pdfUrl,
    'barcode_url' => $barcodeUrl,
    'brand' => 'JUVA Oil Services Nigeria Limited',
    'is_legacy' => (int) $row['is_legacy'] === 1,
]]);