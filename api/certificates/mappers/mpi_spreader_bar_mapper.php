<?php

declare(strict_types=1);

function mpi_spreader_bar_field_map(array $rows): array
{
    $map=[];
    foreach($rows as $row) $map[(string)($row['field_key']??'')]=trim((string)($row['value_text']??''));
    return $map;
}

function mpi_spreader_bar_list($value): array
{
    if(is_array($value)) $list=$value;
    else {
        $raw=trim((string)$value);
        $json=json_decode($raw,true);
        $list=is_array($json)?$json:preg_split('/\s*,\s*/',$raw,-1,PREG_SPLIT_NO_EMPTY);
    }
    return array_values(array_unique(array_map(static function($item):string{return strtolower(trim((string)$item));},$list?:[])));
}

function mpi_spreader_bar_bool($value): ?bool
{
    if($value===null||trim((string)$value)==='') return null;
    $v=strtolower(trim((string)$value));
    if(in_array($v,['1','yes','true','pass'],true)) return true;
    if(in_array($v,['0','no','false','fail'],true)) return false;
    return null;
}

function mpi_spreader_bar_blocker(string $type,string $key,string $label,string $section,string $reason,int $step=3):array
{
    return ['type'=>$type,'field_key'=>$key,'label'=>$label,'section'=>$section,'reason'=>$reason,'step'=>$step];
}

function mpi_spreader_bar_readiness(array $inspection,array $rows,string $number,string $issuedAt,string $expiry,array $attachments=[],bool $requireEvidence=false,int $minimumEvidenceFiles=0):array
{
    $fields=mpi_spreader_bar_field_map($rows);$missing=[];$errors=[];$blocking=[];
    foreach($rows as $row) if((int)($row['is_required']??0)===1&&trim((string)($row['value_text']??''))==='') {
        $missing[]=mpi_spreader_bar_blocker('field',(string)$row['field_key'],(string)$row['label'],'Inspection fields','No value has been saved.');
    }
    foreach([
        'certificate_number'=>['Report Number',$number,1],
        'inspection_date'=>['Date of Thorough Examination',(string)($inspection['inspection_date']??''),2],
        'next_due_date'=>['Next Thorough Examination Date',(string)($inspection['next_due_date']??$expiry),2],
        'client_name'=>['Client / Employer',(string)($inspection['client_name']??''),1],
    ] as $key=>$meta) if(trim($meta[1])==='') $missing[]=mpi_spreader_bar_blocker('field',$key,$meta[0],$meta[2]===1?'Records':'Details','No value has been saved.',$meta[2]);
    $exam=(string)($inspection['inspection_date']??'');
    if($exam!==''&&!valid_iso_date($exam)) $errors['inspection_date']='Date of Thorough Examination must be valid.';
    if(!valid_iso_date($expiry)||($exam!==''&&$expiry<=$exam)) $errors['next_due_date']='Next Thorough Examination Date must be after Date of Thorough Examination.';
    if(($fields['report_date']??'')!==''&&!valid_iso_date((string)$fields['report_date'])) $errors['report_date']='Date of Report must be valid.';
    $groups=[
        'magnetic_particle_equipment'=>['coil','prods','yoke','uv light'],
        'magnetic_particle_medium'=>['dry','visible','wet','fluorescent'],
        'magnetizing_current'=>['ac','hwdc','dc'],
        'magnetizing_process'=>['continuous','residual'],
    ];
    foreach($groups as $key=>$allowed) {
        $selected=mpi_spreader_bar_list($fields[$key]??'');
        if(!$selected) continue;
        foreach($selected as $choice) if(!in_array($choice,$allowed,true)) $errors[$key]='One or more selected values are invalid.';
    }
    if(array_key_exists('equipment_safe_for_use',$fields)&&$fields['equipment_safe_for_use']!==''&&mpi_spreader_bar_bool($fields['equipment_safe_for_use'])===null) $errors['equipment_safe_for_use']='Safe for Use must be Yes or No.';
    $evidence=0;foreach($attachments as $attachment) if((string)($attachment['attachment_type']??'evidence')!=='signature')$evidence++;
    $required=$requireEvidence?max(1,$minimumEvidenceFiles):0;
    if($evidence<$required)$missing[]=mpi_spreader_bar_blocker('evidence','inspection_evidence','Inspection Evidence Attachment','Evidence','Upload at least '.$required.' evidence file(s).',4);
    foreach($errors as $key=>$reason)$blocking[]=mpi_spreader_bar_blocker('validation',(string)$key,ucwords(str_replace('_',' ',(string)$key)),'Inspection fields',(string)$reason);
    $blocking=array_merge($missing,$blocking);
    return ['ready'=>!$missing&&!$errors,'missing_fields'=>$missing,'missing_sections'=>[],'validation_errors'=>$errors,'warnings'=>[],'blocking_items'=>$blocking,'inspection_id'=>(int)($inspection['id']??0)];
}

function mpi_spreader_bar_validate(array $inspection,array $rows,string $number,string $issuedAt,string $expiry,array $attachments=[],bool $requireEvidence=false,int $minimumEvidenceFiles=0):void
{
    $result=mpi_spreader_bar_readiness($inspection,$rows,$number,$issuedAt,$expiry,$attachments,$requireEvidence,$minimumEvidenceFiles);
    if(!$result['ready']) throw new CertificateIssueException('MPI / NDT Spreader Bar certificate cannot be issued. Complete the listed fields.',422,$result);
}

function mpi_spreader_bar_payload(array $inspection,array $rows,string $number,int $revision,string $verifyUrl,string $issuedAt,string $expiry):array
{
    global $config;$fields=mpi_spreader_bar_field_map($rows);
    return [
        'fields'=>$fields,'certificate_number'=>$number,'revision'=>$revision,'verification_url'=>$verifyUrl,
        'examination_date'=>(string)($inspection['inspection_date']??''),'report_date'=>($fields['report_date']??'')?:$issuedAt,
        'next_examination_date'=>(string)($inspection['next_due_date']??$expiry),
        'client_name'=>(string)($inspection['client_name']??''),'client_address'=>(string)($inspection['client_address']??''),
        'equipment_name'=>(string)($inspection['equipment_name']??''),'asset_code'=>(string)($inspection['asset_code']??''),
        'inspector_name'=>($fields['inspector_name_snapshot']??'')?:(string)($inspection['inspector_name']??''),
        'inspector_qualification'=>($fields['inspector_qualification_snapshot']??'')?:(string)($inspection['inspector_qualification']??''),
        'authenticator_name'=>(string)($fields['authenticator_name']??''),'authenticator_qualification'=>(string)($fields['authenticator_qualification']??''),
        'company'=>[
            'name'=>(string)($config['company_name']??'JUVA-OIL SERVICES (NIG) LIMITED'),
            'registered'=>(string)($config['company_address']??'52 Rumuolumeni Road, Port Harcourt'),
            'operational'=>(string)($config['operational_yard']??'127 Trans Amadi, Port Harcourt'),
            'phone'=>(string)($config['company_phone']??'+234 806 516 4945'),
            'email'=>(string)($config['company_email']??'juvaoil@gmail.com'),
            'website'=>(string)($config['company_website']??'www.juvaoil.com'),
        ],
    ];
}

function mpi_spreader_bar_date(string $value):string
{
    $time=strtotime($value);return $time?date('d/m/Y',$time):$value;
}
