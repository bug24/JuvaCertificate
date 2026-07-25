<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$user = require_permission('categories.manage');
require_csrf();
$input = json_body();
$categoryId = isset($input['category_id']) ? (int) $input['category_id'] : 0;
if ($categoryId < 1) api_error('Category id is required.', 422, ['category_id' => 'Required.']);
$stmt = db()->prepare('SELECT * FROM form_templates WHERE category_id=? ORDER BY version DESC LIMIT 1');
$stmt->execute([$categoryId]);
$template = $stmt->fetch();
if (!$template) api_error('Active form template not found.', 404);

$booleanKeys = ['show_inspector_signature','show_authenticator_signature','show_company_stamp','requires_evidence','requires_inspection_photo','requires_inspector_signature','requires_authenticator_signature','requires_company_stamp'];
$values = [];
foreach ($booleanKeys as $key) $values[$key] = !empty($input[$key]) ? 1 : 0;
$minimum = max(0, min(20, (int) ($input['minimum_evidence_files'] ?? 0)));
$allowed = normalize_text_input((string) ($input['allowed_evidence_types'] ?? 'image/jpeg,image/png,image/webp,application/pdf'));
if ($allowed === '' || text_length($allowed) > 255 || !preg_match('/^[a-z0-9+.,\-\/]+$/i', $allowed)) {
    api_error('Please correct the evidence file types.', 422, ['allowed_evidence_types' => 'Use comma-separated MIME types only.']);
}
$update = db()->prepare('UPDATE form_templates SET show_inspector_signature=?,show_authenticator_signature=?,show_company_stamp=?,requires_evidence=?,minimum_evidence_files=?,allowed_evidence_types=?,requires_inspection_photo=?,requires_inspector_signature=?,requires_authenticator_signature=?,requires_company_stamp=? WHERE id=?');
$update->execute([$values['show_inspector_signature'],$values['show_authenticator_signature'],$values['show_company_stamp'],$values['requires_evidence'],$minimum,$allowed,$values['requires_inspection_photo'],$values['requires_inspector_signature'],$values['requires_authenticator_signature'],$values['requires_company_stamp'],(int)$template['id']]);
audit_log((int)$user['id'],'form_templates.authentication_settings_updated','form_template',(int)$template['id'],['category_id'=>$categoryId]);
$stmt->execute([$categoryId]);
respond(['template' => $stmt->fetch(), 'message' => 'Certificate authentication and evidence rules saved.']);
