<?php

declare(strict_types=1);

require_once __DIR__ . '/endless_round_webbing_sling_mapper.php';

function general_thorough_examination_readiness(array $inspection, array $rows, string $number, string $issuedAt, string $expiry, array $attachments = [], bool $requireEvidence = false, int $minimumEvidenceFiles = 0): array
{
    return endless_round_webbing_sling_readiness($inspection, $rows, $number, $issuedAt, $expiry, $attachments, $requireEvidence, $minimumEvidenceFiles);
}

function general_thorough_examination_validate(array $inspection, array $rows, string $number, string $issuedAt, string $expiry, array $attachments = [], bool $requireEvidence = false, int $minimumEvidenceFiles = 0): void
{
    $result = general_thorough_examination_readiness($inspection, $rows, $number, $issuedAt, $expiry, $attachments, $requireEvidence, $minimumEvidenceFiles);
    if (!$result['ready']) {
        throw new CertificateIssueException('General Thorough Examination certificate cannot be issued. Complete the listed fields.', 422, $result);
    }
}

function general_thorough_examination_payload(array $inspection, array $rows, string $number, int $revision, string $verifyUrl, string $issuedAt, string $expiry): array
{
    $payload = endless_round_webbing_sling_payload($inspection, $rows, $number, $revision, $verifyUrl, $issuedAt, $expiry);
    $payload['layout_profile'] = 'general_thorough_examination';
    return $payload;
}
