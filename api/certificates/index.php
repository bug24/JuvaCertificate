<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_status.php';

require_method('GET');
$user = require_auth();
if (!has_permission($user, 'certificates.view') && !has_permission($user, 'certificates.own.view')) {
    api_error('You do not have permission to view certificates.', 403);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(100, max(10, (int) ($_GET['per_page'] ?? 25)));
$offset = ($page - 1) * $perPage;
$where = [];
$params = [];
if (has_permission($user, 'certificates.own.view') && !has_permission($user, 'certificates.view') && !empty($user['client_id'])) {
    $where[] = 'i.client_id = ?'; $params[] = (int) $user['client_id'];
}
$q = normalize_text_input((string) ($_GET['q'] ?? ''));
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(cert.certificate_number LIKE ? OR i.reference LIKE ? OR c.name LIKE ? OR e.name LIKE ? OR e.asset_code LIKE ? OR e.serial_number LIKE ? OR cat.name LIKE ? OR u.name LIKE ?)';
    for ($n=0;$n<8;$n++) { $params[] = $like; }
}
foreach (['client_id'=>'i.client_id','category_id'=>'i.category_id'] as $key=>$column) {
    if (!empty($_GET[$key])) { $where[] = $column . ' = ?'; $params[] = (int) $_GET[$key]; }
}
$status = strtolower(normalize_text_input((string) ($_GET['status'] ?? '')));
if (in_array($status, ['valid','expired','revoked'], true)) {
    if ($status === 'expired') { $where[] = "(cert.status='expired' OR (cert.status='valid' AND cert.expires_at < CURDATE()))"; }
    elseif ($status === 'valid') { $where[] = "cert.status='valid' AND (cert.expires_at IS NULL OR cert.expires_at >= CURDATE())"; }
    else { $where[] = 'cert.status = ?'; $params[] = $status; }
}
if (isset($_GET['legacy']) && in_array((string) $_GET['legacy'], ['0','1'], true)) { $where[]='cert.is_legacy=?'; $params[]=(int) $_GET['legacy']; }
foreach (['issued_from'=>['cert.issued_at','>='],'issued_to'=>['cert.issued_at','<='],'expiry_from'=>['cert.expires_at','>='],'expiry_to'=>['cert.expires_at','<=']] as $key=>$rule) {
    if (!empty($_GET[$key]) && valid_iso_date((string) $_GET[$key])) { $where[]=$rule[0].' '.$rule[1].' ?'; $params[]=(string) $_GET[$key]; }
}
if (!empty($_GET['revision'])) { $where[]='cert.revision=?'; $params[]=(int) $_GET['revision']; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$from = ' FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id JOIN clients c ON c.id=i.client_id JOIN equipment e ON e.id=i.equipment_id JOIN certification_categories cat ON cat.id=i.category_id JOIN users u ON u.id=i.inspector_id';
$countStmt=db()->prepare('SELECT COUNT(*)'.$from.$whereSql);$countStmt->execute($params);$total=(int)$countStmt->fetchColumn();
$sql='SELECT cert.*,i.reference AS inspection_reference,i.renewal_source_certificate_id,c.name AS client_name,e.asset_code,e.serial_number,e.name AS equipment_name,cat.name AS category_name,u.name AS inspector_name'.$from.$whereSql.' ORDER BY cert.issued_at DESC,cert.id DESC LIMIT '.$perPage.' OFFSET '.$offset;
$stmt=db()->prepare($sql);$stmt->execute($params);$rows=$stmt->fetchAll() ?: [];
foreach($rows as &$row){$row['status']=certificate_effective_status($row);$row['pdf_path']=api_url('certificates/download.php?id='.(int)$row['id']);$row['barcode_path']=!empty($row['barcode_path'])?api_url('certificates/qrcode.php?id='.(int)$row['id']):null;$row['verification_url']=app_url('/verify/'.(string)$row['verification_token']);}
unset($row);
respond(['certificates'=>$rows,'pagination'=>['page'=>$page,'per_page'=>$perPage,'total'=>$total,'pages'=>(int)ceil($total/$perPage)],'filters'=>['q'=>$q,'status'=>$status]]);
