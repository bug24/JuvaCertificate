<?php
require_once __DIR__ . '/../api/lib/certificate_status.php';

$cases = [
    'VALID' => [['status' => 'valid', 'expires_at' => '2099-12-31', 'revoked_at' => null], 'valid'],
    'EXPIRED' => [['status' => 'valid', 'expires_at' => '2020-01-01', 'revoked_at' => null], 'expired'],
    'REVOKED' => [['status' => 'revoked', 'expires_at' => '2099-12-31', 'revoked_at' => '2026-01-01 00:00:00'], 'revoked'],
];
$failed = 0;
foreach ($cases as $name => $case) {
    $actual = certificate_effective_status($case[0], '2026-07-12');
    $ok = $actual === $case[1];
    echo $name . ': ' . ($ok ? 'PASS' : 'FAIL (' . $actual . ')') . PHP_EOL;
    if (!$ok) { $failed++; }
}
exit($failed > 0 ? 1 : 0);
