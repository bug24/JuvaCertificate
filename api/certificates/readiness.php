<?php

declare(strict_types=1);

function readiness_fallback_json(array $data, int $status = 200): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        http_response_code($status);
    }
    echo json_encode(['data' => $data, 'error' => null, 'validation' => null], JSON_UNESCAPED_SLASHES);
    exit;
}

function readiness_log(Throwable $exception, int $inspectionId = 0): string
{
    $logId = strtoupper(substr(hash('sha256', uniqid('readiness', true)), 0, 12));
    $line = '[' . gmdate('c') . '] readiness ' . $logId . ' inspection=' . $inspectionId . ' ' . get_class($exception) . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
    error_log($line);
    try {
        if (function_exists('ensure_private_storage_dir')) {
            $dir = ensure_private_storage_dir('logs');
        } else {
            $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage-private' . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
        if (is_dir($dir) && is_writable($dir)) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . 'readiness-error.log', $line, FILE_APPEND | LOCK_EX);
        }
    } catch (Throwable $loggingFailure) {
        error_log('Unable to write readiness log ' . $logId . ': ' . $loggingFailure->getMessage());
    }
    return $logId;
}

try {
    require_once __DIR__ . '/../_bootstrap.php';
    require_once __DIR__ . '/../lib/certificate_engine.php';

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

    $supportedFamilies = ['ccu_visual', 'chain_block', 'endless_round_webbing_sling', 'lever_hoist', 'flat_webbing_sling', 'eye_bolt', 'shackles', 'hook', 'mpi_spreader_bar', 'general_lifting_accessory', 'general_thorough_examination'];
    $family = (string) ($inspection['template_family'] ?? '');
    if (!in_array($family, $supportedFamilies, true)) {
        respond(['success' => true, 'ready' => true, 'missing_fields' => [], 'missing_sections' => [], 'validation_errors' => [], 'blocking_items' => [], 'warnings' => ['Detailed readiness checks are not configured for this certificate template.'], 'can_save_draft' => true, 'can_preview' => true, 'can_submit' => true, 'can_approve' => true, 'can_issue' => true]);
    }

    $fieldStmt = db()->prepare('SELECT f.field_key, f.label, f.field_type, f.is_required, f.pdf_section, iv.value_text FROM form_fields f LEFT JOIN inspection_values iv ON iv.field_id=f.id AND iv.inspection_id=? WHERE f.template_id=? ORDER BY f.sort_order,f.id');
    $fieldStmt->execute([$inspectionId, (int) $inspection['form_template_id']]);
    $fieldRows = $fieldStmt->fetchAll() ?: [];

    $itemStmt = db()->prepare('SELECT section_key,row_index,column_key,value_text FROM inspection_items WHERE inspection_id=? ORDER BY section_key,row_index,id');
    $itemStmt->execute([$inspectionId]);
    $itemRows = $itemStmt->fetchAll() ?: [];

    $certStmt = db()->prepare('SELECT certificate_number FROM certificates WHERE inspection_id=? LIMIT 1');
    $certStmt->execute([$inspectionId]);
    $certificate = $certStmt->fetch();
    $number = $certificate ? (string) $certificate['certificate_number'] : (string) $inspection['reference'];
    $issuedAt = gmdate('Y-m-d');
    $expiry = !empty($inspection['next_due_date']) ? (string) $inspection['next_due_date'] : gmdate('Y-m-d', strtotime('+' . max(1, (int) $inspection['validity_months']) . ' months'));

    $attachmentStmt = db()->prepare('SELECT id,attachment_type,file_name,file_path,mime_type,file_size FROM inspection_attachments WHERE inspection_id=? ORDER BY id');
    $attachmentStmt->execute([$inspectionId]);
    $attachments = $attachmentStmt->fetchAll() ?: [];

    $authentication = [
        'inspector_signature_path' => null,
        'authenticator_signature_path' => null,
        'company_stamp_path' => null,
    ];
    $result = null;
    $preview = null;
    $requiresEvidence = (int) ($inspection['requires_evidence'] ?? 0) === 1;
    $minimumEvidence = (int) ($inspection['minimum_evidence_files'] ?? 0);

    if (in_array($family, ['eye_bolt', 'shackles', 'hook', 'general_lifting_accessory'], true)) {
        $functionMap = [
            'shackles' => 'shackles_readiness',
            'hook' => 'hook_readiness',
            'eye_bolt' => 'eye_bolt_readiness',
            'general_lifting_accessory' => 'general_lifting_accessory_readiness',
        ];
        $function = $functionMap[$family];
        if (!is_callable($function)) {
            throw new RuntimeException('Readiness function is not available: ' . $function);
        }
        $result = $function($inspection, $fieldRows, $itemRows, $number, $issuedAt, $expiry);
        $preview = $result;
    } elseif ($family === 'mpi_spreader_bar') {
        if (!is_callable('mpi_spreader_bar_readiness')) {
            throw new RuntimeException('Readiness function is not available: mpi_spreader_bar_readiness');
        }
        $preview = mpi_spreader_bar_readiness($inspection, $fieldRows, $number, $issuedAt, $expiry, $attachments, false, 0);
        $result = mpi_spreader_bar_readiness($inspection, $fieldRows, $number, $issuedAt, $expiry, $attachments, $requiresEvidence, $minimumEvidence);
    } elseif (in_array($family, ['chain_block', 'endless_round_webbing_sling', 'lever_hoist', 'flat_webbing_sling', 'general_thorough_examination'], true)) {
        $functionMap = [
            'chain_block' => 'chain_block_readiness',
            'endless_round_webbing_sling' => 'endless_round_webbing_sling_readiness',
            'flat_webbing_sling' => 'flat_webbing_sling_readiness',
            'lever_hoist' => 'lever_hoist_readiness',
            'general_thorough_examination' => 'general_thorough_examination_readiness',
        ];
        $function = $functionMap[$family];
        if (!is_callable($function)) {
            throw new RuntimeException('Readiness function is not available: ' . $function);
        }
        $preview = $function($inspection, $fieldRows, $number, $issuedAt, $expiry, $attachments, false, 0);
        $result = $function($inspection, $fieldRows, $number, $issuedAt, $expiry, $attachments, $requiresEvidence, $minimumEvidence);
    } else {
        if (!is_callable('ccu_certificate_readiness')) {
            throw new RuntimeException('Readiness function is not available: ccu_certificate_readiness');
        }
        $result = ccu_certificate_readiness($inspection, $fieldRows, $itemRows, $number, $issuedAt, $expiry);
        $preview = $result;
    }

    $result = is_array($result) ? $result : [];
    $preview = is_array($preview) ? $preview : $result;
    $result += ['missing_fields' => [], 'missing_sections' => [], 'validation_errors' => [], 'blocking_items' => [], 'warnings' => []];
    $preview += ['ready' => false, 'validation_errors' => []];

    $evidenceCount = 0;
    $signatureCount = 0;
    foreach ($attachments as $attachment) {
        if ((string) ($attachment['attachment_type'] ?? 'evidence') === 'signature') {
            $signatureCount++;
        } else {
            $evidenceCount++;
        }
    }

    $warnings = [];
    if ($evidenceCount === 0 && !$requiresEvidence) {
        $warnings[] = 'No inspection evidence attached';
    }
    if ($signatureCount === 0) {
        $warnings[] = 'No inspection-specific signature uploaded; profile signature or an empty signature area will be used';
    }

    $authValidation = [
        'inspector_signature' => [(int) ($inspection['requires_inspector_signature'] ?? 0) === 1 && empty($authentication['inspector_signature_path']), 'Inspector signature', 'Upload an inspection signature or activate the assigned inspector profile signature.'],
        'authenticator_signature' => [(int) ($inspection['requires_authenticator_signature'] ?? 0) === 1 && empty($authentication['authenticator_signature_path']), 'Authenticator signature', 'An active authenticator or approver profile signature is required.'],
        'company_stamp' => [(int) ($inspection['requires_company_stamp'] ?? 0) === 1 && empty($authentication['company_stamp_path']), 'Company stamp', 'Upload and activate the JUVA company stamp.'],
    ];
    foreach ($authValidation as $key => $item) {
        if ($item[0]) {
            $result['validation_errors'][$key] = $item[2];
            $result['blocking_items'][] = ['type' => 'validation', 'key' => $key, 'field_key' => $key, 'label' => $item[1], 'section' => 'Evidence', 'step' => 4, 'reason' => $item[2]];
        }
    }

    $result['ready'] = empty($result['missing_fields']) && empty($result['missing_sections']) && empty($result['validation_errors']);
    $result['success'] = (bool) $result['ready'];
    $result['can_save_draft'] = true;
    $result['can_preview'] = (bool) ($preview['ready'] ?? false);
    $result['can_submit'] = (bool) $result['ready'];
    $result['can_approve'] = (bool) $result['ready'];
    $result['can_issue'] = (bool) $result['ready'];
    $result['optional_warnings'] = $warnings;
    $result['warnings'] = array_values(array_unique(array_merge($result['warnings'] ?? [], $warnings)));
    $result['failed_pending_uploads'] = [];
    $result['preview_validation_errors'] = $preview['validation_errors'] ?? [];
    $result['inspection_id'] = $inspectionId;

    respond($result);
} catch (Throwable $exception) {
    $inspectionId = isset($_GET['inspection_id']) ? (int) $_GET['inspection_id'] : 0;
    $logId = readiness_log($exception, $inspectionId);
    readiness_fallback_json([
        'success' => false,
        'ready' => false,
        'missing_fields' => [],
        'missing_sections' => [],
        'validation_errors' => ['internal' => 'Readiness could not be completed. Reference log ' . $logId . '.'],
        'blocking_items' => [[
            'type' => 'internal',
            'key' => 'readiness_internal_error',
            'field_key' => 'readiness_internal_error',
            'label' => 'Readiness check',
            'section' => 'Review & submit',
            'step' => 5,
            'reason' => 'The server could not complete readiness verification. Reference log ' . $logId . '.',
        ]],
        'warnings' => [],
        'optional_warnings' => [],
        'can_save_draft' => true,
        'can_preview' => false,
        'can_submit' => false,
        'can_approve' => false,
        'can_issue' => false,
        'inspection_id' => $inspectionId,
        'log_id' => $logId,
    ], 200);
}
