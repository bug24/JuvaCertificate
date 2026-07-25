<?php

declare(strict_types=1);

function certificate_effective_status(array $certificate, ?string $asOf = null): string
{
    $stored = strtolower(trim((string) ($certificate['status'] ?? 'valid')));
    if ($stored === 'revoked' || !empty($certificate['revoked_at'])) {
        return 'revoked';
    }
    $today = $asOf ?: gmdate('Y-m-d');
    $expiry = trim((string) ($certificate['expires_at'] ?? $certificate['expiry_date'] ?? ''));
    if ($expiry !== '' && $expiry < $today) {
        return 'expired';
    }
    return 'valid';
}
