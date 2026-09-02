<?php
require_once __DIR__ . '/../mappers/shackles_mapper.php';
require_once __DIR__ . '/landscape_pdf.php';

function shackles_certificate_number_text(string $number): string
{
    return "CERTIFICATE NO:\n" . strtoupper(trim($number));
}

function shackles_layout_contract(): array
{
    return [
        'information_row' => ['employer', 'premises', 'status_legend'],
        'table_columns' => ['serial', 'identification', 'description', 'working_load', 'last_examination', 'manufacturer', 'next_examination', 'reason_code', 'test_details', 'status', 'safe_to_use'],
        'signatory_columns' => ['inspector', 'authenticator'],
        'top_metadata' => ['certificate_number', 'examination_date', 'colour_code', 'standard'],
        'certificate_number_position' => 'top_metadata',
        'populated_values_bold' => true,
    ];
}

function shackles_item_value(array $item, array $keys, string $fallback = ''): string
{
    foreach ($keys as $key) {
        $value = trim((string) ($item[$key] ?? ''));
        if ($value !== '') return $value;
    }
    return $fallback;
}

function shackles_render_certificate_pdf(string $path, array $p): void
{
    $im=imagecreatetruecolor(2339,1654); imagefilledrectangle($im,0,0,2339,1654,certificate_color($im,'#FFFFFF'));
    $font=certificate_find_font(false); $bold=certificate_find_font(true)?:$font; $ink='#202020'; $line='#222222';
    $asset=certificate_runtime_asset('assets/certificates/endless-round-webbing-sling');
    $fit=static function($im,string $v,int $x,int $y,int $w,int $h,int $size,?string $f,string $align='left') use($ink):void{ccu_draw_text_fit($im,$v,$x,$y,$w,$h,$size,$ink,$f,false,$align);};
    $cell=static function($im,int $x,int $y,int $w,int $h,string $v,int $size,?string $f,string $align='left') use($line,$fit):void{imagerectangle($im,$x,$y,$x+$w,$y+$h,certificate_color($im,$line));$fit($im,$v,$x+4,$y+3,$w-8,$h-6,$size,$f,$align);};
    $labelValueCell=static function($im,int $x,int $y,int $w,int $h,string $label,string $value,int $labelSize=14,int $valueSize=18,string $align='left') use($line,$fit,$font,$bold):void{
        imagerectangle($im,$x,$y,$x+$w,$y+$h,certificate_color($im,$line));
        $labelHeight=max(28,(int)round($h*.34));
        $fit($im,$label,$x+5,$y+3,$w-10,$labelHeight-4,$labelSize,$font,$align);
        $fit($im,$value,$x+5,$y+$labelHeight,$w-10,$h-$labelHeight-4,$valueSize,$bold,$align);
    };
    $reasonCell=static function($im,int $x,int $y,int $w,int $h,string $label,string $code) use($line,$fit,$font,$bold):void{
        imagerectangle($im,$x,$y,$x+$w,$y+$h,certificate_color($im,$line));
        $fit($im,$label,$x+4,$y+3,$w-36,$h-6,14,$font);
        $fit($im,$code,$x+$w-34,$y+3,30,$h-6,18,$bold,'center');
    };
    $meta=static function($im,int $x,int $y,int $w,string $label,string $value) use($fit,$font,$bold):void{
        $fit($im,$label,$x,$y,$w,25,14,$font);
        $fit($im,$value,$x,$y+25,$w,34,18,$bold);
    };
    $put=static function($im,string $file,int $x,int $y,int $w,int $h):void{if(!is_file($file))return;$bytes=@file_get_contents($file);$src=$bytes!==false?@imagecreatefromstring($bytes):false;if(!$src)return;$scale=min($w/imagesx($src),$h/imagesy($src));$dw=(int)round(imagesx($src)*$scale);$dh=(int)round(imagesy($src)*$scale);imagecopyresampled($im,$src,$x+(int)(($w-$dw)/2),$y+(int)(($h-$dh)/2),0,0,$dw,$dh,imagesx($src),imagesy($src));imagedestroy($src);};
    $tick=static function($im,int $x,int $y,int $w,int $h):void{$c=certificate_color($im,'#111111');imagesetthickness($im,4);imageline($im,$x+(int)($w*.25),$y+(int)($h*.55),$x+(int)($w*.43),$y+(int)($h*.72),$c);imageline($im,$x+(int)($w*.43),$y+(int)($h*.72),$x+(int)($w*.78),$y+(int)($h*.27),$c);imagesetthickness($im,1);};
    $text=static function($im,string $v,int $x,int $y,int $s,?string $f,string $align='left') use($ink):void{certificate_draw_text($im,$v,$x,$y,$s,$ink,$f,$align);};

    $put($im,$asset.'/leea-logo.png',70,35,170,120);
    $put($im,certificate_runtime_asset('logo.png'),1940,20,330,190);
    $text($im,'JUVA-OIL SERVICES NIGERIA LIMITED',1168,130,42,$bold,'center');
    $text($im,'CERTIFICATE OF THOROUGH EXAMINATION',1168,215,35,$bold,'center');
    $text($im,'SHACKLE',1168,255,27,$bold,'center');
    foreach(["This report complies with the Lifting Equipment Engineers Association's technical requirements",'The Lifting Operations and Lifting Equipment Regulations 1998 SI NO.2307','The Supply of Machinery (Safety) Regulation 1992 SI NO.3073','The Provision and Use of Work Equipment Regulations 1998 SI NO.2306','Nigeria Factories Act CAP F1 LFN, 2004'] as $i=>$regulation)$text($im,$regulation,1168,288+($i*24),17,$font,'center');
    $status=strtoupper((string)($p['status']??'VALID')); certificate_draw_status_badge($im,$status,2070,260,195,55,$bold,22);

    $x=60;$w=2219;$y=420;$fields=$p['fields'];
    $metaWidths=[520,630,500,569];$metaLabels=['CERTIFICATE NO:','DATE OF THIS THOROUGH EXAMINATION:','COLOUR CODE:','STANDARD:'];$metaValues=[strtoupper((string)$p['certificate_number']),shackles_date((string)$p['examination_date']),strtoupper((string)($fields['colour_code']??'')),strtoupper((string)($fields['standard']??''))];$cx=$x;foreach($metaWidths as $i=>$cw){$labelValueCell($im,$cx,$y,$cw,68,$metaLabels[$i],$metaValues[$i],13,17,'center');$cx+=$cw;}$y+=82;
    $clientW=840;$premW=720;$legendW=$w-$clientW-$premW;
    $labelValueCell($im,$x,$y,$clientW,115,'NAME AND ADDRESS OF EMPLOYER FOR WHOM THE THOROUGH EXAMINATION WAS MADE:',strtoupper((string)$p['client_name'])."\n".strtoupper((string)$p['client_address']),14,18);
    $labelValueCell($im,$x+$clientW,$y,$premW,115,'ADDRESS OF PREMISES AT WHICH THE EXAMINATION WAS MADE:',strtoupper((string)$p['inspection_location']),14,18,'center');
    $cell($im,$x+$clientW+$premW,$y,$legendW,115,"STATUS:\nND - NO DEFECT, SDR - SEE DEFECT REPORT\nNF - NOT FOUND, OBS - OBSERVATION (SEE DEFECT REPORT)",15,$font);$y+=115;

    $widths=[72,285,315,170,215,225,225,155,225,150,182];
    $headers=["S/N","IDENTIFICATION NUMBER","DESCRIPTION","WLL OR SWL\n(TONNES)","DATE OF LAST\nTHOROUGH EXAMINATION","MANUFACTURER","LATEST DATE OF NEXT\nTHOROUGH EXAMINATION","REASON FOR EXAMINATION\n(SEE BELOW)","DETAILS OF ANY TEST","STATUS\n(SEE ABOVE)","SAFE TO USE\nYES / NO"];
    $cx=$x;foreach($widths as $i=>$cw){$cell($im,$cx,$y,$cw,96,$headers[$i],15,$font,'center');$cx+=$cw;}$y+=96;
    $items=$p['items'];$rowH=count($items)>4?58:(count($items)>2?68:82);
    foreach($items as $index=>$item){
        $serial=(string)($index+1);
        $values=[
            $serial,
            shackles_item_value($item,['identification_number','serial_number']),
            shackles_item_value($item,['description']),
            shackles_item_value($item,['working_load_limit','swl_wll']),
            shackles_date(shackles_item_value($item,['last_thorough_examination_date','date_last_examined'])),
            shackles_item_value($item,['manufacturer']),
            shackles_date(shackles_item_value($item,['next_thorough_examination_date'],(string)($p['expiry_date']??''))),
            strtoupper(shackles_item_value($item,['reason_for_examination_code'])),
            shackles_item_value($item,['test_details','remarks']),
            strtoupper(shackles_item_value($item,['status_code','status'])),
            strtoupper(shackles_item_value($item,['safe_to_use'],'YES')),
        ];
        $cx=$x;foreach($widths as $i=>$cw){$cell($im,$cx,$y,$cw,$rowH,(string)$values[$i],$i===2?19:17,$bold,'center');$cx+=$cw;}$y+=$rowH;
    }

    $reasonWidths=[340,260,260,260,400,699];$reasonLabels=['REASON FOR EXAMINATION','INSTALLATION:','6 MONTHLY:','12 MONTHLY:','WRITTEN SCHEME:','EXCEPTIONAL CIRCUMSTANCE:'];$reasonCodes=['','A','B','C','D','E'];$cx=$x;foreach($reasonWidths as $i=>$cw){$reasonCell($im,$cx,$y,$cw,58,$reasonLabels[$i],$reasonCodes[$i]);$cx+=$cw;}$y+=68;

    $signW=(int)($w/2);$signH=150;
    $labelValueCell($im,$x,$y,$signW,$signH,'NAME & QUALIFICATIONS OF THE PERSON MAKING THIS REPORT:',strtoupper((string)$p['inspector_name'])."\n".strtoupper((string)$p['inspector_qualification']),14,18);
    $labelValueCell($im,$x+$signW,$y,$w-$signW,$signH,'SIGNATURE OF PERSON AUTHENTICATING THIS REPORT:',strtoupper((string)$p['authenticator_name'])."\n".strtoupper((string)$p['authenticator_qualification']),14,18);
    $text($im,'SIGNATURE:',$x+6,$y+$signH-9,14,$font);$text($im,'SIGNATURE:',$x+$signW+6,$y+$signH-9,14,$font);
    $auth=is_array($p['authentication']??null)?$p['authentication']:[];
    if(!empty($auth['inspector_signature_path']))$put($im,(string)$auth['inspector_signature_path'],$x+250,$y+58,330,82);
    if(!empty($auth['company_stamp_path']))$put($im,(string)$auth['company_stamp_path'],$x+$signW+340,$y+18,330,118);
    if(!empty($auth['authenticator_signature_path']))$put($im,(string)$auth['authenticator_signature_path'],$x+$signW+690,$y+58,330,82);
    $y+=$signH;

    $labelW=470;$ynW=90;$markW=70;
    $cell($im,$x,$y,$labelW,52,'DEFECT / OBSERVATION SHEET ATTACHED?',16,$font);
    $cell($im,$x+$labelW,$y,$ynW,52,'YES',16,$bold,'center');$cell($im,$x+$labelW+$ynW,$y,$markW,52,'',16,$font);
    $cell($im,$x+$labelW+$ynW+$markW,$y,$ynW,52,'NO',16,$bold,'center');$cell($im,$x+$labelW+($ynW*2)+$markW,$y,$markW,52,'',16,$font);
    $attached=strtolower((string)($fields['defect_observation_sheet_attached']??''));
    if($attached==='yes')$tick($im,$x+$labelW+$ynW,$y,$markW,52);
    if($attached==='no')$tick($im,$x+$labelW+($ynW*2)+$markW,$y,$markW,52);

    $footerY=$y+75;$put($im,$asset.'/bsi-logo.png',$x,$footerY,125,90);$put($im,$asset.'/leea-logo.png',$x+150,$footerY,115,90);$put($im,$asset.'/asnt-logo.png',$x+290,$footerY,150,90);
    $fit($im,strtoupper((string)$p['company']['name'])."\n".(string)$p['company']['operational']."\n".(string)$p['company']['phone'].' | '.(string)$p['company']['email'],$x+480,$footerY,1000,105,15,$font);
    if(class_exists('QRCode')){$qr=QRCode::getMinimumQRCode((string)$p['verification_url'],QR_ERROR_CORRECT_LEVEL_M);$n=(int)$qr->getModuleCount();$q=4;$m=5;$size=($n+8)*$m;$cardX=1680;$cardY=$footerY-16;$cardW=590;$cardH=225;imagefilledrectangle($im,$cardX,$cardY,$cardX+$cardW,$cardY+$cardH,certificate_color($im,'#FFFFFF'));imagerectangle($im,$cardX,$cardY,$cardX+$cardW,$cardY+$cardH,certificate_color($im,'#1F4B45'));$qx=$cardX+$cardW-$size-16;$qy=$cardY+12;imagefilledrectangle($im,$qx,$qy,$qx+$size,$qy+$size,certificate_color($im,'#FFFFFF'));for($r=0;$r<$n;$r++)for($c=0;$c<$n;$c++)if($qr->isDark($r,$c))imagefilledrectangle($im,$qx+(($c+$q)*$m),$qy+(($r+$q)*$m),$qx+(($c+$q+1)*$m)-1,$qy+(($r+$q+1)*$m)-1,certificate_color($im,'#000000'));certificate_draw_text($im,'VERIFY WITH INTEGRITY',$cardX+18,$cardY+48,18,'#1F4B45',$bold);certificate_draw_text($im,'Scan to verify this certificate',$cardX+18,$cardY+82,15,'#333333',$font);certificate_draw_text($im,'against JUVA Oil live records.',$cardX+18,$cardY+108,15,'#333333',$font);certificate_draw_text($im,'AUTHENTIC  |  TRACEABLE  |  SECURE',$cardX+18,$cardY+157,13,'#1F4B45',$bold);certificate_draw_text($im,'SCAN TO VERIFY',$qx+(int)($size/2),$cardY+$cardH-12,13,'#1F4B45',$bold,'center');}
    if($cardY+$cardH>1644)throw new RuntimeException('Shackles certificate content exceeds the one-page landscape layout.');
    $jpg=preg_replace('/\.pdf$/i','.jpg',$path)?:$path.'.jpg';imagejpeg($im,$jpg,94);imagedestroy($im);certificate_create_jpeg_pdf_landscape($path,$jpg);
}
