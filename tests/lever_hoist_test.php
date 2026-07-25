<?php
$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once __DIR__ . '/../api/lib/certificate_service.php';

$values = [
    'report_date'=>'2026-07-23',
    'premises_address'=>'Operational Inspection Bay',
    'equipment_type'=>'LEVER HOIST',
    'equipment_description'=>'Hand powered chain hoist',
    'manufacturer'=>'YALE Uno Plus',
    'equipment_id_number'=>'LH-14010024',
    'chain_dimensions'=>'1.5 m x 10 mm',
    'standard'=>'BS EN 13157',
    'safe_working_load'=>'3 TONNES',
    'date_of_manufacture'=>'2020-01-01',
    'date_of_last_examination'=>'2026-01-23',
    'first_examination_after_installation'=>'No',
    'installed_correctly'=>'Yes',
    'examined_within_six_months'=>'Yes',
    'examined_within_twelve_months'=>'No',
    'examined_under_scheme'=>'Yes',
    'examined_after_exceptional_circumstances'=>'No',
    'defect_description'=>'NONE',
    'defect_immediate_danger'=>'No',
    'defect_future_danger'=>'No',
    'future_danger_description'=>'NONE',
    'repair_required'=>'NONE',
    'tests_carried_out'=>'FUNCTIONAL TEST, VISUAL AND THOROUGH EXAMINATION',
    'observations'=>'NONE',
    'fit_for_purpose'=>'Yes',
    'inspector_name_snapshot'=>'Qualified Inspection Officer',
    'inspector_qualification_snapshot'=>'LEEA, ASNT II',
    'authenticator_name'=>'Certification Reviewer',
    'authenticator_qualification'=>'LEEA LMM',
];
$required = ['premises_address','equipment_type','equipment_description','manufacturer','chain_dimensions','standard','safe_working_load','first_examination_after_installation','installed_correctly','examined_within_six_months','examined_within_twelve_months','examined_under_scheme','examined_after_exceptional_circumstances','defect_description','repair_required','tests_carried_out','fit_for_purpose'];
$rows = [];
foreach ($values as $key=>$value) $rows[] = ['field_key'=>$key,'label'=>ucwords(str_replace('_',' ',$key)),'is_required'=>in_array($key,$required,true)?1:0,'value_text'=>$value];

$clients = [
    ['code'=>'JGS','name'=>'Jukoso Global Services','address'=>'Port Harcourt, Rivers State'],
    ['code'=>'SPIE','name'=>'SPIE Nigeria Limited','address'=>'Trans Amadi, Port Harcourt'],
    ['code'=>'AVN','name'=>'AVEON Offshore','address'=>'Rumuolumeni, Port Harcourt'],
];
$audit = ensure_private_storage_dir('audit/lever-hoist/run-' . gmdate('Ymd-His'));
foreach ($clients as $client) {
    $inspection = ['id'=>990020,'inspection_date'=>'2026-07-23','next_due_date'=>'2027-01-23','client_name'=>$client['name'],'client_address'=>$client['address'],'equipment_name'=>'Lever Hoist','asset_code'=>$client['code'].'-LH-001','inspector_name'=>'Qualified Inspection Officer','inspector_qualification'=>'LEEA, ASNT II'];
    $number = 'JUVA/'.$client['code'].'/LVHST/001';
    $ready = lever_hoist_readiness($inspection,$rows,$number,'2026-07-23','2027-01-23');
    if (!$ready['ready']) throw new RuntimeException('Lever Hoist readiness failed for '.$client['code'].': '.json_encode($ready));
    $payload = lever_hoist_payload($inspection,$rows,$number,1,'https://cert.juvaoil.com/verify/'.str_repeat(strtolower($client['code'][0]),64),'2026-07-23','2027-01-23');
    $payload['authentication'] = ['inspector_signature_path'=>null,'authenticator_signature_path'=>null];
    $payload['status'] = 'VALID';
    $pdf = $audit . DIRECTORY_SEPARATOR . strtolower($client['code']) . '-lever-hoist.pdf';
    lever_hoist_render_certificate_pdf($pdf,$payload);
    $bytes = (string)file_get_contents($pdf);
    if (!is_file($pdf) || filesize($pdf)<100000 || strpos($bytes,'/MediaBox [0 0 595 842]')===false || strpos($bytes,'/Count 1')===false) {
        throw new RuntimeException('Lever Hoist portrait PDF failed for '.$client['code']);
    }
}

$dangerRows = $rows;
foreach ($dangerRows as &$row) {
    if ($row['field_key']==='defect_future_danger') $row['value_text']='Yes';
    if ($row['field_key']==='future_danger_description') $row['value_text']='';
}
unset($row);
$blocked = lever_hoist_readiness(['id'=>1,'inspection_date'=>'2026-07-23','next_due_date'=>'2027-01-23'],$dangerRows,'JUVA/TEST/LVHST/001','2026-07-23','2027-01-23');
if (!isset($blocked['validation_errors']['future_danger_description'])) throw new RuntimeException('Lever Hoist conditional defect validation failed.');

echo "LEVER HOIST THREE-CLIENT PORTRAIT RENDER: PASS\n";
echo "LEVER HOIST CONDITIONAL DEFECT VALIDATION: PASS\n";
echo "LEVER HOIST AUDIT DIR: ".$audit."\n";
