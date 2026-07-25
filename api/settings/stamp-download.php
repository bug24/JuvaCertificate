<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('GET');require_permission('users.manage');$row=db()->query('SELECT company_stamp_path,company_stamp_original_name,company_stamp_mime_type FROM certificate_branding_settings WHERE id=1')->fetch();$path=$row?resolve_storage_path((string)$row['company_stamp_path']):null;if(!$path)api_error('Company stamp not found.',404);stream_download($path,(string)($row['company_stamp_original_name']?:'company-stamp.png'),(string)($row['company_stamp_mime_type']?:'image/png'),true);
