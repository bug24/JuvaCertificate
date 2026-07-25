<?php
require_once __DIR__ . '/endless_round_webbing_sling_mapper.php';

function lever_hoist_field_map(array $rows): array
{
    return endless_round_webbing_sling_field_map($rows);
}

function lever_hoist_readiness(array $inspection, array $rows, string $number, string $issuedAt, string $expiry, array $attachments = [], bool $requireEvidence = false, int $minimumEvidenceFiles = 0): array
{
    $result = endless_round_webbing_sling_readiness($inspection, $rows, $number, $issuedAt, $expiry, $attachments, $requireEvidence, $minimumEvidenceFiles);
    $fields = lever_hoist_field_map($rows);
    if (endless_round_webbing_sling_boolean($fields['defect_future_danger'] ?? null) === 'Yes'
        && trim((string)($fields['future_danger_description'] ?? '')) === '') {
        $reason = 'Describe the defect that could become dangerous.';
        $result['validation_errors']['future_danger_description'] = $reason;
        $result['blocking_items'][] = endless_round_webbing_sling_blocker('validation', 'future_danger_description', 'Not Yet Dangerous Defect Description', 'Inspection fields', $reason);
        $result['ready'] = false;
    }
    return $result;
}

function lever_hoist_validate(array $inspection, array $rows, string $number, string $issuedAt, string $expiry, array $attachments = [], bool $requireEvidence = false, int $minimumEvidenceFiles = 0): void
{
    $result = lever_hoist_readiness($inspection, $rows, $number, $issuedAt, $expiry, $attachments, $requireEvidence, $minimumEvidenceFiles);
    if (!$result['ready']) {
        throw new CertificateIssueException('Lever Hoist certificate cannot be issued. Complete the listed fields.', 422, $result);
    }
}

function lever_hoist_payload(array $inspection, array $rows, string $number, int $revision, string $verifyUrl, string $issuedAt, string $expiry): array
{
    $payload = endless_round_webbing_sling_payload($inspection, $rows, $number, $revision, $verifyUrl, $issuedAt, $expiry);
    $payload['equipment_name'] = (string) ($inspection['equipment_name'] ?? 'LEVER HOIST');
    $payload['layout_profile'] = 'lever_hoist';
    return $payload;
}
