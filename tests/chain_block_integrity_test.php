<?php
$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once __DIR__ . '/../api/_bootstrap.php';
require_once __DIR__ . '/../api/certificates/templates/chain_block.php';

$inspection = array(
    'inspection_date' => '2026-07-13',
    'next_due_date' => '2027-07-13',
    'client_name' => 'Regression Client',
    'equipment_name' => 'Regression Chain Block',
);

$result = chain_block_readiness(
    $inspection,
    array(),
    'JUVA/TEST/CHBLK/001',
    '2026-07-13',
    '2027-07-13',
    array(),
    true
);

$keys = array();
foreach ($result['missing_fields'] as $field) {
    $keys[] = (string) ($field['field_key'] ?? '');
}

$failed = 0;
if (!in_array('inspection_evidence', $keys, true)) {
    echo "CHAIN EVIDENCE REQUIRED: FAIL\n";
    $failed++;
} else {
    echo "CHAIN EVIDENCE REQUIRED: PASS\n";
}

if ($result['ready'] !== false) {
    echo "INCOMPLETE CHAIN READINESS: FAIL\n";
    $failed++;
} else {
    echo "INCOMPLETE CHAIN READINESS: PASS\n";
}

$optional = chain_block_readiness(
    $inspection,
    array(),
    'JUVA/TEST/CHBLK/002',
    '2026-07-13',
    '2027-07-13',
    array(),
    false,
    0
);
$optionalKeys = array_map(static function (array $field): string { return (string) ($field['field_key'] ?? ''); }, $optional['missing_fields']);
if (in_array('inspection_evidence', $optionalKeys, true) || $optional['ready'] !== true) {
    echo "CHAIN OPTIONAL EVIDENCE: FAIL\n";
    $failed++;
} else {
    echo "CHAIN OPTIONAL EVIDENCE: PASS\n";
}
exit($failed > 0 ? 1 : 0);
