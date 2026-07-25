<?php

declare(strict_types=1);

function certificate_authentication_assets(array $inspection, array $fieldRows, array $attachments): array
{
    $fields = [];
    foreach ($fieldRows as $row) {
        $fields[(string) ($row['field_key'] ?? '')] = trim((string) ($row['value_text'] ?? ''));
    }

    $inspectionSignature = null;
    $inspectionSignatureKey = null;
    foreach (array_reverse($attachments) as $attachment) {
        if ((string) ($attachment['attachment_type'] ?? '') === 'signature') {
            $path = resolve_storage_path((string) ($attachment['file_path'] ?? ''));
            if ($path) { $inspectionSignature = $path; $inspectionSignatureKey = (string) ($attachment['file_path'] ?? ''); break; }
        }
    }

    $profileSignature = null;
    $profileSignatureKey = null;
    $profileStmt = db()->prepare('SELECT signature_path FROM users WHERE id=? AND signature_is_active=1 LIMIT 1');
    $profileStmt->execute([(int) $inspection['inspector_id']]);
    $profile = $profileStmt->fetch();
    if ($profile && !empty($profile['signature_path'])) { $profileSignatureKey = (string) $profile['signature_path']; $profileSignature = resolve_storage_path($profileSignatureKey); }

    $inspectorPath = $inspectionSignature ?: $profileSignature;
    $inspectorSource = $inspectionSignature ? 'inspection_upload' : ($profileSignature ? 'user_profile' : 'none');
    $inspectorKey = $inspectionSignature ? $inspectionSignatureKey : ($profileSignature ? $profileSignatureKey : null);

    $authenticatorPath = null;
    $authenticatorSource = 'none';
    $authenticatorKey = null;
    $authenticatorName = (string) ($fields['authenticator_name'] ?? '');
    if ($authenticatorName !== '') {
        $authStmt = db()->prepare('SELECT signature_path FROM users WHERE name=? AND status=? AND signature_is_active=1 LIMIT 1');
        $authStmt->execute([$authenticatorName, 'active']);
        $authUser = $authStmt->fetch();
        if ($authUser && !empty($authUser['signature_path'])) {
            $authenticatorPath = resolve_storage_path((string) $authUser['signature_path']);
            if ($authenticatorPath) { $authenticatorSource = 'user_profile'; $authenticatorKey = (string) $authUser['signature_path']; }
        }
    }
    if (!$authenticatorPath) {
        $approvalStmt = db()->prepare('SELECT u.signature_path FROM approvals a JOIN users u ON u.id=a.reviewer_id WHERE a.inspection_id=? AND a.decision=? AND u.signature_is_active=1 ORDER BY a.id DESC LIMIT 1');
        $approvalStmt->execute([(int) $inspection['id'], 'approved']);
        $approver = $approvalStmt->fetch();
        if ($approver && !empty($approver['signature_path'])) {
            $authenticatorPath = resolve_storage_path((string) $approver['signature_path']);
            if ($authenticatorPath) { $authenticatorSource = 'user_profile'; $authenticatorKey = (string) $approver['signature_path']; }
        }
    }

    $branding = db()->query('SELECT * FROM certificate_branding_settings WHERE id=1 LIMIT 1')->fetch() ?: [];
    $stampPath = null;
    $stampKey = null;
    if ((int) ($branding['company_stamp_is_active'] ?? 0) === 1 && !empty($branding['company_stamp_path'])) {
        $stampKey = (string) $branding['company_stamp_path'];
        $stampPath = resolve_storage_path($stampKey);
    }

    return [
        'inspector_signature_path' => $inspectorPath,
        'inspector_signature_source' => $inspectorSource,
        'inspector_signature_storage_key' => $inspectorKey,
        'authenticator_signature_path' => $authenticatorPath,
        'authenticator_signature_source' => $authenticatorSource,
        'authenticator_signature_storage_key' => $authenticatorKey,
        'company_stamp_path' => $stampPath,
        'company_stamp_storage_key' => $stampKey,
        'inspector_name_snapshot' => (string) (($fields['inspector_name_snapshot'] ?? '') ?: ($inspection['inspector_name'] ?? '')),
        'inspector_qualifications_snapshot' => (string) (($fields['inspector_qualification_snapshot'] ?? '') ?: ($inspection['inspector_qualification'] ?? '')),
        'authenticator_name_snapshot' => $authenticatorName,
        'authenticator_qualifications_snapshot' => (string) ($fields['authenticator_qualification'] ?? ''),
    ];
}
