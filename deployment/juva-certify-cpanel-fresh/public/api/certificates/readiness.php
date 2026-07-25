<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_service.php';

require_method('GET');
$user = require_any_permission(['certificates.view', 'certificates.issue', 'inspections.view', 'inspections.edit']);
$inspectionId = isset($_GET['inspection_id']) ? (int) $_GET['inspection_id'] : 0;
if ($inspectionId < 1) {
    api_error('Inspection id is required.', 422, ['inspection_id' => 'Required.']);
}
[$scopeSql, $scopeParams] = inspection_scope_clause($user, 'i');
$inspectionStmt = db()->prepare('SELECT i.*, c.name AS client_name, c.address AS client_address, e.asset_code, e.name AS equipment_name, cat.name AS category_name, cat.template_family, cat.validity_months, ft.requires_evidence, ft.minimum_evidence_files, ft.requires_inspection_photo, ft.requires_inspector_signature, ft.requires_authenticator_signature, ft.requires_company_stamp, ft.show_inspector_signature, ft.show_authenticator_signature, ft.show_company_stamp, u.name AS inspector_name, u.qualification AS inspector_qualification FROM inspections i JOIN clients c ON c.id=i.client_id JOIN equipment e ON e.id=i.equipment_id JOIN certification_categories cat ON cat.id=i.category_id JOIN form_templates ft ON ft.id=i.form_template_id JOIN users u ON u.id=i.inspector_id WHERE i.id=?' . $scopeSql . ' LIMIT 1');
$inspectionStmt->execute(array_merge([$inspectionId], $scopeParams));
$inspection = $inspectionStmt->fetch();
if (!$inspection) {
    api_error('Inspection not found.', 404);
}
if (!in_array((string) $inspection['template_family'], ['ccu_visual', 'chain_block', 'endless_round_webbing_sling', 'lever_hoist', 'flat_webbing_sling', 'eye_bolt', 'hook', 'mpi_spreader_bar', 'general_lifting_accessory', 'general_thorough_examination'], true)) {
    respond(['ready' => true, 'missing_fields' => [], 'missing_sections' => [], 'validation_errors' => [], 'warnings' => ['Readiness details currently apply to CCU Visual Inspection.']]);
}
$fieldStmt = db()->prepare('SELECT f.field_key, f.label, f.field_type, f.is_required, f.pdf_section, iv.value_text FROM form_fields f LEFT JOIN inspection_values iv ON iv.field_id=f.id AND iv.inspection_id=? WHERE f.template_id=? ORDER BY f.sort_order,f.id');
$fieldStmt->execute([$inspectionId, (int) $inspection['form_template_id']]);
$itemStmt = db()->prepare('SELECT section_key,row_index,column_key,value_text FROM inspection_items WHERE inspection_id=? ORDER BY section_key,row_index,id');
$itemStmt->execute([$inspectionId]);
$certStmt = db()->prepare('SELECT certificate_number FROM certificates WHERE inspection_id=? LIMIT 1');
$certStmt->execute([$inspectionId]);
$certificate = $certStmt->fetch();
$number = $certificate ? (string) $certificate['certificate_number'] : (string) $inspection['reference'];
$issuedAt = gmdate('Y-m-d');
$expiry = !empty($inspection['next_due_date']) ? (string) $inspection['next_due_date'] : gmdate('Y-m-d', strtotime('+' . max(1, (int) $inspection['validity_months']) . ' months'));
$fieldRows = $fieldStmt->fetchAll() ?: [];
$itemRows = $itemStmt->fetchAll() ?: [];
$attachmentStmt = db()->prepare('SELECT id,attachment_type,file_name,file_path,mime_type,file_size FROM inspection_attachments WHERE inspection_id=? ORDER BY id');
$attachmentStmt->execute([$inspectionId]);
$attachments = $attachmentStmt->fetchAll() ?: [];
$authentication = certificate_authentication_assets($inspection, $fieldRows, $attachments);
if (in_array((string) $inspection['template_family'], ['eye_bolt', 'hook', 'general_lifting_accessory'], true)) {
    $family = (string) $inspection['template_family'];
    if ($family === 'hook') $result = hook_readiness($inspection,$fieldRows,$itemRows,$number,$issuedAt,$expiry);
    elseif ($family === 'general_lifting_accessory') $result = general_lifting_accessory_readiness($inspection,$fieldRows,$itemRows,$number,$issuedAt,$expiry);
    else $result = eye_bolt_readiness($inspection,$fieldRows,$itemRows,$number,$issuedAt,$expiry);
    $result['can_save_draft']=true; $result['can_preview']=(bool)$result['ready']; $result['can_submit']=(bool)$result['ready']; $result['can_approve']=(bool)$result['ready']; $result['can_issue']=(bool)$result['ready'];
    $result['optional_warnings']=[]; respond($result);
}if ((string) $inspection['template_family'] === 'mpi_spreader_bar') {
    $requiresEvidence=(int)($inspection['requires_evidence']??0)===1; $minimumEvidence=(int)($inspection['minimum_evidence_files']??0);
    $preview=mpi_spreader_bar_readiness($inspection,$fieldRows,$number,$issuedAt,$expiry,$attachments,false,0);
    $result=mpi_spreader_bar_readiness($inspection,$fieldRows,$number,$issuedAt,$expiry,$attachments,$requiresEvidence,$minimumEvidence);
    $result['can_save_draft']=true; $result['can_preview']=(bool)$preview['ready']; $result['can_submit']=(bool)$result['ready']; $result['can_approve']=(bool)$result['ready']; $result['can_issue']=(bool)$result['ready'];
    $result['optional_warnings']=[]; respond($result);
}
if (in_array((string) $inspection['template_family'], ['chain_block', 'endless_round_webbing_sling', 'lever_hoist', 'flat_webbing_sling', 'general_thorough_examination'], true)) {

    $requiresEvidence = (int) ($inspection['requires_evidence'] ?? 0) === 1;
    $minimumEvidence = (int) ($inspection['minimum_evidence_files'] ?? 0);
    $family = (string) $inspection['template_family'];
    $readinessFunctions = ['general_thorough_examination'=>'general_thorough_examination_readiness','chain_block'=>'chain_block_readiness','flat_webbing_sling'=>'flat_webbing_sling_readiness','lever_hoist'=>'lever_hoist_readiness','endless_round_webbing_sling'=>'endless_round_webbing_sling_readiness'];
    $readinessFunction = $readinessFunctions[$family];
    $preview = $readinessFunction($inspection, $fieldRows, $number, $issuedAt, $expiry, $attachments, false, 0);
    $issue = $readinessFunction($inspection, $fieldRows, $number, $issuedAt, $expiry, $attachments, $requiresEvidence, $minimumEvidence);
    $issue['can_save_draft'] = true;
    $issue['can_preview'] = (bool) $preview['ready'];
    $issue['can_submit'] = (bool) $issue['ready'];
    $issue['can_approve'] = (bool) $issue['ready'];
    $issue['can_issue'] = (bool) $issue['ready'];
    $evidenceCount = 0; $signatureCount = 0;
    foreach ($attachments as $attachment) {
        if ((string) ($attachment['attachment_type'] ?? 'evidence') === 'signature') { $signatureCount++; } else { $evidenceCount++; }
    }
    $warnings = [];
    if ($evidenceCount === 0 && !$requiresEvidence) { $warnings[] = 'No inspection evidence attached'; }
    if ($signatureCount === 0) { $warnings[] = 'No inspection-specific signature uploaded; profile signature or an empty signature area will be used'; }
    if ((int) ($inspection['requires_inspector_signature'] ?? 0) === 1 && empty($authentication['inspector_signature_path'])) { $issue['validation_errors']['inspector_signature'] = 'Upload an inspection signature or activate the assigned inspector profile signature.'; }
    if ((int) ($inspection['requires_authenticator_signature'] ?? 0) === 1 && empty($authentication['authenticator_signature_path'])) { $issue['validation_errors']['authenticator_signature'] = 'An active authenticator or approver profile signature is required.'; }
    if ((int) ($inspection['requires_company_stamp'] ?? 0) === 1 && empty($authentication['company_stamp_path'])) { $issue['validation_errors']['company_stamp'] = 'Upload and activate the JUVA company stamp.'; }
    $issue['ready'] = !$issue['missing_fields'] && !$issue['validation_errors'];
    $issue['can_submit'] = (bool) $issue['ready']; $issue['can_approve'] = (bool) $issue['ready']; $issue['can_issue'] = (bool) $issue['ready'];
    if (!isset($issue['blocking_items'])) { $issue['blocking_items'] = $issue['missing_fields']; }
    $issue['optional_warnings'] = $warnings;
    $issue['warnings'] = $warnings;
    $issue['failed_pending_uploads'] = [];
    $issue['preview_validation_errors'] = $preview['validation_errors'];
    respond($issue);}
$readiness = ccu_certificate_readiness($inspection, $fieldRows, $itemRows, $number, $issuedAt, $expiry);
$evidenceCount = 0; $signatureCount = 0; foreach ($attachments as $attachment) { if ((string) ($attachment['attachment_type'] ?? 'evidence') === 'signature') $signatureCount++; else $evidenceCount++; }
if ((int) ($inspection['requires_evidence'] ?? 0) === 1 && $evidenceCount < max(1, (int) ($inspection['minimum_evidence_files'] ?? 0))) $readiness['validation_errors']['evidence'] = 'Upload the evidence required by this template.';
if ((int) ($inspection['requires_inspector_signature'] ?? 0) === 1 && empty($authentication['inspector_signature_path'])) $readiness['validation_errors']['inspector_signature'] = 'Inspector signature is required.';
if ((int) ($inspection['requires_authenticator_signature'] ?? 0) === 1 && empty($authentication['authenticator_signature_path'])) $readiness['validation_errors']['authenticator_signature'] = 'Authenticator signature is required.';
if ((int) ($inspection['requires_company_stamp'] ?? 0) === 1 && empty($authentication['company_stamp_path'])) $readiness['validation_errors']['company_stamp'] = 'Active company stamp is required.';
$readiness['ready'] = !$readiness['missing_fields'] && !$readiness['missing_sections'] && !$readiness['validation_errors'];
$readiness['optional_warnings'] = array_values(array_filter([$evidenceCount === 0 && (int)($inspection['requires_evidence'] ?? 0) !== 1 ? 'No inspection evidence attached' : null, $signatureCount === 0 ? 'No inspection-specific signature uploaded; profile signature or an empty signature area will be used' : null]));
$readiness['warnings'] = $readiness['optional_warnings'];
$readiness['can_save_draft'] = true;
$readiness['can_preview'] = (bool) $readiness['ready'];
$readiness['can_submit'] = (bool) $readiness['ready'];
$readiness['can_issue'] = (bool) $readiness['ready'];
respond($readiness);


