<?php

declare(strict_types=1);

function certificate_professional_identity(array $user): string
{
    $parts = [];
    foreach (['qualification', 'job_title', 'professional_memberships', 'certificate_signing_role'] as $key) {
        $value = trim((string) ($user[$key] ?? ''));
        if ($value !== '' && !in_array(strtolower($value), array_map('strtolower', $parts), true)) $parts[] = $value;
    }
    return implode(', ', $parts);
}

function certificate_identity_user(int $userId): ?array
{
    if ($userId < 1) return null;
    $stmt = db()->prepare('SELECT id,name,qualification,job_title,professional_memberships,certificate_signing_role,signature_path,signature_is_active,status FROM users WHERE id=? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function certificate_active_profile_signature(?array $user): array
{
    if (!$user || (int) ($user['signature_is_active'] ?? 0) !== 1 || empty($user['signature_path'])) return [null, null];
    $key = (string) $user['signature_path'];
    $path = resolve_storage_path($key);
    return $path ? [$path, $key] : [null, null];
}

function certificate_active_company_stamp(): array
{
    $branding = db()->query('SELECT company_stamp_path,company_stamp_is_active FROM certificate_branding_settings WHERE id=1 LIMIT 1')->fetch() ?: [];
    if ((int) ($branding['company_stamp_is_active'] ?? 0) !== 1 || empty($branding['company_stamp_path'])) return [null, null];
    $key = (string) $branding['company_stamp_path'];
    $path = resolve_storage_path($key);
    return $path ? [$path, $key] : [null, null];
}

function certificate_apply_identity_to_field_rows(array $fieldRows, array $authentication): array
{
    $values = [
        'inspector_name' => (string) ($authentication['inspector_name_snapshot'] ?? ''),
        'inspector_name_snapshot' => (string) ($authentication['inspector_name_snapshot'] ?? ''),
        'inspector_qualification' => (string) ($authentication['inspector_qualifications_snapshot'] ?? ''),
        'inspector_qualification_snapshot' => (string) ($authentication['inspector_qualifications_snapshot'] ?? ''),
        'authenticator_name' => (string) ($authentication['authenticator_name_snapshot'] ?? ''),
        'authenticator_name_snapshot' => (string) ($authentication['authenticator_name_snapshot'] ?? ''),
        'authenticator_qualification' => (string) ($authentication['authenticator_qualifications_snapshot'] ?? ''),
        'authenticator_qualifications' => (string) ($authentication['authenticator_qualifications_snapshot'] ?? ''),
        'authenticator_qualification_snapshot' => (string) ($authentication['authenticator_qualifications_snapshot'] ?? ''),
    ];
    foreach ($fieldRows as &$row) {
        $key = (string) ($row['field_key'] ?? '');
        if (array_key_exists($key, $values)) $row['value_text'] = $values[$key];
    }
    unset($row);
    return $fieldRows;
}

function certificate_authentication_assets(array $inspection, array $fieldRows, array $attachments): array
{
    try {
        return certificate_authentication_assets_unsafe($inspection, $fieldRows, $attachments);
    } catch (Throwable $error) {
        error_log('Optional certificate authentication assets unavailable: ' . $error->getMessage());
        return ['inspector_signature_path'=>null,'inspector_signature_source'=>'none','inspector_signature_storage_key'=>null,'authenticator_signature_path'=>null,'authenticator_signature_source'=>'none','authenticator_signature_storage_key'=>null,'company_stamp_path'=>null,'company_stamp_storage_key'=>null,'inspector_name_snapshot'=>(string)($inspection['inspector_name']??''),'inspector_qualifications_snapshot'=>(string)($inspection['inspector_qualification']??''),'authenticator_name_snapshot'=>'','authenticator_qualifications_snapshot'=>''];
    }
}

function certificate_authentication_assets_unsafe(array $inspection, array $fieldRows, array $attachments): array
{
    $inspectionSignature = null;
    $inspectionSignatureKey = null;
    foreach (array_reverse($attachments) as $attachment) {
        if ((string) ($attachment['attachment_type'] ?? '') === 'signature') {
            $path = resolve_storage_path((string) ($attachment['file_path'] ?? ''));
            if ($path) { $inspectionSignature = $path; $inspectionSignatureKey = (string) ($attachment['file_path'] ?? ''); break; }
        }
    }

    $inspector = certificate_identity_user((int) ($inspection['inspector_id'] ?? 0));
    [$profileSignature, $profileSignatureKey] = certificate_active_profile_signature($inspector);
    $inspectorPath = $inspectionSignature ?: $profileSignature;
    $inspectorSource = $inspectionSignature ? 'inspection_upload' : ($profileSignature ? 'user_profile' : 'none');
    $inspectorKey = $inspectionSignature ? $inspectionSignatureKey : ($profileSignature ? $profileSignatureKey : null);

    $authenticator = certificate_identity_user((int) ($inspection['authenticator_id'] ?? 0));
    [$authenticatorPath, $authenticatorKey] = certificate_active_profile_signature($authenticator);
    $authenticatorSource = $authenticatorPath ? 'user_profile' : 'none';

    [$stampPath, $stampKey] = certificate_active_company_stamp();

    return [
        'inspector_signature_path' => $inspectorPath,
        'inspector_signature_source' => $inspectorSource,
        'inspector_signature_storage_key' => $inspectorKey,
        'authenticator_signature_path' => $authenticatorPath,
        'authenticator_signature_source' => $authenticatorSource,
        'authenticator_signature_storage_key' => $authenticatorKey,
        'company_stamp_path' => $stampPath,
        'company_stamp_storage_key' => $stampKey,
        'inspector_name_snapshot' => (string) ($inspector['name'] ?? $inspection['inspector_name'] ?? ''),
        'inspector_qualifications_snapshot' => $inspector ? certificate_professional_identity($inspector) : (string) ($inspection['inspector_qualification'] ?? ''),
        'authenticator_name_snapshot' => (string) ($authenticator['name'] ?? ''),
        'authenticator_qualifications_snapshot' => $authenticator ? certificate_professional_identity($authenticator) : '',
    ];
}
