<?php
require_once __DIR__ . '/../_bootstrap.php';
require_methods(['GET','POST']);
$user = require_permission('users.manage');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $row = db()->query('SELECT * FROM certificate_branding_settings WHERE id=1')->fetch() ?: [];
    unset($row['company_stamp_path']);
    $row['company_stamp_url'] = !empty($row['company_stamp_original_name']) ? api_url('settings/stamp-download.php') : null;
    respond(['branding' => $row]);
}
require_csrf();
$action = isset($_POST['action']) ? (string) $_POST['action'] : 'upload_stamp';
if (in_array($action, ['remove_stamp','disable_stamp','enable_stamp'], true)) {
    $row = db()->query('SELECT company_stamp_path FROM certificate_branding_settings WHERE id=1')->fetch() ?: [];
    if ($action === 'remove_stamp' && !empty($row['company_stamp_path'])) { $used=db()->prepare('SELECT COUNT(*) FROM certificate_revisions WHERE company_stamp_path_snapshot=?'); $used->execute([(string)$row['company_stamp_path']]); if((int)$used->fetchColumn()===0){ $path=resolve_storage_path((string)$row['company_stamp_path']); if($path)@unlink($path); } }
    if ($action === 'remove_stamp') db()->prepare('UPDATE certificate_branding_settings SET company_stamp_path=NULL,company_stamp_original_name=NULL,company_stamp_mime_type=NULL,company_stamp_uploaded_at=NULL,company_stamp_uploaded_by=NULL,company_stamp_is_active=0 WHERE id=1')->execute();
    else db()->prepare('UPDATE certificate_branding_settings SET company_stamp_is_active=? WHERE id=1')->execute([$action === 'enable_stamp' ? 1 : 0]);
    audit_log((int)$user['id'],'settings.' . $action,'branding',1);
    respond(['message' => 'Branding settings updated.']);
}
if (empty($_FILES['file']) || (int)$_FILES['file']['error'] !== UPLOAD_ERR_OK) api_error('Select a company stamp image.',422);
$file=$_FILES['file']; if((int)$file['size']>4*1024*1024)api_error('Company stamp must not exceed 4MB.',422);
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$map=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];if(!isset($map[$mime]))api_error('Use a valid PNG, JPG or WEBP company stamp.',422);if(!validate_image_dimensions($file['tmp_name']))api_error('Image dimensions are unsafe or too large.',422);
$dir=ensure_private_storage_dir('branding');$name=gmdate('YmdHis').'-'.random_token(4).'.'.$map[$mime];$path=$dir.DIRECTORY_SEPARATOR.$name;if(!move_uploaded_file($file['tmp_name'],$path))api_error('Unable to store company stamp.',500);
$key=storage_key('branding',$name);$original=normalize_text_input((string)$file['name']);db()->prepare('UPDATE certificate_branding_settings SET company_stamp_path=?,company_stamp_original_name=?,company_stamp_mime_type=?,company_stamp_uploaded_at=?,company_stamp_uploaded_by=?,company_stamp_is_active=1 WHERE id=1')->execute([$key,$original,$mime,now_sql(),(int)$user['id']]);audit_log((int)$user['id'],'settings.company_stamp_uploaded','branding',1,['mime_type'=>$mime]);respond(['message'=>'Company stamp uploaded.','stamp_url'=>api_url('settings/stamp-download.php')]);
