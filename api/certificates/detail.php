<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_status.php';
require_method('GET');
$user=require_auth();
if(!has_permission($user,'certificates.view')&&!has_permission($user,'certificates.own.view')){api_error('You do not have permission to view certificates.',403);}
$id=(int)($_GET['id']??0);if($id<1){api_error('Certificate id is required.',422,['id'=>'Required.']);}
[$scopeSql,$scopeParams]=inspection_scope_clause($user,'i');
$stmt=db()->prepare('SELECT cert.*,i.reference AS inspection_reference,i.status AS inspection_status,i.renewal_source_certificate_id,i.cloned_from_inspection_id,c.id AS client_id,c.name AS client_name,e.id AS equipment_id,e.asset_code,e.serial_number,e.name AS equipment_name,cat.id AS category_id,cat.name AS category_name,u.name AS inspector_name FROM certificates cert JOIN inspections i ON i.id=cert.inspection_id JOIN clients c ON c.id=i.client_id JOIN equipment e ON e.id=i.equipment_id JOIN certification_categories cat ON cat.id=i.category_id JOIN users u ON u.id=i.inspector_id WHERE cert.id=?'.$scopeSql.' LIMIT 1');
$stmt->execute(array_merge([$id],$scopeParams));$certificate=$stmt->fetch();if(!$certificate){api_error('Certificate not found.',404);}
$certificate['status']=certificate_effective_status($certificate);$certificate['pdf_url']=api_url('certificates/download.php?id='.$id);$certificate['qr_url']=api_url('certificates/qrcode.php?id='.$id);$certificate['verification_url']=app_url('/verify/'.$certificate['verification_token']);
$rev=db()->prepare('SELECT cr.revision,cr.pdf_hash,cr.created_at,u.name AS created_by_name FROM certificate_revisions cr LEFT JOIN users u ON u.id=cr.created_by WHERE cr.certificate_id=? ORDER BY cr.revision DESC');$rev->execute([$id]);
$renew=db()->prepare('SELECT i.id,i.reference,i.status,cert.id AS certificate_id,cert.certificate_number FROM inspections i LEFT JOIN certificates cert ON cert.inspection_id=i.id WHERE i.renewal_source_certificate_id=? ORDER BY i.created_at DESC');$renew->execute([$id]);
respond(['certificate'=>$certificate,'revisions'=>$rev->fetchAll() ?: [],'renewals'=>$renew->fetchAll() ?: []]);
