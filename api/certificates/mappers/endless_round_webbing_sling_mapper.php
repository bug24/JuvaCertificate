<?php

declare(strict_types=1);

function endless_round_webbing_sling_field_map(array $rows): array
{
    $map = [];
    foreach ($rows as $row) {
        $key = (string) ($row['field_key'] ?? '');
        $raw = $row['value_text'] ?? null;
        $booleanKeys = ['first_examination_after_installation','installed_correctly','examined_within_six_months','examined_within_twelve_months','examined_under_scheme','examined_after_exceptional_circumstances','defect_immediate_danger','defect_future_danger','fit_for_purpose'];
        $map[$key] = in_array($key, $booleanKeys, true) ? (endless_round_webbing_sling_boolean($raw) ?? trim((string) $raw)) : trim((string) $raw);
    }
    return $map;
}

function endless_round_webbing_sling_boolean($value): ?string
{
    if ($value === null || $value === '') return null;
    if ($value === true || $value === 1 || $value === '1') return 'Yes';
    if ($value === false || $value === 0 || $value === '0') return 'No';
    $normalized = strtolower(trim((string) $value));
    if ($normalized === 'yes' || $normalized === 'true') return 'Yes';
    if ($normalized === 'no' || $normalized === 'false') return 'No';
    return null;
}

function endless_round_webbing_sling_blocker(string $type, string $key, string $label, string $section, string $reason, int $step = 3): array
{
    return ['type' => $type, 'key' => $key, 'field_key' => $key, 'label' => $label, 'section' => $section, 'step' => $step, 'reason' => $reason];
}
function endless_round_webbing_sling_readiness(array $inspection, array $rows, string $number, string $issuedAt, string $expiry, array $attachments = [], bool $requireEvidence = false, int $minimumEvidenceFiles = 0): array
{
    $fields = endless_round_webbing_sling_field_map($rows);
    $fieldLabels = [];
    foreach ($rows as $row) {
        $fieldKey = (string) ($row['field_key'] ?? '');
        $fieldLabel = (string) ($row['label'] ?? '');
        if ($fieldKey !== '' && $fieldLabel !== '') {
            $fieldLabels[$fieldKey] = $fieldLabel;
        }
    }
    $missing = [];
    $errors = [];
    foreach ($rows as $row) {
        if ((int) ($row['is_required'] ?? 0) === 1 && (($row['value_text'] ?? null) === null || (is_string($row['value_text']) && trim($row['value_text']) === ''))) {
            $missing[] = endless_round_webbing_sling_blocker('field', (string) $row['field_key'], (string) $row['label'], 'Inspection fields', 'No value has been saved.');
        }
    }
    foreach ([
        'certificate_number' => ['Certificate Number', $number],
        'inspection_date' => ['Date of Thorough Examination', (string) ($inspection['inspection_date'] ?? '')],
        'next_due_date' => ['Next Thorough Examination Date', (string) ($inspection['next_due_date'] ?? '')],
    ] as $key => $item) {
        if (trim($item[1]) === '') {
            $missing[] = endless_round_webbing_sling_blocker('field', $key, $item[0], $key === 'certificate_number' ? 'Records' : 'Details', 'No value has been saved.', $key === 'certificate_number' ? 1 : 2);
        }
    }
    $exam = (string) ($inspection['inspection_date'] ?? '');
    if ($exam !== '' && !valid_iso_date($exam)) {
        $errors['inspection_date'] = 'Date of Thorough Examination must be valid.';
    }
    if (!valid_iso_date($expiry) || ($exam !== '' && $expiry <= $exam)) {
        $errors['next_due_date'] = 'Next Thorough Examination Date must be after Date of Thorough Examination.';
    }
    foreach (['first_examination_after_installation','installed_correctly','examined_within_six_months','examined_within_twelve_months','examined_under_scheme','examined_after_exceptional_circumstances','defect_immediate_danger','defect_future_danger','fit_for_purpose'] as $key) {
        if (isset($fields[$key]) && $fields[$key] !== '' && !in_array(strtolower($fields[$key]), ['yes','no'], true)) {
            $errors[$key] = ucwords(str_replace('_', ' ', $key)) . ' must be Yes or No.';
        }
    }
    foreach (['report_date','date_of_manufacture','date_of_last_examination','action_due_date'] as $dateKey) {
        if (($fields[$dateKey] ?? '') !== '' && !valid_iso_date((string) $fields[$dateKey])) {
            $errors[$dateKey] = ucwords(str_replace('_', ' ', $dateKey)) . ' must be a valid date.';
        }
    }
    if (($fields['report_date'] ?? '') !== '' && $exam !== '' && (string) $fields['report_date'] < $exam) {
        $errors['report_date'] = 'Date of Report cannot be earlier than Date of Thorough Examination.';
    }    $defects = strtoupper(trim((string) ($fields['defect_description'] ?? '')));
    if ($defects !== '' && !in_array($defects, ['NONE','NIL','N/A'], true)) {
        if (($fields['defect_immediate_danger'] ?? '') === '' || ($fields['defect_future_danger'] ?? '') === '') {
            $errors['defect_status'] = 'Select both defect danger answers when defects are recorded.';
        }
    }
    if (endless_round_webbing_sling_boolean($fields['defect_future_danger'] ?? null) === 'Yes') {
        if (empty($fields['action_due_date']) || !valid_iso_date((string) $fields['action_due_date'])) {
            $errors['action_due_date'] = 'Corrective Action Date is required when future danger is Yes.';
        }
    }    $evidenceCount = 0;
    foreach ($attachments as $attachment) {
        if ((string) ($attachment['attachment_type'] ?? 'evidence') === 'evidence') { $evidenceCount++; }
    }
    $requiredEvidenceCount = $requireEvidence ? max(1, $minimumEvidenceFiles) : 0;
    if ($evidenceCount < $requiredEvidenceCount) {
        $missing[] = endless_round_webbing_sling_blocker('evidence', 'inspection_evidence', 'Inspection Evidence Attachment', 'Evidence', 'Upload at least ' . $requiredEvidenceCount . ' evidence file(s).', 4);
    }
    $blocking = $missing;
    foreach ($errors as $key => $reason) {
        $detailsStep = in_array($key, ['inspection_date','next_due_date'], true);
        $blocking[] = endless_round_webbing_sling_blocker('validation', (string) $key, $fieldLabels[(string) $key] ?? ucwords(str_replace('_', ' ', (string) $key)), $detailsStep ? 'Details' : 'Inspection fields', (string) $reason, $detailsStep ? 2 : 3);
    }
    return ['ready' => !$blocking, 'missing_fields' => $missing, 'missing_sections' => [], 'validation_errors' => $errors, 'blocking_items' => $blocking, 'warnings' => [], 'inspection_id' => (int) ($inspection['id'] ?? 0)];
}

function endless_round_webbing_sling_validate(array $inspection, array $rows, string $number, string $issuedAt, string $expiry, array $attachments = [], bool $requireEvidence = false, int $minimumEvidenceFiles = 0): void
{
    $result = endless_round_webbing_sling_readiness($inspection, $rows, $number, $issuedAt, $expiry, $attachments, $requireEvidence, $minimumEvidenceFiles);
    if ($result['ready']) {
        return;
    }
    $labels = array_map(static function (array $field): string { return (string) $field['label']; }, $result['missing_fields']);
    $message = $labels ? "Endless Round Webbing Sling certificate cannot be issued. Complete:\n- " . implode("\n- ", $labels) : 'Endless Round Webbing Sling certificate cannot be issued. Correct the validation errors shown.';
    throw new CertificateIssueException($message, 422, $result);
}

function endless_round_webbing_sling_payload(array $inspection, array $rows, string $number, int $revision, string $verifyUrl, string $issuedAt, string $expiry): array
{
    global $config;
    $fields = endless_round_webbing_sling_field_map($rows);
    return [
        'fields' => $fields,
        'certificate_number' => $number,
        'revision' => $revision,
        'verification_url' => $verifyUrl,
        'examination_date' => (string) ($inspection['inspection_date'] ?? ''),
        'report_date' => ($fields['report_date'] ?? '') ?: (string) ($inspection['inspection_date'] ?? $issuedAt),
        'next_examination_date' => (string) ($inspection['next_due_date'] ?? $expiry),
        'client_name' => (string) ($inspection['client_name'] ?? ''),
        'client_address' => (string) ($inspection['client_address'] ?? ''),
        'equipment_name' => (string) ($inspection['equipment_name'] ?? 'ENDLESS ROUND WEBBING SLING'),
        'asset_code' => (string) ($inspection['asset_code'] ?? ''),
        'inspector_name' => ($fields['inspector_name_snapshot'] ?? '') ?: (string) ($inspection['inspector_name'] ?? ''),
        'inspector_qualification' => ($fields['inspector_qualification_snapshot'] ?? '') ?: (string) ($inspection['inspector_qualification'] ?? ''),
        'company' => [
            'name' => (string) $config['company_name'],
            'registered' => (string) $config['company_registered_address'],
            'operational' => (string) $config['company_operational_address'],
            'phone' => (string) $config['company_phone'],
            'email' => (string) $config['company_email'],
            'website' => (string) $config['company_website'],
        ],
    ];
}

function endless_round_webbing_sling_date(string $value): string
{
    $time = strtotime($value);
    return $time === false ? $value : date('d/m/Y', $time);
}

