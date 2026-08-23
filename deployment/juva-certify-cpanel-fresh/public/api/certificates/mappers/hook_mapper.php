<?php

declare(strict_types=1);

function hook_field_map(array $rows): array
{
    $map = [];
    foreach ($rows as $row) $map[(string) ($row['field_key'] ?? '')] = trim((string) ($row['value_text'] ?? ''));
    return $map;
}

function hook_item_rows(array $items): array
{
    $grouped = [];
    foreach ($items as $cell) {
        if ((string) ($cell['section_key'] ?? '') !== 'hook_items') continue;
        $index = (int) ($cell['row_index'] ?? 0);
        $grouped[$index][(string) ($cell['column_key'] ?? '')] = trim((string) ($cell['value_text'] ?? ''));
    }
    ksort($grouped);
    return array_values(array_filter($grouped, static function (array $row): bool {
        foreach ($row as $value) if (trim((string) $value) !== '') return true;
        return false;
    }));
}

function hook_blocker(string $type, string $key, string $label, string $section, string $reason, int $step = 3): array
{
    return ['type'=>$type,'key'=>$key,'field_key'=>$key,'label'=>$label,'section'=>$section,'step'=>$step,'reason'=>$reason];
}

function hook_readiness(array $inspection, array $rows, array $items, string $number, string $issuedAt, string $expiry): array
{
    $fields = hook_field_map($rows);
    $itemRows = hook_item_rows($items);
    $missing = []; $errors = [];
    foreach ($rows as $row) {
        if ((int) ($row['is_required'] ?? 0) === 1 && trim((string) ($row['value_text'] ?? '')) === '') {
            $missing[] = hook_blocker('field',(string)$row['field_key'],(string)$row['label'],'Inspection fields','No value has been saved.');
        }
    }
    foreach (['certificate_number'=>['Certificate Number',$number,1],'inspection_date'=>['Date of Thorough Examination',(string)($inspection['inspection_date']??''),2],'next_due_date'=>['Next Thorough Examination Date',(string)($inspection['next_due_date']??''),2],'inspection_location'=>['Address of Premises',(string)($inspection['location']??''),2]] as $key=>$meta) {
        if (trim($meta[1]) === '') $missing[] = hook_blocker('field',$key,$meta[0],$meta[2]===1?'Records':'Details','No value has been saved.',$meta[2]);
    }
    $exam = (string) ($inspection['inspection_date'] ?? '');
    if ($exam !== '' && !valid_iso_date($exam)) $errors['inspection_date'] = 'Date of Thorough Examination must be valid.';
    if (!valid_iso_date($expiry) || ($exam !== '' && $expiry <= $exam)) $errors['next_due_date'] = 'Next Thorough Examination Date must be after Date of Thorough Examination.';
    if (!in_array(strtolower((string)($fields['defect_observation_sheet_attached']??'')), ['yes','no'], true)) $errors['defect_observation_sheet_attached'] = 'Defect / Observation Sheet Attached must be Yes or No.';
    $reportDate = trim((string)($fields['report_date'] ?? ''));
    if ($reportDate !== '' && !valid_iso_date($reportDate)) $errors['report_date'] = 'Date of Report must be a valid date.';
    if (!$itemRows) $missing[] = hook_blocker('section','hook_items','Hook Examination Register','Inspection fields','Add at least one hook row.');
    if (count($itemRows) > 6) $errors['hook_items'] = 'A one-page Hook certificate supports a maximum of six rows.';
    $required = ['identification_number'=>'Identification Number','description'=>'Description','working_load_limit'=>'WLL or SWL','manufacturer'=>'Manufacturer','next_thorough_examination_date'=>'Next Thorough Examination','reason_for_examination_code'=>'Reason Code','test_details'=>'Test Details','status_code'=>'Status','safe_to_use'=>'Safe to Use'];
    foreach ($itemRows as $index=>$item) {
        foreach ($required as $key=>$label) if (trim((string)($item[$key]??''))==='') $missing[] = hook_blocker('item','hook_items.'.($index+1).'.'.$key,'Row '.($index+1).' - '.$label,'Hook Examination Register','No value has been saved.');
        if (($item['last_thorough_examination_date']??'')!=='' && !valid_partial_date((string)$item['last_thorough_examination_date'])) $errors['hook_items.'.($index+1).'.last_thorough_examination_date'] = 'Row '.($index+1).' has an invalid historical examination date.';
        if (($item['next_thorough_examination_date']??'')!=='' && !valid_iso_date((string)$item['next_thorough_examination_date'])) $errors['hook_items.'.($index+1).'.next_thorough_examination_date'] = 'Row '.($index+1).' next thorough examination must be a complete valid date.';
        if (!in_array(strtoupper((string)($item['reason_for_examination_code']??'')),['A','B','C','D','E'],true)) $errors['hook_items.'.($index+1).'.reason_for_examination_code'] = 'Row '.($index+1).' reason code must be A, B, C, D or E.';
        if (!in_array(strtoupper((string)($item['status_code']??'')),['ND','SDR','NF','OBS'],true)) $errors['hook_items.'.($index+1).'.status_code'] = 'Row '.($index+1).' status must be ND, SDR, NF or OBS.';
        if (!in_array(strtolower((string)($item['safe_to_use']??'')),['yes','no'],true)) $errors['hook_items.'.($index+1).'.safe_to_use'] = 'Row '.($index+1).' Safe to Use must be Yes or No.';
    }
    $blocking = $missing;
    foreach ($errors as $key=>$reason) $blocking[] = hook_blocker('validation',(string)$key,ucwords(str_replace(['.','_'],[' - ',' '],(string)$key)),'Inspection fields',(string)$reason);
    return ['ready'=>!$blocking,'missing_fields'=>$missing,'missing_sections'=>[],'validation_errors'=>$errors,'blocking_items'=>$blocking,'warnings'=>[],'inspection_id'=>(int)($inspection['id']??0)];
}

function hook_validate(array $inspection, array $rows, array $items, string $number, string $issuedAt, string $expiry): void
{
    $result = hook_readiness($inspection,$rows,$items,$number,$issuedAt,$expiry);
    if (!$result['ready']) throw new CertificateIssueException('Hook certificate cannot be issued. Complete the listed fields.',422,$result);
}

function hook_payload(array $inspection, array $rows, array $items, string $number, int $revision, string $verifyUrl, string $issuedAt, string $expiry): array
{
    global $config;
    $fields = hook_field_map($rows);
    return [
        'fields'=>$fields,'items'=>hook_item_rows($items),'certificate_number'=>$number,'revision'=>$revision,
        'verification_url'=>$verifyUrl,'examination_date'=>(string)($inspection['inspection_date']??''),'report_date'=>($fields['report_date']??'') ?: (string)($inspection['inspection_date']??$issuedAt),'issue_date'=>$issuedAt,'expiry_date'=>$expiry,
        'client_name'=>(string)($inspection['client_name']??''),'client_address'=>(string)($inspection['client_address']??''),'inspection_location'=>(string)($inspection['location']??''),
        'inspector_name'=>($fields['inspector_name_snapshot']??'') ?: (string)($inspection['inspector_name']??''),
        'inspector_qualification'=>($fields['inspector_qualification_snapshot']??'') ?: (string)($inspection['inspector_qualification']??''),
        'authenticator_name'=>(string)($fields['authenticator_name']??''),'authenticator_qualification'=>(string)($fields['authenticator_qualification']??''),
        'company'=>['name'=>(string)$config['company_name'],'registered'=>(string)$config['company_registered_address'],'operational'=>(string)$config['company_operational_address'],'phone'=>(string)$config['company_phone'],'email'=>(string)$config['company_email'],'website'=>(string)$config['company_website']],
    ];
}

function hook_date(string $value): string
{
    return format_juva_partial_date($value);
}
