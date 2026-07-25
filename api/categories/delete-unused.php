<?php

require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/category_status.php';

require_method('POST');
$user=require_permission('categories.delete_unused');
require_csrf();
if((string)($user['role']['slug']??'')!=='super-admin') api_error('Only a Super Administrator may delete an unused category.',403);
$input=json_input();
$id=(int)($input['id']??0);
if($id<1) api_error('Category id is required.',422,['id'=>'Required.']);
$dependencies=category_dependency_summary($id);
if($dependencies['inspection_count']>0||$dependencies['certificate_count']>0||$dependencies['used_template_count']>0){
    api_error('This category has historical dependencies and cannot be deleted.',422,['category'=>'Deactivate it or mark it as Legacy instead.']);
}
$pdo=db();$pdo->beginTransaction();
try{
    $stmt=$pdo->prepare('SELECT name,status FROM certification_categories WHERE id=? FOR UPDATE');$stmt->execute([$id]);$category=$stmt->fetch();
    if(!$category) api_error('Category not found.',404);
    $pdo->prepare('DELETE c FROM form_repeatable_columns c JOIN form_repeatable_sections s ON s.id=c.section_id JOIN form_templates t ON t.id=s.template_id WHERE t.category_id=?')->execute([$id]);
    $pdo->prepare('DELETE s FROM form_repeatable_sections s JOIN form_templates t ON t.id=s.template_id WHERE t.category_id=?')->execute([$id]);
    $pdo->prepare('DELETE f FROM form_fields f JOIN form_templates t ON t.id=f.template_id WHERE t.category_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM form_templates WHERE category_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM certification_categories WHERE id=?')->execute([$id]);
    audit_log((int)$user['id'],'categories.deleted_unused','category',$id,['category_name'=>(string)$category['name']]);
    $pdo->commit();
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
respond(['deleted'=>true,'id'=>$id]);
