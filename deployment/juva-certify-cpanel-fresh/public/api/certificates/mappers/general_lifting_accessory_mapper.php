<?php

declare(strict_types=1);

require_once __DIR__ . '/eye_bolt_mapper.php';

function general_lifting_accessory_field_map(array $rows): array
{
    $map = [];
    foreach ($rows as $row) {
        $map[(string) ($row['field_key'] ?? '')] = trim((string) ($row['value_text'] ?? ''));
    }
    return $map;
}

function general_lifting_accessory_item_rows(array $items): array
{
    $acceptedSections = ['eye_bolt_items', 'general_lifting_accessory_items', 'accessory_items'];
    $grouped = [];
    foreach ($items as $cell) {
        $section = (string) ($cell['section_key'] ?? '');
        if (!in_array($section, $acceptedSections, true)) {
            continue;
        }
        $index = (int) ($cell['row_index'] ?? 0);
        $grouped[$index][(string) ($cell['column_key'] ?? '')] = trim((string) ($cell['value_text'] ?? ''));
    }
    ksort($grouped);
    return array_values(array_filter($grouped, static function (array $row): bool {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }
        return false;
    }));
}

function general_lifting_accessory_blocker(string $type, string $key, string $label, string $section, string $reason, int $step = 3): array
{
    return ['type' => $type, 'key' => $key, 'field_key' => $key, 'label' => $label, 'section' => $section, 'step' => $step, 'reason' => $reason];
}

function general_lifting_accessory_readiness(array $inspection, array $rows, array $items, string $number, string $issuedAt, string $expiry): array
{
    $fields = general_lifting_accessory_field_map($rows);
    $itemRows = general_lifting_accessory_item_rows($items);
    $missing = [];
    $errors = [];

    foreach ($rows as $row) {
        if ((int) ($row['is_required'] ?? 0) === 1 && trim((string) ($row['value_text'] ?? '')) === '') {
            $missing[] = general_lifting_accessory_blocker('field', (string) $row['field_key'], (string) $row['label'], 'Inspection fields', 'No value has been saved.');
        }
    }

    $core = [
        'certificate_number' => ['Certificate Number', $number, 1],
        'inspection_date' => ['Date of Thorough Examination', (string) ($inspection['inspection_date'] ?? ''), 2],
        'next_due_date' => ['Next Thorough Examination Date', (string) ($inspection['next_due_date'] ?? ''), 2],
        'inspection_location' => ['Address of Premises', (string) ($inspection['location'] ?? ''), 2],
    ];
    foreach ($core as $key => $meta) {
        if (trim((string) $meta[1]) === '') {
            $missing[] = general_lifting_accessory_blocker('field', $key, $meta[0], $meta[2] === 1 ? 'Records' : 'Details', 'No value has been saved.', (int) $meta[2]);
        }
    }

    $exam = (string) ($inspection['inspection_date'] ?? '');
    if ($exam !== '' && !valid_iso_date($exam)) {
        $errors['inspection_date'] = 'Date of Thorough Examination must be valid.';
    }
    if (!valid_iso_date($expiry) || ($exam !== '' && $expiry <= $exam)) {
        $errors['next_due_date'] = 'Next Thorough Examination Date must be after Date of Thorough Examination.';
    }
    if (!in_array(strtolower((string) ($fields['defect_observation_sheet_attached'] ?? '')), ['yes', 'no'], true)) {
        $errors['defect_observation_sheet_attached'] = 'Defect / Observation Sheet Attached must be Yes or No.';
    }

    if (!$itemRows) {
        $missing[] = general_lifting_accessory_blocker('section', 'general_lifting_accessory_items', 'General Lifting Accessory Register', 'Inspection fields', 'Add at least one accessory row.');
    }
    if (count($itemRows) > 6) {
        $errors['general_lifting_accessory_items'] = 'A one-page General Lifting Accessories certificate supports a maximum of six rows.';
    }

    $required = [
        'identification_number' => 'Identification Number',
        'description' => 'Equipment / Accessory Type and Description',
        'working_load_limit' => 'WLL or SWL',
        'manufacturer' => 'Manufacturer',
        'next_thorough_examination_date' => 'Next Thorough Examination',
        'reason_for_examination_code' => 'Reason Code',
        'test_details' => 'Details of Test',
        'status_code' => 'Status',
        'safe_to_use' => 'Safe to Use',
    ];
    foreach ($itemRows as $index => $item) {
        foreach ($required as $key => $label) {
            if (trim((string) ($item[$key] ?? '')) === '') {
                $missing[] = general_lifting_accessory_blocker('item', 'general_lifting_accessory_items.' . ($index + 1) . '.' . $key, 'Row ' . ($index + 1) . ' - ' . $label, 'General Lifting Accessory Register', 'No value has been saved.');
            }
        }
        if (($item['last_thorough_examination_date'] ?? '') !== '' && !valid_partial_date((string) $item['last_thorough_examination_date'])) {
            $errors['general_lifting_accessory_items.' . ($index + 1) . '.last_thorough_examination_date'] = 'Row ' . ($index + 1) . ' has an invalid historical examination date.';
        }
        if (($item['next_thorough_examination_date'] ?? '') !== '' && !valid_iso_date((string) $item['next_thorough_examination_date'])) {
            $errors['general_lifting_accessory_items.' . ($index + 1) . '.next_thorough_examination_date'] = 'Row ' . ($index + 1) . ' next thorough examination must be a complete valid date.';
        }
        if (!in_array(strtoupper((string) ($item['reason_for_examination_code'] ?? '')), ['A', 'B', 'C', 'D', 'E'], true)) {
            $errors['general_lifting_accessory_items.' . ($index + 1) . '.reason_for_examination_code'] = 'Row ' . ($index + 1) . ' reason code must be A, B, C, D or E.';
        }
        if (!in_array(strtoupper((string) ($item['status_code'] ?? '')), ['ND', 'SDR', 'NF', 'OBS'], true)) {
            $errors['general_lifting_accessory_items.' . ($index + 1) . '.status_code'] = 'Row ' . ($index + 1) . ' status must be ND, SDR, NF or OBS.';
        }
        if (!in_array(strtolower((string) ($item['safe_to_use'] ?? '')), ['yes', 'no'], true)) {
            $errors['general_lifting_accessory_items.' . ($index + 1) . '.safe_to_use'] = 'Row ' . ($index + 1) . ' Safe to Use must be Yes or No.';
        }
    }

    $blocking = $missing;
    foreach ($errors as $key => $reason) {
        $blocking[] = general_lifting_accessory_blocker('validation', (string) $key, ucwords(str_replace(['.', '_'], [' - ', ' '], (string) $key)), 'Inspection fields', (string) $reason);
    }

    return [
        'ready' => empty($blocking),
        'missing_fields' => $missing,
        'missing_sections' => [],
        'validation_errors' => $errors,
        'blocking_items' => $blocking,
        'warnings' => [],
        'inspection_id' => (int) ($inspection['id'] ?? 0),
    ];
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
    $payload = eye_bolt_payload($inspection, $rows, $items, $number, $revision, $verifyUrl, $issuedAt, $expiry);
    $payload['fields'] = general_lifting_accessory_field_map($rows);
    $payload['items'] = general_lifting_accessory_item_rows($items);
    return $payload;
}
