<?php

declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'CLI';
define('JUVA_INSPECTION_VALIDATION_ONLY', true);
require_once __DIR__ . '/../api/inspections/index.php';

$fields = [
    [
        'id' => 1,
        'label' => 'Magnetic Particle Equipment',
        'field_type' => 'checkbox',
        'is_required' => 1,
        'options_json' => json_encode(['Coil', 'Prods', 'Yoke', 'UV Light']),
        'validation_json' => null,
    ],
    [
        'id' => 2,
        'label' => 'Safe for Use',
        'field_type' => 'checkbox',
        'is_required' => 1,
        'options_json' => null,
        'validation_json' => null,
    ],
];

[$validErrors, $validValues] = validate_inspection_values([
    1 => 'Coil, Prods, Yoke, UV Light',
    2 => 'Yes',
], $fields);

if ($validErrors || ($validValues[1] ?? '') !== 'Coil, Prods, Yoke, UV Light' || ($validValues[2] ?? '') !== 'Yes') {
    fwrite(STDERR, "MULTISELECT CHECKBOX VALIDATION: FAIL\n");
    exit(1);
}

[$invalidErrors] = validate_inspection_values([
    1 => 'Coil, Browser Script',
    2 => 'No',
], $fields);

if (!isset($invalidErrors['field_1'])) {
    fwrite(STDERR, "MULTISELECT CHECKBOX ALLOW-LIST: FAIL\n");
    exit(1);
}

echo "MULTISELECT CHECKBOX VALIDATION: PASS\n";
echo "MULTISELECT CHECKBOX ALLOW-LIST: PASS\n";
echo "BINARY CHECKBOX REGRESSION: PASS\n";
