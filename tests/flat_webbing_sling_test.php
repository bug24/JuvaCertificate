<?php
$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once __DIR__ . '/../api/lib/certificate_service.php';

$inspection = [
    'id'=>999001,'inspection_date'=>'2026-07-14','next_due_date'=>'2027-01-14',
    'client_name'=>'Riverside Fabrication Services','client_address'=>'Industrial Layout, Port Harcourt','location'=>'Riverside Operational Yard, Port Harcourt',
    'equipment_name'=>'Round Sling RS-42','asset_code'=>'RFS-RS-042',
    'inspector_name'=>'Qualified Inspection Officer','inspector_qualification'=>'LEEA, ASNT II',
];
$values = [
    'report_date'=>'2026-07-14','premises_address'=>'Riverside Operational Yard, Port Harcourt',
    'equipment_type'=>'Flat Webbing Sling','sling_length'=>'4 m','equipment_description'=>'Polyester endless round sling with protective cover',
    'manufacturer'=>'West African Lifting Products','equipment_id_number'=>'RFS-RS-042','asset_number'=>'ASSET-042',
    'standard'=>'BS EN 1492-2','safe_working_load'=>'5 tonnes','date_of_manufacture'=>'2024-03-10','last_thorough_examination_date'=>'2026-01-12',
    'first_examination_after_installation'=>'No','installed_correctly'=>'Yes','examined_within_six_months'=>'Yes',
    'examined_within_twelve_months'=>'No','examined_under_scheme'=>'Yes','examined_after_exceptional_circumstances'=>'No',
    'defect_description'=>'NONE','defect_immediate_danger'=>'No','defect_future_danger'=>'No','action_due_date'=>'',
    'repair_required'=>'NONE','tests_carried_out'=>'Visual and thorough examination','observations'=>'No adverse condition observed',
    'fit_for_purpose'=>'Yes','inspector_name_snapshot'=>'Qualified Inspection Officer','inspector_qualification_snapshot'=>'LEEA, ASNT II',
    'authenticator_name'=>'Certification Reviewer','authenticator_qualification'=>'LEEA LMM',
];
$required = ['equipment_type','sling_length','sling_width','equipment_description','manufacturer','identification_number','standard','safe_working_load','first_examination_after_installation','installed_correctly','examined_within_six_months','examined_within_twelve_months','examined_under_scheme','examined_after_exceptional_circumstances','defect_description','repair_required','tests_carried_out','fit_for_purpose'];
$rows = [];
foreach ($values as $key=>$value) $rows[] = ['field_key'=>$key,'label'=>ucwords(str_replace('_',' ',$key)),'is_required'=>in_array($key,$required,true)?1:0,'pdf_section'=>'test','value_text'=>$value];

$failed = 0;
$ready = flat_webbing_sling_readiness($inspection,$rows,'JUVA/RFS/FLTWBSL/001','2026-07-14','2027-01-14',[],false,0);
if (!$ready['ready']) { echo "FLAT WEBBING SLING OPTIONAL EVIDENCE READINESS: FAIL\n"; print_r($ready); $failed++; } else echo "FLAT WEBBING SLING OPTIONAL EVIDENCE READINESS: PASS\n";

foreach (['first_examination_after_installation','installed_correctly','examined_within_six_months','examined_within_twelve_months','examined_under_scheme','examined_after_exceptional_circumstances','fit_for_purpose'] as $key) {
    foreach (['Yes','No'] as $answer) {
        $copy=$rows; foreach($copy as &$row){if($row['field_key']===$key)$row['value_text']=$answer;} unset($row);
        $check=flat_webbing_sling_readiness($inspection,$copy,'JUVA/RFS/FLTWBSL/001','2026-07-14','2027-01-14',[],false,0);
        if(!$check['ready']){$failed++;echo "FLAT WEBBING SLING BOOLEAN $key=$answer: FAIL\n";}
    }
}
if ($failed===0) echo "FLAT WEBBING SLING BOOLEAN YES/NO MATRIX: PASS\n";

foreach ([[true,'Yes'],[false,'No'],[1,'Yes'],[0,'No'],['1','Yes'],['0','No'],['yes','Yes'],['no','No']] as $case) {
    if (flat_webbing_sling_boolean($case[0]) !== $case[1]) {
        echo 'FLAT WEBBING SLING BOOLEAN NORMALIZATION '.var_export($case[0],true).": FAIL\n";
        $failed++;
    }
}
if (flat_webbing_sling_boolean(null) !== null || flat_webbing_sling_boolean('') !== null) {
    echo "FLAT WEBBING SLING BOOLEAN UNANSWERED NORMALIZATION: FAIL\n";
    $failed++;
} else echo "FLAT WEBBING SLING BOOLEAN REPRESENTATION NORMALIZATION: PASS\n";

$missingAnswer = $rows;
foreach ($missingAnswer as &$row) if ($row['field_key'] === 'examined_within_twelve_months') $row['value_text'] = '';
unset($row);
$missingCheck = flat_webbing_sling_readiness($inspection,$missingAnswer,'JUVA/RFS/FLTWBSL/001','2026-07-14','2027-01-14',[],false,0);
$blocker = $missingCheck['blocking_items'][0] ?? [];
if (($blocker['key'] ?? '') !== 'examined_within_twelve_months' || ($blocker['step'] ?? 0) !== 3 || empty($blocker['label']) || empty($blocker['reason'])) {
    echo "FLAT WEBBING SLING STRUCTURED BLOCKER: FAIL\n";
    print_r($missingCheck);
    $failed++;
} else echo "FLAT WEBBING SLING STRUCTURED BLOCKER: PASS\n";
$futureDanger = $rows;
foreach ($futureDanger as &$row) {
    if ($row['field_key'] === 'defect_description') $row['value_text'] = 'Abrasion requiring monitored replacement';
    if ($row['field_key'] === 'defect_future_danger') $row['value_text'] = 'Yes';
    if ($row['field_key'] === 'action_due_date') $row['value_text'] = '';
}
unset($row);
$dangerCheck = flat_webbing_sling_readiness($inspection,$futureDanger,'JUVA/RFS/FLTWBSL/001','2026-07-14','2027-01-14',[],false,0);
if (!isset($dangerCheck['validation_errors']['action_due_date'])) { echo "FLAT WEBBING SLING CONDITIONAL DEFECT DATE: FAIL\n"; $failed++; } else echo "FLAT WEBBING SLING CONDITIONAL DEFECT DATE: PASS\n";

$auditDir = ensure_private_storage_dir('audit/flat-webbing-sling/readiness-fix/run-' . gmdate('Ymd-His'));
$pdf = $auditDir . DIRECTORY_SEPARATOR . 'non-aveon-flat-webbing-sling-readiness-fix.pdf';
$payload=flat_webbing_sling_payload($inspection,$rows,'JUVA/RFS/FLTWBSL/001',1,'https://cert.juvaoil.com/verify/0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef','2026-07-14','2027-01-14');
$payload['authentication']=['inspector_signature_path'=>null,'authenticator_signature_path'=>null,'company_stamp_path'=>null];
$payload['status']='VALID';
@unlink($pdf);
flat_webbing_sling_render_certificate_pdf($pdf,$payload);
if(!is_file($pdf)||filesize($pdf)<100000||strpos((string)file_get_contents($pdf),'/Count 1')===false){echo "FLAT WEBBING SLING PDF RENDER: FAIL\n";$failed++;}else echo "FLAT WEBBING SLING PDF RENDER: PASS\n";

function extract_pdf_jpeg(string $pdfPath,string $jpegPath): bool {
    $data=file_get_contents($pdfPath); if($data===false)return false;
    $object=strpos($data,'5 0 obj'); if($object===false)return false;
    $start=strpos($data,"stream\n",$object); if($start===false)return false; $start+=7;
    $end=strpos($data,"\nendstream",$start); if($end===false)return false;
    return file_put_contents($jpegPath,substr($data,$start,$end-$start))!==false;
}
$generatedJpg=$auditDir.DIRECTORY_SEPARATOR.'generated.png';
$tmpJpg=$auditDir.DIRECTORY_SEPARATOR.'generated-source.jpg';
$reference='C:/Users/JUDE/Documents/Juva Oil/BAR CODE CERTIFICATE SAMPLES/FLAT WEBBING SLING.jpg';
if(extract_pdf_jpeg($pdf,$tmpJpg)&&is_file($reference)){
    $gen=imagecreatefromjpeg($tmpJpg); $ref=imagecreatefromjpeg($reference);
    $w=827;$h=1170;$refSized=imagecreatetruecolor($w,$h);$genSized=imagecreatetruecolor($w,$h);imagecopyresampled($refSized,$ref,0,0,0,0,$w,$h,imagesx($ref),imagesy($ref));imagecopyresampled($genSized,$gen,0,0,0,0,$w,$h,imagesx($gen),imagesy($gen));
    imagepng($genSized,$generatedJpg);
    $side=imagecreatetruecolor($w*2,$h);imagecopy($side,$refSized,0,0,0,0,$w,$h);imagecopy($side,$genSized,$w,0,0,0,$w,$h);imagepng($side,$auditDir.DIRECTORY_SEPARATOR.'side-by-side.png');
    $overlay=imagecreatetruecolor($w,$h);imagecopy($overlay,$refSized,0,0,0,0,$w,$h);imagecopymerge($overlay,$genSized,0,0,0,0,$w,$h,50);imagepng($overlay,$auditDir.DIRECTORY_SEPARATOR.'overlay-50-percent.png');
    $diff=imagecreatetruecolor($w,$h);$sumDiff=0;for($y=0;$y<$h;$y++){for($x=0;$x<$w;$x++){ $a=imagecolorat($refSized,$x,$y);$b=imagecolorat($genSized,$x,$y);$r=abs((($a>>16)&255)-(($b>>16)&255));$g=abs((($a>>8)&255)-(($b>>8)&255));$bl=abs(($a&255)-($b&255));$sumDiff+=($r+$g+$bl);imagesetpixel($diff,$x,$y,(($r << 16) | ($g << 8) | $bl)); }}imagepng($diff,$auditDir.DIRECTORY_SEPARATOR.'pixel-difference.png');echo 'FLAT WEBBING SLING VISUAL MEAN ABSOLUTE DIFFERENCE: '.number_format($sumDiff/($w*$h*3),2)."\n";
    imagedestroy($gen);imagedestroy($genSized);imagedestroy($ref);imagedestroy($refSized);imagedestroy($side);imagedestroy($overlay);imagedestroy($diff);@unlink($tmpJpg);
    echo "FLAT WEBBING SLING VISUAL ARTIFACTS: PASS\n";
}else{echo "FLAT WEBBING SLING VISUAL ARTIFACTS: FAIL\n";$failed++;}

exit($failed?1:0);
