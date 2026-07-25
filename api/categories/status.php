<?php

require_once __DIR__ . '/../_bootstrap.php';

require_once __DIR__ . '/../lib/category_status.php';

require_methods(['GET','POST']);
$id = $_SERVER['REQUEST_METHOD']==='GET' ? (int)($_GET['id']??0) : (int)(json_input()['id']??0);
if ($id<1) api_error('Category id is required.',422,['id'=>'Required.']);
$stmt=db()->prepare('SELECT id,name,status FROM certification_categories WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$category=$stmt->fetch();
if(!$category) api_error('Category not found.',404);

if($_SERVER['REQUEST_METHOD']==='GET'){
    require_permission('categories.view');
    respond(['category'=>$category,'dependencies'=>category_dependency_summary($id)]);
}

$input=json_input();
$next=strtolower(trim((string)($input['status']??'')));
$reason=normalize_text_input((string)($input['reason']??''),true);
if(!in_array($next,['active','inactive','legacy'],true)) api_error('Invalid category status.',422,['status'=>'Choose Active, Inactive or Legacy.']);
$previous=(string)$category['status'];
if($next===$previous) respond(['category'=>$category,'dependencies'=>category_dependency_summary($id),'unchanged'=>true]);
$user=require_auth();
require_csrf();
$slug=(string)($user['role']['slug']??'');
$legacyTransition=$next==='legacy'||$previous==='legacy';
if($legacyTransition){
    if($slug!=='super-admin') api_error('Only a Super Administrator may mark or restore a legacy category.',403);
    require_permission($next==='legacy'?'categories.mark_legacy':'categories.restore_legacy');
}else{
    require_permission('categories.change_status');
}
$dependencies=category_dependency_summary($id);
if(($next==='legacy'||$previous==='legacy'||($next==='inactive'&&($dependencies['inspection_count']>0||$dependencies['certificate_count']>0)))&&$reason===''){
    api_error('A reason is required for this status change.',422,['reason'=>'Explain why this category status is changing.']);
}
if(text_length($reason)>1000||contains_markup($reason)) api_error('Use a plain-text reason up to 1000 characters.',422,['reason'=>'Use plain text only.']);
$stmt=db()->prepare('UPDATE certification_categories SET status=?,updated_at=? WHERE id=?');
$stmt->execute([$next,now_sql(),$id]);
audit_log((int)$user['id'],'categories.status_changed','category',$id,[
    'category_name'=>(string)$category['name'],'previous_status'=>$previous,'new_status'=>$next,
    'reason'=>$reason,'dependencies'=>$dependencies,
]);
$stmt=db()->prepare('SELECT * FROM certification_categories WHERE id=?');
$stmt->execute([$id]);
respond(['category'=>$stmt->fetch(),'dependencies'=>$dependencies]);
