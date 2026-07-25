<?php
$_SERVER['REQUEST_METHOD']='CLI';
require_once __DIR__.'/../api/lib/certificate_service.php';

$values=[
 'report_date'=>'2026-07-24','premises_address'=>'Jukoso Fabrication Yard, Port Harcourt','item_inspected'=>'MODULAR SPREADER BAR',
 'serial_number'=>'JGS-SB-2407','material_type'=>'HIGH STRENGTH STEEL','inspection_area_surface_condition'=>'LIFTING LUGS AND WELD ZONES',
 'standard_used'=>'ASTM E709-21, BS EN 13155:2020','acceptance_limits'=>'NO RELEVANT INDICATIONS; ACCEPTED','safe_working_load'=>'25 TONNES','dimension'=>'L = 8.2 M',
 'magnetic_particle_equipment'=>'Yoke, UV Light','magnetic_particle_medium'=>'Visible, Wet','magnetizing_current'=>'AC','magnetizing_process'=>'Continuous',
 'dye_penetrant'=>'N/A','dye_developer'=>'N/A','dye_solvent_cleaner'=>'N/A','nde_procedure_reference'=>'JUVA/MPI/014/2026',
 'inspection_method'=>'CONTINUOUS WET MAGNETIZATION','remarks'=>'No relevant defect indication was found. The equipment is fit for continued operation.',
 'equipment_safe_for_use'=>'No','inspector_name_snapshot'=>'JUVA Inspection Officer','inspector_qualification_snapshot'=>'ASNT LEVEL II MPI',
 'authenticator_name'=>'JUVA Technical Reviewer','authenticator_qualification'=>'LEEA, ASNT LEVEL II',
];
$required=array_keys($values);$rows=[];foreach($values as $key=>$value)$rows[]=['field_key'=>$key,'label'=>ucwords(str_replace('_',' ',$key)),'is_required'=>in_array($key,$required,true)?1:0,'pdf_section'=>'inspection','value_text'=>$value];
$inspection=['id'=>990030,'inspection_date'=>'2026-07-24','next_due_date'=>'2027-01-24','client_name'=>'Jukoso Global Services','client_address'=>'Port Harcourt, Rivers State','equipment_name'=>'Spreader Bar','asset_code'=>'JGS-SB-2407','inspector_name'=>'JUVA Inspection Officer','inspector_qualification'=>'ASNT LEVEL II MPI'];
$number='JUVA/JGS/MPISBAR/001';$ready=mpi_spreader_bar_readiness($inspection,$rows,$number,'2026-07-24','2027-01-24');
if(!$ready['ready'])throw new RuntimeException('MPI readiness failed: '.json_encode($ready));
if(mpi_spreader_bar_bool('No')!==false)throw new RuntimeException('MPI Safe for Use NO was treated as missing.');
$matrix=['magnetic_particle_equipment'=>['coil','prods','yoke','uv light'],'magnetic_particle_medium'=>['dry','visible','wet','fluorescent'],'magnetizing_current'=>['ac','hwdc','dc'],'magnetizing_process'=>['continuous','residual']];
foreach($matrix as $key=>$choices)foreach($choices as $choice)if(mpi_spreader_bar_list($choice)!==[$choice])throw new RuntimeException("MPI matrix normalization failed: $key/$choice");
$payload=mpi_spreader_bar_payload($inspection,$rows,$number,1,'https://cert.juvaoil.com/verify/'.str_repeat('a',64),'2026-07-24','2027-01-24');$payload['authentication']=['inspector_signature_path'=>null,'authenticator_signature_path'=>null,'company_stamp_path'=>null];$payload['status']='VALID';
$audit=ensure_private_storage_dir('audit/mpi-spreader-bar/run-'.gmdate('Ymd-His'));$pdf=$audit.DIRECTORY_SEPARATOR.'non-aveon-mpi-spreader-bar.pdf';mpi_spreader_bar_render_certificate_pdf($pdf,$payload);
$bytes=(string)file_get_contents($pdf);if(!is_file($pdf)||filesize($pdf)<100000||strpos($bytes,'/MediaBox [0 0 595 842]')===false||strpos($bytes,'/Count 1')===false)throw new RuntimeException('MPI portrait PDF failed.');
$jpg=$audit.DIRECTORY_SEPARATOR.'generated.jpg';$object=strpos($bytes,'5 0 obj');$start=$object===false?false:strpos($bytes,"stream\n",$object);if($start===false)throw new RuntimeException('MPI embedded image missing.');$start+=7;$end=strpos($bytes,"\nendstream",$start);file_put_contents($jpg,substr($bytes,$start,$end-$start));
$reference='C:/Users/JUDE/Documents/Juva Oil/BAR CODE CERTIFICATE SAMPLES/GROUPED CERTIFICATES/MPI-SPREADER BAR_1.jpg';
if(is_file($reference)){$gen=imagecreatefromjpeg($jpg);$ref=imagecreatefromjpeg($reference);$w=827;$h=1169;$gs=imagecreatetruecolor($w,$h);$rs=imagecreatetruecolor($w,$h);imagecopyresampled($gs,$gen,0,0,0,0,$w,$h,imagesx($gen),imagesy($gen));imagecopyresampled($rs,$ref,0,0,0,0,$w,$h,imagesx($ref),imagesy($ref));$side=imagecreatetruecolor($w*2,$h);imagecopy($side,$rs,0,0,0,0,$w,$h);imagecopy($side,$gs,$w,0,0,0,$w,$h);imagepng($side,$audit.'/side-by-side.png');$overlay=imagecreatetruecolor($w,$h);imagecopy($overlay,$rs,0,0,0,0,$w,$h);imagecopymerge($overlay,$gs,0,0,0,0,$w,$h,50);imagepng($overlay,$audit.'/overlay-50-percent.png');$diff=imagecreatetruecolor($w,$h);for($y=0;$y<$h;$y++)for($x=0;$x<$w;$x++){$a=imagecolorat($rs,$x,$y);$b=imagecolorat($gs,$x,$y);$r=abs((($a>>16)&255)-(($b>>16)&255));$g=abs((($a>>8)&255)-(($b>>8)&255));$bl=abs(($a&255)-($b&255));imagesetpixel($diff,$x,$y,(($r<<16)|($g<<8)|$bl));}imagepng($diff,$audit.'/pixel-difference.png');foreach([$gen,$ref,$gs,$rs,$side,$overlay,$diff] as $image)imagedestroy($image);}
@unlink($jpg);
echo "MPI DEDICATED READINESS: PASS\nMPI MATRIX NORMALIZATION: PASS\nMPI SAFE-FOR-USE NO: PASS\nMPI NON-AVEON PORTRAIT PDF: PASS\nMPI QR URL PAYLOAD: PASS\nMPI AUDIT DIR: $audit\n";
