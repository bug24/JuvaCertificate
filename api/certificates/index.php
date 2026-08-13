<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_status.php';
require_once __DIR__ . '/../lib/certificate_archive.php';

require_method('GET');
$user = require_auth();
if (!has_permission($user, 'certificates.view') && !has_permission($user, 'certificates.own.view')) {
    api_error('You do not have permission to view certificates.', 403);
}
[$filters, $filterErrors] = certificate_archive_filters($_GET);
if ($filterErrors) api_error('Invalid certificate filters.', 422, $filterErrors);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(100, max(10, (int) ($_GET['per_page'] ?? 25)));
$offset = ($page - 1) * $perPage;
[$whereSql, $params] = certificate_archive_where($filters, $user);
$from = ' FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id JOIN clients c ON c.id=i.client_id JOIN equipment e ON e.id=i.equipment_id JOIN certification_categories cat ON cat.id=i.category_id JOIN users u ON u.id=i.inspector_id';
$countStmt = db()->prepare('SELECT COUNT(*)' . $from . $whereSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$sql = 'SELECT cert.*, i.reference AS inspection_reference, i.inspection_date, i.renewal_source_certificate_id, c.name AS client_name, e.asset_code, e.serial_number, e.name AS equipment_name, cat.name AS category_name, u.name AS inspector_name' . $from . $whereSql . ' ORDER BY cert.issued_at DESC, cert.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];
foreach ($rows as &$row) {
    $row['status'] = certificate_effective_status($row);
    $row['pdf_path'] = api_url('certificates/download.php?id=' . (int) $row['id']);
    $row['barcode_path'] = !empty($row['barcode_path']) ? api_url('certificates/qrcode.php?id=' . (int) $row['id']) : null;
    $row['verification_url'] = app_url('/verify/' . (string) $row['verification_token']);
}
unset($row);

[$scopeSql, $scopeParams] = certificate_archive_scope($user);
$scopeWhere = $scopeSql !== '' ? ' WHERE ' . $scopeSql : '';
$clientStmt = db()->prepare('SELECT DISTINCT c.id, c.name FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id JOIN clients c ON c.id=i.client_id' . $scopeWhere . ' ORDER BY c.name');
$clientStmt->execute($scopeParams);
$categoryStmt = db()->prepare('SELECT DISTINCT cat.id, cat.name FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id JOIN certification_categories cat ON cat.id=i.category_id' . $scopeWhere . ' ORDER BY cat.name');
$categoryStmt->execute($scopeParams);

respond([
    'certificates' => $rows,
    'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))],
    'filters' => $filters,
    'filter_options' => ['clients' => $clientStmt->fetchAll() ?: [], 'categories' => $categoryStmt->fetchAll() ?: []],
]);