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

$inspection=['id'=>999,'inspection_date'=>'2026-08-11','next_due_date'=>'2027-02-11','location'=>'Port Harcourt','client_name'=>'TEST CLIENT','client_address'=>'Test Address','equipment_name'=>'Test Bow Shackle','asset_code'=>'SHK-001','equipment_serial_number'=>'SHK-001','equipment_manufacturer'=>'Crosby','equipment_safe_working_load'=>'25 TONNES','equipment_standard'=>'BS EN 13889','equipment_manufacture_date_value'=>'2029','previous_examination_date'=>'2026-02','inspector_name'=>'Test Inspector','inspector_qualification'=>'LEEA'];
$values=['client_address'=>'Test Address','inspection_location'=>'Port Harcourt','accessory_type'=>'Bow Shackle','size_marking'=>'25 mm','manufacturer'=>'Crosby','standard'=>'BS EN 13889','colour_code'=>'BLUE','safe_working_load'=>'25 TONNES','pin_condition'=>'pass','body_distortion_check'=>'pass','defect_observation_sheet_attached'=>'No','reason_for_examination_code'=>'B'];
$rows=[];
foreach($values as $key=>$value) $rows[]=['field_key'=>$key,'label'=>$key,'is_required'=>1,'value_text'=>$value];
$itemValues=['serial_number'=>'1','identification_number'=>'SHK-001','description'=>'SCREW PIN BOW SHACKLE','working_load_limit'=>'25 TONNES','swl_wll'=>'25 TONNES','last_thorough_examination_date'=>'2026-02-11','date_last_examined'=>'2026-02-11','manufacturer'=>'CROSBY','next_thorough_examination_date'=>'2027-02-11','reason_for_examination_code'=>'B','test_details'=>'VISUAL EXAMINATION','remarks'=>'VISUAL EXAMINATION','status_code'=>'ND','status'=>'ND','safe_to_use'=>'YES'];
$items=[];
foreach($itemValues as $key=>$value) $items[]=['section_key'=>'shackles_items','row_index'=>0,'column_key'=>$key,'value_text'=>$value];
$canonicalNumber='JUVA/JUKOSO/SHK/001';
$r=shackles_readiness($inspection,$rows,$items,$canonicalNumber,'2026-08-11','2027-02-11');
shackles_test_assert((bool)$r['ready'],'readiness should pass');
echo "SHACKLES READINESS: PASS\n";

$p=shackles_payload($inspection,$rows,$items,$canonicalNumber,1,'https://cert.juvaoil.com/verify/test-token','2026-08-11','2027-02-11');
$p['authentication']=['inspector_signature_path'=>null,'authenticator_signature_path'=>null];
$p['authenticator_name']='Test Authenticator';
$p['authenticator_qualification']='LEEA LMM';
shackles_test_assert($p['certificate_number']===$canonicalNumber,'mapper must preserve the engine-supplied canonical certificate number');
$contract=shackles_layout_contract();
shackles_test_assert($contract['information_row']===['employer','premises','status_legend'],'upper information row must preserve the original three-part structure');
shackles_test_assert($contract['certificate_number_position']==='top_metadata','certificate number must appear in the mandated top metadata line');
shackles_test_assert($contract['table_columns']===['serial','identification','description','working_load','last_examination','manufacturer','next_examination','reason_code','test_details','status','safe_to_use'],'main table order must match the original document');
shackles_test_assert($contract['signatory_columns']===['inspector','authenticator'],'original two-column signatory structure must remain');
shackles_test_assert($contract['top_metadata']===['certificate_number','examination_date','colour_code','standard'],'top metadata order must match the mandated document');
shackles_test_assert($contract['populated_values_bold']===true,'entered values must render bold');
$templateSource = file_get_contents(__DIR__.'/../api/certificates/templates/shackles.php');
shackles_test_assert(is_string($templateSource) && strpos($templateSource, '$widths=[72,285,315,170,215,225,225,155,225,150,182];') !== false, 'main table rows must span the full 2219-pixel form width');
shackles_test_assert(shackles_item_value($p['items'][0],['identification_number','serial_number'])==='SHK-001','current identification field must map to the original identification column');
shackles_test_assert(shackles_item_value(['serial_number'=>'LEGACY-1'],['identification_number','serial_number'])==='LEGACY-1','legacy Shackles identifiers must retain a safe renderer fallback');
shackles_test_assert(($p['fields']['date_of_manufacture']??'')==='2029','year-only manufacture date must retain its precision');
shackles_test_assert(($p['items'][0]['date_last_examined']??'')==='2026-02','month/year previous examination date must retain its precision');
shackles_test_assert(shackles_date('2029')==='2029','renderer must not invent month or day');
shackles_test_assert(shackles_date('2026-02')==='02/2026','renderer must preserve month/year precision');
echo "SHACKLES ORIGINAL LAYOUT CONTRACT: PASS\n";

$dir=sys_get_temp_dir().'/juva-shackles-visual-correction';
if(!is_dir($dir)&&!mkdir($dir,0775,true)) exit(1);
$renderCases=[
    ['status'=>'VALID','number'=>$canonicalNumber,'file'=>'shackles-valid.pdf'],
    ['status'=>'EXPIRED','number'=>$canonicalNumber,'file'=>'shackles-expired.pdf'],
    ['status'=>'VALID','number'=>'JUVA/LONGCLIENT12/SHK/001','file'=>'shackles-long-client-code.pdf'],
];
foreach($renderCases as $case){
    $p['status']=$case['status'];
    $p['certificate_number']=$case['number'];
    $pdf=$dir.'/'.$case['file'];
    shackles_render_certificate_pdf($pdf,$p);
    shackles_test_assert(is_file($pdf)&&filesize($pdf)>=10000,'renderer must create '.$case['file']);
    echo 'SHACKLES '.$case['status'].' '.$case['number']." LANDSCAPE PDF: PASS\n";
}
$singleItem=$p['items'][0];
$p['items']=[$singleItem,$singleItem,$singleItem,$singleItem,$singleItem,$singleItem];
$p['certificate_number']='JUVA/LONGCLIENT12/SHK/999';
$sixRowPdf=$dir.'/shackles-six-row.pdf';
shackles_render_certificate_pdf($sixRowPdf,$p);
shackles_test_assert(is_file($sixRowPdf)&&filesize($sixRowPdf)>=10000,'renderer must keep six rows within one landscape page');
echo "SHACKLES SIX ROW LANDSCAPE PDF: PASS\n";
