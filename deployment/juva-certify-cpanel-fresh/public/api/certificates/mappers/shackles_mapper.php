<?php

declare(strict_types=1);

function shackles_field_map(array $rows): array
{
    $map = [];
    foreach ($rows as $row) {
        $map[(string) ($row['field_key'] ?? '')] = trim((string) ($row['value_text'] ?? ''));
    }
    return $map;
}

function shackles_item_rows(array $items): array
{
    $grouped = [];
    foreach ($items as $cell) {
        if ((string) ($cell['section_key'] ?? '') !== 'shackles_items') continue;
        $index = (int) ($cell['row_index'] ?? 0);
        $grouped[$index][(string) ($cell['column_key'] ?? '')] = trim((string) ($cell['value_text'] ?? ''));
    }
    ksort($grouped);
    return array_values(array_filter($grouped, static function (array $row): bool {
        foreach ($row as $value) if (trim((string) $value) !== '') return true;
        return false;
    }));
}

function shackles_resolved_items(array $inspection, array $items, array $fields): array
{
    $rows = shackles_item_rows($items);
    foreach ($rows as $index => &$row) {
        if ($index === 0) {
            $row['identification_number'] = trim((string) ($inspection['equipment_serial_number'] ?? $inspection['asset_code'] ?? ''));
            $row['description'] = trim((string) ($inspection['equipment_name'] ?? ''));
            $row['swl_wll'] = trim((string) ($inspection['equipment_safe_working_load'] ?? ''));
            $row['manufacturer'] = trim((string) ($inspection['equipment_manufacturer'] ?? ''));
            $previous = trim((string) ($inspection['previous_examination_date'] ?? ''));
            if ($previous !== '') {
                $row['date_last_examined'] = $previous;
                $row['last_thorough_examination_date'] = $previous;
            }
        }
        $row['reason_for_examination_code'] = trim((string) ($fields['reason_for_examination_code'] ?? $row['reason_for_examination_code'] ?? ''));
        $row['next_thorough_examination_date'] = trim((string) ($inspection['next_due_date'] ?? $row['next_thorough_examination_date'] ?? ''));
    }
    unset($row);
    return $rows;
}

function shackles_blocker(string $type, string $key, string $label, string $section, string $reason, int $step = 3): array
{
    return ['type'=>$type,'key'=>$key,'field_key'=>$key,'label'=>$label,'section'=>$section,'step'=>$step,'reason'=>$reason];
}

function shackles_readiness(array $inspection, array $rows, array $items, string $number, string $issuedAt, string $expiry): array
{
    $fields = shackles_field_map($rows);
    $itemRows = shackles_resolved_items($inspection, $items, $fields);
    $missing = []; $errors = [];
    foreach ($rows as $row) {
        if ((int) ($row['is_required'] ?? 0) === 1 && trim((string) ($row['value_text'] ?? '')) === '') {
            $missing[] = shackles_blocker('field',(string)$row['field_key'],(string)$row['label'],'Inspection fields','No value has been saved.');
        }
    }
    foreach (['certificate_number'=>['Certificate Number',$number,1],'inspection_date'=>['Date of Thorough Examination',(string)($inspection['inspection_date']??''),2],'next_due_date'=>['Next Thorough Examination Date',(string)($inspection['next_due_date']??''),2],'inspection_location'=>['Address of Premises',(string)($inspection['location']??''),2]] as $key=>$meta) {
        if (trim($meta[1]) === '') $missing[] = shackles_blocker('field',$key,$meta[0],$meta[2]===1?'Records':'Details','No value has been saved.',$meta[2]);
    }
    foreach ([
        'reason_for_examination_code'=>'Reason for Examination',
        'colour_code'=>'Colour Code',
    ] as $key=>$label) {
        if (trim((string)($fields[$key]??'')) === '') $missing[] = shackles_blocker('field',$key,$label,'Inspection fields','No value has been saved.');
    }
    $standard = trim((string) (($inspection['equipment_standard'] ?? '') ?: ($fields['standard'] ?? '')));
    if ($standard === '') $missing[] = shackles_blocker('field','standard','Standard','Equipment Register','Set the equipment reference standard.');
    $exam = (string) ($inspection['inspection_date'] ?? '');
    if ($exam !== '' && !valid_iso_date($exam)) $errors['inspection_date'] = 'Date of Thorough Examination must be a complete valid date.';
    if (!valid_iso_date($expiry) || ($exam !== '' && $expiry <= $exam)) $errors['next_due_date'] = 'Next Thorough Examination Date must be a complete date after Date of Thorough Examination.';
    if (!in_array(strtolower((string)($fields['defect_observation_sheet_attached']??'')), ['yes','no'], true)) $errors['defect_observation_sheet_attached'] = 'Defect / Observation Sheet Attached must be Yes or No.';
    if (!$itemRows) $missing[] = shackles_blocker('section','shackles_items','Shackles Examination Register','Inspection fields','Add at least one shackle row.');
    if (count($itemRows) > 6) $errors['shackles_items'] = 'A one-page Shackles certificate supports a maximum of six rows.';
    $required = ['identification_number'=>'Identification Number','description'=>'Description','swl_wll'=>'WLL / SWL','manufacturer'=>'Manufacturer','test_details'=>'Details of Test','status'=>'Status','safe_to_use'=>'Safe to Use'];
    foreach ($itemRows as $index=>$item) {
        foreach ($required as $key=>$label) if (trim((string)($item[$key]??''))==='') $missing[] = shackles_blocker('item','shackles_items.'.($index+1).'.'.$key,'Row '.($index+1).' - '.$label,'Shackles Examination Register','No value has been saved.');
        $last = trim((string)($item['date_last_examined']??''));
        if ($last !== '' && !valid_partial_date($last)) $errors['shackles_items.'.($index+1).'.date_last_examined'] = 'Last examination date must be a valid full date, month and year, or year.';
    }
    $reason = strtoupper(trim((string)($fields['reason_for_examination_code']??'')));
    if ($reason !== '' && !in_array($reason,['A','B','C','D','E'],true)) $errors['reason_for_examination_code'] = 'Select reason code A, B, C, D or E.';
    $blocking = $missing;
    foreach ($errors as $key=>$reasonText) $blocking[] = shackles_blocker('validation',(string)$key,ucwords(str_replace(['.','_'],[' - ',' '],(string)$key)),'Inspection fields',(string)$reasonText);
    return ['ready'=>!$blocking,'missing_fields'=>$missing,'missing_sections'=>[],'validation_errors'=>$errors,'blocking_items'=>$blocking,'warnings'=>[],'inspection_id'=>(int)($inspection['id']??0)];
}

function shackles_validate(array $inspection, array $rows, array $items, string $number, string $issuedAt, string $expiry): void
{
    $result = shackles_readiness($inspection,$rows,$items,$number,$issuedAt,$expiry);
    if (!$result['ready']) throw new CertificateIssueException('Shackles certificate cannot be issued. Complete the listed fields.',422,$result);
}

function shackles_payload(array $inspection, array $rows, array $items, string $number, int $revision, string $verifyUrl, string $issuedAt, string $expiry): array
{
    global $config;
    $fields = shackles_field_map($rows);
    $fields['standard'] = trim((string)(($inspection['equipment_standard'] ?? '') ?: ($fields['standard'] ?? '')));
    $fields['date_of_manufacture'] = trim((string)(($inspection['equipment_manufacture_date_value'] ?? '') ?: ($inspection['equipment_manufacture_date'] ?? '') ?: ($fields['date_of_manufacture'] ?? '')));
    $fields['date_of_previous_examination'] = trim((string)(($inspection['previous_examination_date'] ?? '') ?: ($fields['date_of_previous_examination'] ?? '')));
    return [
        'fields'=>$fields,'items'=>shackles_resolved_items($inspection,$items,$fields),'certificate_number'=>$number,'revision'=>$revision,
        'verification_url'=>$verifyUrl,'examination_date'=>(string)($inspection['inspection_date']??''),'issue_date'=>$issuedAt,'expiry_date'=>$expiry,
        'client_name'=>(string)($inspection['client_name']??''),'client_address'=>(string)($inspection['client_address']??''),'inspection_location'=>(string)($inspection['location']??''),
        'inspector_name'=>($fields['inspector_name_snapshot']??'') ?: (string)($inspection['inspector_name']??''),
        'inspector_qualification'=>($fields['inspector_qualification_snapshot']??'') ?: (string)($inspection['inspector_qualification']??''),
        'authenticator_name'=>(string)($fields['authenticator_name']??''),'authenticator_qualification'=>(string)($fields['authenticator_qualification']??''),
        'company'=>['name'=>(string)$config['company_name'],'registered'=>(string)$config['company_registered_address'],'operational'=>(string)$config['company_operational_address'],'phone'=>(string)$config['company_phone'],'email'=>(string)$config['company_email'],'website'=>(string)$config['company_website']],
    ];
}

function shackles_date(string $value): string
{
    return format_juva_partial_date($value);
}
