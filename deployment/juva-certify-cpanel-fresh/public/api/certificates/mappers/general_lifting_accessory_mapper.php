<?php

declare(strict_types=1);

require_once __DIR__ . '/eye_bolt_mapper.php';

function general_lifting_accessory_readiness(array $inspection, array $rows, array $items, string $number, string $issuedAt, string $expiry): array
{
    return eye_bolt_readiness($inspection, $rows, $items, $number, $issuedAt, $expiry);
}

function general_lifting_accessory_validate(array $inspection, array $rows, array $items, string $number, string $issuedAt, string $expiry): void
{
    $result = general_lifting_accessory_readiness($inspection, $rows, $items, $number, $issuedAt, $expiry);
    if (!$result['ready']) {
        throw new CertificateIssueException('General Lifting Accessories certificate cannot be issued. Complete the listed fields.', 422, $result);
    }
}

function general_lifting_accessory_payload(array $inspection, array $rows, array $items, string $number, int $revision, string $verifyUrl, string $issuedAt, string $expiry): array
{
    return eye_bolt_payload($inspection, $rows, $items, $number, $revision, $verifyUrl, $issuedAt, $expiry);
}
