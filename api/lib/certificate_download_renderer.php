<?php

declare(strict_types=1);

require_once __DIR__ . '/certificate_engine.php';

function certificate_revision_authentication(array $revision): array
{
    $inspectorKey = (string) ($revision['inspector_signature_path_snapshot'] ?? '');
    $authenticatorKey = (string) ($revision['authenticator_signature_path_snapshot'] ?? '');
    $stampKey = (string) ($revision['company_stamp_path_snapshot'] ?? '');
    return [
        'inspector_signature_path' => $inspectorKey !== '' ? resolve_storage_path($inspectorKey) : null,
        'inspector_signature_source' => (string) ($revision['inspector_signature_source'] ?? 'none'),
        'inspector_signature_storage_key' => $inspectorKey !== '' ? $inspectorKey : null,
        'authenticator_signature_path' => $authenticatorKey !== '' ? resolve_storage_path($authenticatorKey) : null,
        'authenticator_signature_source' => (string) ($revision['authenticator_signature_source'] ?? 'none'),
        'authenticator_signature_storage_key' => $authenticatorKey !== '' ? $authenticatorKey : null,
        'company_stamp_path' => $stampKey !== '' ? resolve_storage_path($stampKey) : null,
        'company_stamp_storage_key' => $stampKey !== '' ? $stampKey : null,
        'inspector_name_snapshot' => (string) ($revision['inspector_name_snapshot'] ?? ''),
        'inspector_qualifications_snapshot' => (string) ($revision['inspector_qualifications_snapshot'] ?? ''),
        'authenticator_name_snapshot' => (string) ($revision['authenticator_name_snapshot'] ?? ''),
        'authenticator_qualifications_snapshot' => (string) ($revision['authenticator_qualifications_snapshot'] ?? ''),
    ];
}

function certificate_apply_revision_identity(array $payload, array $authentication): array
{
    $inspectorName = (string) ($authentication['inspector_name_snapshot'] ?? '');
    $inspectorQualifications = (string) ($authentication['inspector_qualifications_snapshot'] ?? '');
    $authenticatorName = (string) ($authentication['authenticator_name_snapshot'] ?? '');
    $authenticatorQualifications = (string) ($authentication['authenticator_qualifications_snapshot'] ?? '');
    $payload['authentication'] = $authentication;
    $payload['inspector_name'] = $inspectorName;
    $payload['inspector_qualification'] = $inspectorQualifications;
    $payload['authenticator_name'] = $authenticatorName;
    $payload['authenticator_qualification'] = $authenticatorQualifications;
    if (isset($payload['fields']) && is_array($payload['fields'])) {
        foreach (['inspector_name', 'inspector_name_snapshot'] as $key) $payload['fields'][$key] = $inspectorName;
        foreach (['inspector_qualification', 'inspector_qualification_snapshot'] as $key) $payload['fields'][$key] = $inspectorQualifications;
        foreach (['authenticator_name', 'authenticator_name_snapshot'] as $key) $payload['fields'][$key] = $authenticatorName;
        foreach (['authenticator_qualification', 'authenticator_qualifications', 'authenticator_qualification_snapshot'] as $key) $payload['fields'][$key] = $authenticatorQualifications;
    }
    return $payload;
}

function certificate_render_existing_download(array $certificate): ?string
{
    $inspectionStmt = db()->prepare('SELECT i.*,c.name client_name,c.address client_address,e.asset_code,e.name equipment_name,cat.name category_name,cat.validity_months,cat.template_family,cat.layout_key,ft.show_inspector_signature,ft.show_authenticator_signature,ft.show_company_stamp,u.name inspector_name,u.qualification inspector_qualification FROM inspections i JOIN clients c ON c.id=i.client_id JOIN equipment e ON e.id=i.equipment_id JOIN certification_categories cat ON cat.id=i.category_id JOIN form_templates ft ON ft.id=i.form_template_id JOIN users u ON u.id=i.inspector_id WHERE i.id=? LIMIT 1');
    $inspectionStmt->execute([(int) $certificate['inspection_id']]);
    $inspection = $inspectionStmt->fetch();
    if (!$inspection) return null;
    $family = (string) ($inspection['template_family'] ?? '');
    $supported = ['ccu_visual','chain_block','endless_round_webbing_sling','lever_hoist','flat_webbing_sling','eye_bolt','shackles','hook','mpi_spreader_bar','general_lifting_accessory','general_thorough_examination'];
    if (!in_array($family, $supported, true)) return null;

    $revisionStmt = db()->prepare('SELECT * FROM certificate_revisions WHERE certificate_id=? AND revision=? LIMIT 1');
    $revisionStmt->execute([(int) $certificate['id'], (int) $certificate['revision']]);
    $revision = $revisionStmt->fetch();
    if (!$revision) return null;

    $fieldStmt = db()->prepare('SELECT f.field_key,f.label,f.field_type,f.is_required,f.pdf_section,iv.value_text FROM form_fields f LEFT JOIN inspection_values iv ON iv.field_id=f.id AND iv.inspection_id=? WHERE f.template_id=? ORDER BY f.sort_order,f.id');
    $fieldStmt->execute([(int) $inspection['id'], (int) $inspection['form_template_id']]);
    $rows = $fieldStmt->fetchAll() ?: [];
    $itemStmt = db()->prepare('SELECT section_key,row_index,column_key,value_text FROM inspection_items WHERE inspection_id=? ORDER BY section_key,row_index,id');
    $itemStmt->execute([(int) $inspection['id']]);
    $items = $itemStmt->fetchAll() ?: [];

    $number = (string) $certificate['certificate_number'];
    $revisionNumber = (int) $certificate['revision'];
    $verify = app_url('/verify/' . (string) $certificate['verification_token']);
    $issued = (string) $certificate['issued_at'];
    $expiry = (string) $certificate['expires_at'];
    $fingerprint = strtoupper(substr(hash('sha256', $number . '|' . (string) $certificate['verification_token'] . '|' . $revisionNumber . '|' . $issued . '|' . $expiry), 0, 16));

    if ($family === 'ccu_visual') $payload = ccu_payload($inspection,$rows,$items,$number,$revisionNumber,$verify,$issued,$expiry,$fingerprint);
    elseif ($family === 'chain_block') $payload = chain_block_payload($inspection,$rows,$number,$revisionNumber,$verify,$issued,$expiry);
    elseif ($family === 'endless_round_webbing_sling') $payload = endless_round_webbing_sling_payload($inspection,$rows,$number,$revisionNumber,$verify,$issued,$expiry);
    elseif ($family === 'lever_hoist') $payload = lever_hoist_payload($inspection,$rows,$number,$revisionNumber,$verify,$issued,$expiry);
    elseif ($family === 'flat_webbing_sling') $payload = flat_webbing_sling_payload($inspection,$rows,$number,$revisionNumber,$verify,$issued,$expiry);
    elseif ($family === 'eye_bolt') $payload = eye_bolt_payload($inspection,$rows,$items,$number,$revisionNumber,$verify,$issued,$expiry);
    elseif ($family === 'shackles') $payload = shackles_payload($inspection,$rows,$items,$number,$revisionNumber,$verify,$issued,$expiry);
    elseif ($family === 'hook') $payload = hook_payload($inspection,$rows,$items,$number,$revisionNumber,$verify,$issued,$expiry);
    elseif ($family === 'mpi_spreader_bar') $payload = mpi_spreader_bar_payload($inspection,$rows,$number,$revisionNumber,$verify,$issued,$expiry);
    elseif ($family === 'general_lifting_accessory') $payload = general_lifting_accessory_payload($inspection,$rows,$items,$number,$revisionNumber,$verify,$issued,$expiry);
    else $payload = general_thorough_examination_payload($inspection,$rows,$number,$revisionNumber,$verify,$issued,$expiry);

    $authentication = certificate_revision_authentication($revision);
    if ((int) ($inspection['show_inspector_signature'] ?? 1) !== 1) $authentication['inspector_signature_path'] = null;
    if ((int) ($inspection['show_authenticator_signature'] ?? 1) !== 1) $authentication['authenticator_signature_path'] = null;
    if ((int) ($inspection['show_company_stamp'] ?? 0) !== 1) $authentication['company_stamp_path'] = null;
    $payload = certificate_apply_revision_identity($payload, $authentication);
    $payload['status'] = certificate_effective_status($certificate);

    $temporaryBase = tempnam(sys_get_temp_dir(), 'juva-cert-download-');
    if ($temporaryBase === false) throw new RuntimeException('Unable to create certificate download workspace.');
    $path = $temporaryBase . '.pdf';
    @unlink($temporaryBase);
    try {
        if ($family === 'ccu_visual') ccu_render_certificate_pdf($path,$payload);
        elseif ($family === 'chain_block') chain_block_render_certificate_pdf($path,$payload);
        elseif ($family === 'endless_round_webbing_sling') endless_round_webbing_sling_render_certificate_pdf($path,$payload);
        elseif ($family === 'lever_hoist') lever_hoist_render_certificate_pdf($path,$payload);
        elseif ($family === 'flat_webbing_sling') flat_webbing_sling_render_certificate_pdf($path,$payload);
        elseif ($family === 'eye_bolt') eye_bolt_render_certificate_pdf($path,$payload);
        elseif ($family === 'shackles') shackles_render_certificate_pdf($path,$payload);
        elseif ($family === 'hook') hook_render_certificate_pdf($path,$payload);
        elseif ($family === 'mpi_spreader_bar') mpi_spreader_bar_render_certificate_pdf($path,$payload);
        elseif ($family === 'general_lifting_accessory') general_lifting_accessory_render_certificate_pdf($path,$payload);
        else general_thorough_examination_render_certificate_pdf($path,$payload);
    } catch (Throwable $error) {
        @unlink($path);
        throw $error;
    }
    certificate_archive_hash($path);
    return $path;
}