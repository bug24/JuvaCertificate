<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_engine.php';

require_method('POST');
$user = require_auth();
consume_rate_limit('certificate_preview', (string) $user['id'], 20, 600);
require_csrf();
$input = json_input();
$id = (int) ($input['inspection_id'] ?? 0);
if ($id < 1) api_error('Inspection id is required.', 422, ['inspection_id' => 'Required.']);
$base = fetch_inspection_for_user($id, $user);
if (!$base) api_error('Inspection not found.', 404);
if (!in_array((string) $base['status'], ['draft', 'correction', 'submitted', 'approved'], true)) {
    api_error('Certificate preview is not available from the current inspection status.', 422, ['workflow' => 'Open a draft, returned, submitted or approved inspection.']);
}

$stmt = db()->prepare('SELECT i.*,c.name client_name,c.address client_address,e.asset_code,e.name equipment_name,e.serial_number equipment_serial_number,e.manufacturer equipment_manufacturer,e.safe_working_load equipment_safe_working_load,e.reference_standard equipment_standard,e.manufacture_date equipment_manufacture_date,e.manufacture_date_value equipment_manufacture_date_value,(SELECT h.inspection_date FROM inspections h WHERE h.equipment_id=e.id AND h.id<>i.id AND h.status IN (\'approved\',\'issued\',\'expired\',\'revoked\') ORDER BY h.inspection_date DESC,h.id DESC LIMIT 1) previous_examination_date,cat.name category_name,cat.validity_months,cat.template_family,cat.layout_key,ft.requires_evidence,ft.minimum_evidence_files,ft.requires_inspector_signature,ft.requires_authenticator_signature,ft.requires_company_stamp,ft.show_inspector_signature,ft.show_authenticator_signature,ft.show_company_stamp,u.name inspector_name,u.qualification inspector_qualification FROM inspections i JOIN clients c ON c.id=i.client_id JOIN equipment e ON e.id=i.equipment_id JOIN certification_categories cat ON cat.id=i.category_id JOIN form_templates ft ON ft.id=i.form_template_id JOIN users u ON u.id=i.inspector_id WHERE i.id=? LIMIT 1');
$stmt->execute([$id]);
$inspection = $stmt->fetch();
if (!$inspection) api_error('Inspection not found.', 404);
$family = (string) $inspection['template_family'];
if (!in_array($family, ['ccu_visual', 'chain_block', 'endless_round_webbing_sling', 'lever_hoist', 'flat_webbing_sling', 'eye_bolt', 'shackles', 'hook', 'mpi_spreader_bar', 'general_lifting_accessory', 'general_thorough_examination'], true)) api_error('Dedicated preview is not available for this category.', 422);

$number = normalize_certificate_number($input['certificate_number'] ?? $inspection['reference']);
$issued = (!empty($input['issued_at']) && valid_iso_date((string) $input['issued_at'])) ? (string) $input['issued_at'] : gmdate('Y-m-d');
$expiry = (string) ($inspection['next_due_date'] ?? '');
if ($expiry === '') $expiry = gmdate('Y-m-d', strtotime('+' . max(1, (int) $inspection['validity_months']) . ' months'));
$stmt = db()->prepare('SELECT f.field_key,f.label,f.field_type,f.is_required,f.pdf_section,iv.value_text FROM form_fields f LEFT JOIN inspection_values iv ON iv.field_id=f.id AND iv.inspection_id=? WHERE f.template_id=? ORDER BY f.sort_order,f.id');
$stmt->execute([$id, (int) $inspection['form_template_id']]);
$rows = $stmt->fetchAll() ?: [];
$stmt = db()->prepare('SELECT section_key,row_index,column_key,value_text FROM inspection_items WHERE inspection_id=? ORDER BY section_key,row_index,id');
$stmt->execute([$id]);
$items = $stmt->fetchAll() ?: [];
$stmt = db()->prepare('SELECT id,attachment_type,file_name,file_path,mime_type,file_size FROM inspection_attachments WHERE inspection_id=? ORDER BY id');
$stmt->execute([$id]);
$attachments = $stmt->fetchAll() ?: [];
$auth = certificate_authentication_assets($inspection, $rows, $attachments);
$rows = certificate_apply_identity_to_field_rows($rows, $auth);
if ((int) ($inspection['show_inspector_signature'] ?? 1) !== 1) $auth['inspector_signature_path'] = null;
if ((int) ($inspection['show_authenticator_signature'] ?? 1) !== 1) $auth['authenticator_signature_path'] = null;
if ((int) ($inspection['show_company_stamp'] ?? 0) !== 1) $auth['company_stamp_path'] = null;

$dir = ensure_private_storage_dir('previews');
$token = random_token(24);
$path = $dir . DIRECTORY_SEPARATOR . $family . '-preview-' . $id . '-' . $token . '.pdf';
$verify = app_url('/verify/preview');
try {
    if ($family === 'chain_block') {
        $ready = chain_block_readiness($inspection, $rows, $number, $issued, $expiry, [], false);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = chain_block_payload($inspection, $rows, $number, 0, $verify, $issued, $expiry);
        $payload['authentication'] = $auth;
        $payload['status'] = 'PREVIEW';
        chain_block_render_certificate_pdf($path, $payload);
    } elseif ($family === 'endless_round_webbing_sling') {
        $ready = endless_round_webbing_sling_readiness($inspection, $rows, $number, $issued, $expiry, $attachments, false, 0);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = endless_round_webbing_sling_payload($inspection, $rows, $number, 0, $verify, $issued, $expiry);
        $payload['authentication'] = $auth;
        $payload['status'] = 'PREVIEW';
        endless_round_webbing_sling_render_certificate_pdf($path, $payload);
    } elseif ($family === 'lever_hoist') {
        $ready = lever_hoist_readiness($inspection, $rows, $number, $issued, $expiry, $attachments, false, 0);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = lever_hoist_payload($inspection, $rows, $number, 0, $verify, $issued, $expiry);
        $payload['authentication'] = $auth;
        $payload['status'] = 'PREVIEW';
        lever_hoist_render_certificate_pdf($path, $payload);
    } elseif ($family === 'flat_webbing_sling') {
        $ready = flat_webbing_sling_readiness($inspection, $rows, $number, $issued, $expiry, $attachments, false, 0);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = flat_webbing_sling_payload($inspection, $rows, $number, 0, $verify, $issued, $expiry);
        $payload['authentication'] = $auth;
        $payload['status'] = 'PREVIEW';
        flat_webbing_sling_render_certificate_pdf($path, $payload);
    } elseif ($family === 'shackles') {
        $ready = shackles_readiness($inspection, $rows, $items, $number, $issued, $expiry);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = shackles_payload($inspection, $rows, $items, $number, 0, $verify, $issued, $expiry);
        $payload['authentication'] = $auth;
        $payload['inspector_name'] = (string) ($auth['inspector_name_snapshot'] ?? $payload['inspector_name'] ?? '');
        $payload['inspector_qualification'] = (string) ($auth['inspector_qualifications_snapshot'] ?? $payload['inspector_qualification'] ?? '');
        $payload['authenticator_name'] = (string) ($auth['authenticator_name_snapshot'] ?? $payload['authenticator_name'] ?? '');
        $payload['authenticator_qualification'] = (string) ($auth['authenticator_qualifications_snapshot'] ?? $payload['authenticator_qualification'] ?? '');
        $payload['status'] = 'PREVIEW';
        shackles_render_certificate_pdf($path, $payload);
    } elseif ($family === 'eye_bolt') {
        $ready = eye_bolt_readiness($inspection, $rows, $items, $number, $issued, $expiry);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = eye_bolt_payload($inspection, $rows, $items, $number, 0, $verify, $issued, $expiry);
        $payload['authentication'] = $auth;
        $payload['status'] = 'PREVIEW';
        eye_bolt_render_certificate_pdf($path, $payload);
    } elseif ($family === 'general_lifting_accessory') {
        $ready=general_lifting_accessory_readiness($inspection,$rows,$items,$number,$issued,$expiry);
        if(!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.',422,$ready);
        $payload=general_lifting_accessory_payload($inspection,$rows,$items,$number,0,$verify,$issued,$expiry);
        $payload['authentication']=$auth;$payload['status']='PREVIEW';
        general_lifting_accessory_render_certificate_pdf($path,$payload);
    } elseif ($family === 'general_thorough_examination') {
        $ready=general_thorough_examination_readiness($inspection,$rows,$number,$issued,$expiry,$attachments,false,0);
        if(!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.',422,$ready);
        $payload=general_thorough_examination_payload($inspection,$rows,$number,0,$verify,$issued,$expiry);
        $payload['authentication']=$auth;$payload['status']='PREVIEW';
        general_thorough_examination_render_certificate_pdf($path,$payload);    } elseif ($family === 'mpi_spreader_bar') {
        $ready = mpi_spreader_bar_readiness($inspection, $rows, $number, $issued, $expiry, $attachments, false, 0);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = mpi_spreader_bar_payload($inspection, $rows, $number, 0, $verify, $issued, $expiry);
        $payload['authentication'] = $auth; $payload['status'] = 'PREVIEW';
        mpi_spreader_bar_render_certificate_pdf($path, $payload);
    } elseif ($family === 'hook') {
        $ready = hook_readiness($inspection, $rows, $items, $number, $issued, $expiry);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = hook_payload($inspection, $rows, $items, $number, 0, $verify, $issued, $expiry);
        $payload['authentication'] = $auth;
        $payload['status'] = 'PREVIEW';
        hook_render_certificate_pdf($path, $payload);
    } else {
        $ready = ccu_certificate_readiness($inspection, $rows, $items, $number, $issued, $expiry);
        if (!$ready['ready']) api_error('Certificate preview could not be generated. Complete the listed fields.', 422, $ready);
        $payload = ccu_payload($inspection, $rows, $items, $number, 0, $verify, $issued, $expiry, 'PREVIEW000000000');
        $payload['authentication'] = $auth;
        $payload['status'] = 'PREVIEW';
        ccu_render_certificate_pdf($path, $payload);
    }
} catch (CertificateIssueException $e) {
    api_error($e->getMessage(), $e->httpStatus(), $e->validation());
} catch (Throwable $e) {
    $reference = strtoupper(substr(hash('sha256', uniqid('preview', true)), 0, 12));
    $line = '[' . gmdate('c') . '] preview ' . $reference . ' inspection=' . $id . ' ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
    error_log($line);
    try { $logDir = ensure_private_storage_dir('logs'); file_put_contents($logDir . DIRECTORY_SEPARATOR . 'preview-error.log', $line, FILE_APPEND | LOCK_EX); } catch (Throwable $ignored) { error_log('Unable to write preview log ' . $reference); }
    api_error('Certificate preview generation failed. Reference log ' . $reference . '.', 500);
}
if (!is_file($path) || filesize($path) < 100) api_error('Certificate preview generation did not produce a readable PDF.', 500);

$_SESSION['certificate_previews'][$token] = ['path' => $path, 'inspection_id' => $id, 'expires_at' => time() + 900, 'download_name' => preg_replace('/[^A-Z0-9._-]/i', '_', $number) . '-preview.pdf'];
audit_log((int) $user['id'], 'certificates.preview_generated', 'inspection', $id, ['template_family' => $family]);
respond(['success' => true, 'inspection_id' => $id, 'preview_url' => api_url('certificates/preview-download.php?token=' . $token), 'expires_in' => 900]);
