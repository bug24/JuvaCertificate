<?php

declare(strict_types=1);

require_once __DIR__.'/../api/lib/certificate_service.php';
require_once __DIR__.'/../api/certificates/mappers/shackles_mapper.php';
require_once __DIR__.'/../api/certificates/templates/shackles.php';

function shackles_test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "SHACKLES TEST FAILED: {$message}\n");
        exit(1);
    }
}

$inspection=['id'=>999,'inspection_date'=>'2026-08-11','next_due_date'=>'2027-02-11','location'=>'Port Harcourt','client_name'=>'TEST CLIENT','client_address'=>'Test Address','equipment_name'=>'Test Bow Shackle','asset_code'=>'SHK-001','inspector_name'=>'Test Inspector','inspector_qualification'=>'LEEA'];
$values=['client_address'=>'Test Address','inspection_location'=>'Port Harcourt','accessory_type'=>'Bow Shackle','size_marking'=>'25 mm','manufacturer'=>'Crosby','standard'=>'BS EN 13889','safe_working_load'=>'25 TONNES','pin_condition'=>'pass','body_distortion_check'=>'pass','defect_observation_sheet_attached'=>'No'];
$rows=[];
foreach($values as $key=>$value) $rows[]=['field_key'=>$key,'label'=>$key,'is_required'=>1,'value_text'=>$value];
$items=[['section_key'=>'shackles_items','row_index'=>0,'column_key'=>'serial_number','value_text'=>'SHK-001'],['section_key'=>'shackles_items','row_index'=>0,'column_key'=>'description','value_text'=>'Bow shackle'],['section_key'=>'shackles_items','row_index'=>0,'column_key'=>'swl_wll','value_text'=>'25 TONNES'],['section_key'=>'shackles_items','row_index'=>0,'column_key'=>'manufacturer','value_text'=>'Crosby'],['section_key'=>'shackles_items','row_index'=>0,'column_key'=>'status','value_text'=>'ND']];
$canonicalNumber='JUVA/JUKOSO/SHK/001';
$r=shackles_readiness($inspection,$rows,$items,$canonicalNumber,'2026-08-11','2027-02-11');
shackles_test_assert((bool)$r['ready'],'readiness should pass');
echo "SHACKLES READINESS: PASS\n";

$p=shackles_payload($inspection,$rows,$items,$canonicalNumber,1,'https://cert.juvaoil.com/verify/test-token','2026-08-11','2027-02-11');
$p['authentication']=['inspector_signature_path'=>null,'authenticator_signature_path'=>null];
shackles_test_assert($p['certificate_number']===$canonicalNumber,'mapper must preserve the engine-supplied canonical certificate number');
$upper=shackles_upper_information_cells($p);
shackles_test_assert($upper['certificate_number']==="CERTIFICATE NO:\n{$canonicalNumber}",'upper information grid must contain the complete canonical certificate number');
shackles_test_assert(strpos($upper['status_legend'],'STATUS:')===0,'upper information grid must retain the status legend');

echo "SHACKLES UPPER CERTIFICATE NUMBER: PASS\n";
$dir=sys_get_temp_dir().'/juva-shackles-test';
if(!is_dir($dir)&&!mkdir($dir,0775,true)) exit(1);
$renderCases=[
    ['status'=>'VALID','number'=>$canonicalNumber,'file'=>'shackles-valid.pdf'],
    ['status'=>'EXPIRED','number'=>$canonicalNumber,'file'=>'shackles-expired.pdf'],
    ['status'=>'VALID','number'=>'JUVA/LONGCLIENT12/SHK/001','file'=>'shackles-long-client-code.pdf'],
];
foreach($renderCases as $case){
    $p['status']=$case['status'];
    $p['certificate_number']=$case['number'];
    $upper=shackles_upper_information_cells($p);
    shackles_test_assert(strpos($upper['certificate_number'],$case['number'])!==false,'header helper must retain the full number for '.$case['file']);
    $pdf=$dir.'/'.$case['file'];
    shackles_render_certificate_pdf($pdf,$p);
    shackles_test_assert(is_file($pdf)&&filesize($pdf)>=10000,'renderer must create '.$case['file']);
    echo 'SHACKLES '.$case['status'].' '.$case['number']." LANDSCAPE PDF: PASS\n";
}
