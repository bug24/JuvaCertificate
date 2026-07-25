<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_service.php';
require_method('POST');
$user=require_permission('certificates.issue');require_csrf();$input=json_input();$id=(int)($input['id']??0);$reason=normalize_text_input((string)($input['reason']??''),true);
if($id<1||$reason===''){api_error('Certificate and revision reason are required.',422,['id'=>$id<1?'Required.':null,'reason'=>$reason===''?'Explain why a new revision is required.':null]);}
$stmt=db()->prepare('SELECT inspection_id FROM certificates WHERE id=? LIMIT 1');$stmt->execute([$id]);$certificate=$stmt->fetch();if(!$certificate){api_error('Certificate not found.',404);}
$result=issue_certificate($user,(int)$certificate['inspection_id'],['create_revision'=>true],['allow_self_issue'=>privileged_role((string)$user['role']['slug'])]);
audit_log((int)$user['id'],'certificates.revision_created','certificate',(int)$result['certificate_id'],['reason'=>$reason,'revision'=>(int)$result['revision']]);
respond($result,201);
