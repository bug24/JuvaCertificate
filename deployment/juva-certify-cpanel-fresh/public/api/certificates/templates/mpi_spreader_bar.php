<?php
require_once __DIR__.'/../mappers/mpi_spreader_bar_mapper.php';

function mpi_spreader_bar_render_certificate_pdf(string $path,array $p):void
{
    $im=imagecreatetruecolor(1654,2339);imagefilledrectangle($im,0,0,1654,2339,certificate_color($im,'#FFFFFF'));
    $font=certificate_find_font(false);$bold=certificate_find_font(true)?:$font;$black='#151515';$muted='#363636';$green='#08785D';$red='#B42318';
    $asset=certificate_runtime_asset('assets/certificates/chain-block');$fields=$p['fields'];$x=80;$w=1494;$y=70;
    $text=function($im,string $value,int $x,int $y,int $size,string $color,?string $face,string $align='left'){certificate_draw_text($im,$value,$x,$y,$size,$color,$face,$align);};
    $wrap=function($im,string $value,int $x,int $y,int $width,int $size,string $color,?string $face,int $line=25,string $align='left',int $max=6)use($text){
        $lines=certificate_wrap_text($value,$width,$size,$face);foreach(array_slice($lines,0,$max) as $i=>$row){$tx=$align==='center'?$x+(int)($width/2):$x;$text($im,$row,$tx,$y+($i*$line),$size,$color,$face,$align);}
    };
    $cell=function($im,int $x,int $y,int $cw,int $ch,string $value,int $size,?string $face,string $align='left',int $pad=10)use($wrap,$black){
        imagerectangle($im,$x,$y,$x+$cw,$y+$ch,certificate_color($im,'#252525'));$wrap($im,$value,$x+$pad,$y+$size+8,$cw-($pad*2),$size,$black,$face,$size+5,$align,max(1,(int)(($ch-12)/($size+5))));
    };
    $put=function($im,string $file,int $x,int $y,int $maxW,int $maxH){if(!is_file($file))return;$src=@imagecreatefromstring((string)file_get_contents($file));if(!$src)return;$sw=imagesx($src);$sh=imagesy($src);$scale=min($maxW/$sw,$maxH/$sh);$dw=(int)($sw*$scale);$dh=(int)($sh*$scale);imagecopyresampled($im,$src,$x+(int)(($maxW-$dw)/2),$y+(int)(($maxH-$dh)/2),0,0,$dw,$dh,$sw,$sh);imagedestroy($src);};
    $mark=function($im,int $cx,int $cy,bool $checked){if(!$checked)return;$c=certificate_color($im,'#111111');imagesetthickness($im,5);imageline($im,$cx-10,$cy,$cx-2,$cy+10,$c);imageline($im,$cx-2,$cy+10,$cx+14,$cy-13,$c);imagesetthickness($im,1);};
    $box=function($im,int $x,int $y,string $label,bool $checked)use($text,$mark,$font,$black){$text($im,$label,$x,$y+25,16,$black,$font);imagerectangle($im,$x+82,$y,$x+116,$y+34,certificate_color($im,'#222'));$mark($im,$x+99,$y+17,$checked);};
    $selected=function(string $key,string $choice)use($fields):bool{return in_array(strtolower($choice),mpi_spreader_bar_list($fields[$key]??''),true);};
    $field=function(string $key,string $fallback='')use($fields):string{$v=trim((string)($fields[$key]??''));return $v!==''?$v:$fallback;};

    $put($im,certificate_runtime_asset('logo.png'),$x-30,$y-20,280,170);
    $put($im,$asset.'/leea-logo.png',$x+$w-155,$y,145,115);
    imagearc($im,$x+270,$y+3,960,92,0,360,certificate_color($im,'#222'));imagerectangle($im,$x+270,$y+3,$x+1230,$y+95,certificate_color($im,'#222'));
    $text($im,'JUVA-OIL SERVICES (NIG) LIMITED',$x+750,$y+65,36,$black,$bold,'center');
    $status=strtoupper((string)($p['status']??'VALID'));
    certificate_draw_status_badge($im,$status,$x+$w-155,$y+125,145,45,$bold,21);
    $y+=145;$text($im,'VISUAL/MAGNETIC PARTICLE INSPECTION CERTIFICATE',$x+(int)($w/2),$y,29,$black,$bold,'center');$y+=36;
    foreach(["This report complies with the Lifting Equipment Engineers Association's technical requirements",'STATUTORY INSTRUMENTS 1990 NO.2307','The Lifting Operations and Lifting Equipment Regulations 1998','The Supply of Machinery (Safety) Regulation 1992','The Provision and Use of Work Equipment Regulations 1998'] as $line){$text($im,$line,$x+(int)($w/2),$y,14,$muted,$font,'center');$y+=20;}
    $y+=22;$third=(int)($w/3);
    $cell($im,$x,$y,$third,55,'DATE OF THOROUGH EXAMINATION: '.mpi_spreader_bar_date((string)$p['examination_date']),17,$font);
    $cell($im,$x+$third,$y,$third,55,'DATE OF REPORT: '.mpi_spreader_bar_date((string)$p['report_date']),17,$font);
    $cell($im,$x+($third*2),$y,$w-($third*2),55,'REPORT NO: '.$p['certificate_number'],17,$font);$y+=72;
    $half=(int)($w*.58);
    $cell($im,$x,$y,$half,105,"NAME AND ADDRESS OF EMPLOYER FOR WHOM THE THOROUGH EXAMINATION WAS MADE:\n".strtoupper((string)$p['client_name'])."\n".strtoupper((string)$p['client_address']),17,$font);
    $cell($im,$x+$half,$y,$w-$half,105,"ADDRESS OF PREMISES AT WHICH THE EXAMINATION WAS MADE:\n".strtoupper($field('premises_address')),17,$font,'center');$y+=105;
    $left=(int)($w*.55);
    $cell($im,$x,$y,$left,210,"ITEM INSPECTED: ".strtoupper($field('item_inspected',(string)$p['equipment_name']))."\n\nSERIAL NUMBER: ".strtoupper($field('serial_number',(string)$p['asset_code']))."\n\nSTANDARD USED: ".strtoupper($field('standard_used'))."\n\nSAFE WORKING LOAD: ".strtoupper($field('safe_working_load')),18,$font);
    $cell($im,$x+$left,$y,$w-$left,210,"TYPE OF MATERIAL: ".strtoupper($field('material_type'))."\n\nAREAS INSPECTED / SURFACE CONDITION: ".strtoupper($field('inspection_area_surface_condition'))."\n\nACCEPTANCE LIMITS: ".strtoupper($field('acceptance_limits'))."\n\nDIMENSION: ".strtoupper($field('dimension')),18,$font);$y+=245;
    $mpW=(int)($w*.60);$dpW=$w-$mpW;
    $cell($im,$x,$y,$mpW,50,'MAGNETIC PARTICLE',22,$bold,'center');$cell($im,$x+$mpW,$y,$dpW,50,'DYE PENETRANT',22,$bold,'center');$y+=50;
    $matrixStart=$y;imagerectangle($im,$x,$y,$x+$mpW,$y+390,certificate_color($im,'#222'));
    $text($im,'EQUIPMENT',$x+15,$y+34,17,$black,$font);$box($im,$x+270,$y+8,'COIL',$selected('magnetic_particle_equipment','coil'));$box($im,$x+500,$y+8,'PRODS',$selected('magnetic_particle_equipment','prods'));
    $box($im,$x+270,$y+55,'YOKE',$selected('magnetic_particle_equipment','yoke'));$box($im,$x+500,$y+55,'UV LIGHT',$selected('magnetic_particle_equipment','uv light'));imageline($im,$x,$y+105,$x+$mpW,$y+105,certificate_color($im,'#222'));
    $text($im,'MAGNETIC PARTICLE',$x+15,$y+140,17,$black,$font);$box($im,$x+270,$y+115,'DRY',$selected('magnetic_particle_medium','dry'));$box($im,$x+500,$y+115,'VISIBLE',$selected('magnetic_particle_medium','visible'));$box($im,$x+270,$y+162,'WET',$selected('magnetic_particle_medium','wet'));$box($im,$x+500,$y+162,'FLUORESCENT',$selected('magnetic_particle_medium','fluorescent'));imageline($im,$x,$y+215,$x+$mpW,$y+215,certificate_color($im,'#222'));
    $text($im,'MAGNETIZING CURRENT',$x+15,$y+250,17,$black,$font);$box($im,$x+300,$y+225,'AC',$selected('magnetizing_current','ac'));$box($im,$x+500,$y+225,'HWDC',$selected('magnetizing_current','hwdc'));$box($im,$x+300,$y+272,'DC',$selected('magnetizing_current','dc'));imageline($im,$x,$y+325,$x+$mpW,$y+325,certificate_color($im,'#222'));
    $text($im,'MAGNETIZING PROCESS',$x+15,$y+360,17,$black,$font);$box($im,$x+430,$y+335,'CONTINUOUS',$selected('magnetizing_process','continuous'));$box($im,$x+430,$y+375,'RESIDUAL',$selected('magnetizing_process','residual'));
    $cell($im,$x+$mpW,$matrixStart,$dpW,130,"PENETRANT\n\n".strtoupper($field('dye_penetrant','N/A')),18,$font);
    $cell($im,$x+$mpW,$matrixStart+130,$dpW,130,"DEVELOPER\n\n".strtoupper($field('dye_developer','N/A')),18,$font);
    $cell($im,$x+$mpW,$matrixStart+260,$dpW,130,"SOLVENT / CLEANER\n\n".strtoupper($field('dye_solvent_cleaner','N/A')),18,$font);$y+=420;
    $cell($im,$x,$y,$w,70,"NDE (NON-DESTRUCTIVE EXAMINATION)\nPROCEDURE REF. NO: ".strtoupper($field('nde_procedure_reference'))."     METHOD: ".strtoupper($field('inspection_method')),17,$font);$y+=70;
    $cell($im,$x,$y,$w,120,"REMARKS:\n".$field('remarks'),18,$font);$y+=120;
    $safe=mpi_spreader_bar_bool($fields['equipment_safe_for_use']??null);$cell($im,$x,$y,$w,62,'IS THIS EQUIPMENT SAFE FOR USE?',18,$bold);$box($im,$x+$w-300,$y+14,'YES',$safe===true);$box($im,$x+$w-145,$y+14,'NO',$safe===false);$y+=62;
    $signW=(int)($w/3);$auth=$p['authentication']??[];
    $cell($im,$x,$y,$signW,118,"INSPECTOR\n".strtoupper((string)$p['inspector_name'])."\n".strtoupper((string)$p['inspector_qualification'])."\nSIGNATURE:",15,$font);
    $cell($im,$x+$signW,$y,$signW,118,"AUTHENTICATOR\n".strtoupper((string)$p['authenticator_name'])."\n".strtoupper((string)$p['authenticator_qualification'])."\nSIGNATURE:",15,$font);
    $cell($im,$x+($signW*2),$y,$w-($signW*2),118,"NEXT THOROUGH EXAMINATION\n".mpi_spreader_bar_date((string)$p['next_examination_date']),18,$bold,'center');
    if(!empty($auth['inspector_signature_path']))$put($im,(string)$auth['inspector_signature_path'],$x+250,$y+55,200,52);
    if(!empty($auth['authenticator_signature_path']))$put($im,(string)$auth['authenticator_signature_path'],$x+$signW+250,$y+55,200,52);$y+=118;
    $company=$p['company'];$cell($im,$x,$y,$w,74,"NAME AND ADDRESS OF COMPANY AUTHENTICATING THIS REPORT: ".strtoupper((string)$company['name']),16,$font);$y+=84;
    imagearc($im,$x,$y,$w,132,0,360,certificate_color($im,'#222'));imagerectangle($im,$x,$y,$x+$w,$y+132,certificate_color($im,'#222'));
    $wrap($im,"OFFICE: ".$company['registered']."\n\nOPERATIONAL BASE: ".$company['operational'],$x+18,$y+38,$w-36,18,$black,$font,28,'left',4);
    if(!empty($auth['company_stamp_path']))$put($im,(string)$auth['company_stamp_path'],$x+$w-270,$y+18,230,96);$y+=155;
    $put($im,$asset.'/bsi-logo.png',$x+20,$y,130,78);$put($im,$asset.'/asnt-logo.png',$x+180,$y,130,78);$put($im,$asset.'/acebi-logo.png',$x+340,$y,145,78);
    if(class_exists('QRCode')){
        $qr=QRCode::getMinimumQRCode((string)$p['verification_url'],QR_ERROR_CORRECT_LEVEL_M);$count=$qr->getModuleCount();$module=5;$quiet=4;$size=($count+8)*$module;$qx=$x+$w-$size-15;$qy=$y-10;
        imagefilledrectangle($im,$qx,$qy,$qx+$size,$qy+$size,certificate_color($im,'#FFF'));for($yy=0;$yy<$count;$yy++)for($xx=0;$xx<$count;$xx++)if($qr->isDark($yy,$xx))imagefilledrectangle($im,$qx+(($xx+$quiet)*$module),$qy+(($yy+$quiet)*$module),$qx+(($xx+$quiet+1)*$module)-1,$qy+(($yy+$quiet+1)*$module)-1,certificate_color($im,'#111'));
        $text($im,'SCAN TO VERIFY AUTHENTICITY',$qx+(int)($size/2),$qy+$size+22,11,$black,$bold,'center');$text($im,'cert.juvaoil.com',$qx+(int)($size/2),$qy+$size+39,10,$muted,$font,'center');
    }
    $jpg=tempnam(sys_get_temp_dir(),'juva-mpi-');if($jpg===false)throw new RuntimeException('Unable to create MPI render workspace.');imagejpeg($im,$jpg,94);imagedestroy($im);certificate_create_jpeg_pdf($path,$jpg);
}
