<?php
require_once __DIR__ . '/../mappers/flat_webbing_sling_mapper.php';

function flat_webbing_sling_render_certificate_pdf(string $path, array $p): void
{
    $im = imagecreatetruecolor(1654, 2339);
    imagefilledrectangle($im, 0, 0, 1654, 2339, certificate_color($im, '#FFFFFF'));
    $font = certificate_find_font(false);
    $bold = certificate_find_font(true) ?: $font;
    $ink = '#242424';
    $line = '#333333';
    $asset = certificate_runtime_asset('assets/certificates/flat-webbing-sling');
    $field = static function (string $key, string $fallback = '') use ($p): string {
        $value = trim((string) ($p['fields'][$key] ?? ''));
        return $value !== '' ? $value : $fallback;
    };
    $text = static function ($im, string $value, int $x, int $y, int $size, ?string $font, string $align = 'left') use ($ink): void {
        certificate_draw_text($im, $value, $x, $y, $size, $ink, $font, $align);
    };
    $fit = static function ($im, string $value, int $x, int $y, int $w, int $h, int $size, ?string $font, string $align = 'left') use ($ink): void {
        ccu_draw_text_fit($im, $value, $x, $y, $w, $h, $size, $ink, $font, false, $align);
    };
    $cell = static function ($im, int $x, int $y, int $w, int $h, string $value, int $size, ?string $font, string $align = 'left') use ($line, $fit): void {
        imagerectangle($im, $x, $y, $x + $w, $y + $h, certificate_color($im, $line));
        $fit($im, $value, $x + 2, $y + 2, $w - 4, $h - 4, $size, $font, $align);
    };
    $put = static function ($im, string $file, int $x, int $y, int $w, int $h): void {
        if (!is_file($file)) { return; }
        $bytes = @file_get_contents($file);
        $src = $bytes !== false ? @imagecreatefromstring($bytes) : false;
        if (!$src) { return; }
        $scale = min($w / imagesx($src), $h / imagesy($src));
        $dw = (int) round(imagesx($src) * $scale);
        $dh = (int) round(imagesy($src) * $scale);
        imagecopyresampled($im, $src, $x + (int) (($w - $dw) / 2), $y + (int) (($h - $dh) / 2), 0, 0, $dw, $dh, imagesx($src), imagesy($src));
        imagedestroy($src);
    };
    $roundedBox = static function ($im, int $x1, int $y1, int $x2, int $y2, int $radius, string $fill, string $border): void {
        $fillColor = certificate_color($im, $fill); $borderColor = certificate_color($im, $border);
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $fillColor);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $fillColor);
        imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $fillColor);
        imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $fillColor);
        imageline($im, $x1 + $radius, $y1, $x2 - $radius, $y1, $borderColor); imageline($im, $x1 + $radius, $y2, $x2 - $radius, $y2, $borderColor);
        imageline($im, $x1, $y1 + $radius, $x1, $y2 - $radius, $borderColor); imageline($im, $x2, $y1 + $radius, $x2, $y2 - $radius, $borderColor);
        imagearc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $borderColor); imagearc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $borderColor);
        imagearc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $borderColor); imagearc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $borderColor);
    };    $tick = static function ($im, int $x, int $y, int $w, int $h): void {
        $color = certificate_color($im, '#111111');
        imagesetthickness($im, 5);
        imageline($im, $x + (int) ($w * .25), $y + (int) ($h * .55), $x + (int) ($w * .43), $y + (int) ($h * .73), $color);
        imageline($im, $x + (int) ($w * .43), $y + (int) ($h * .73), $x + (int) ($w * .78), $y + (int) ($h * .27), $color);
        imagesetthickness($im, 1);
    };
    $yn = static function ($im, int $x, int $y, int $w, int $h, string $question, string $value) use ($cell, $bold, $font, $tick): void {
        $questionW = $w - 260;
        $cell($im, $x, $y, $questionW, $h, $question, 17, $font);
        $cell($im, $x + $questionW, $y, 65, $h, 'YES', 16, $bold, 'center');
        $cell($im, $x + $questionW + 65, $y, 65, $h, '', 16, $font, 'center');
        $cell($im, $x + $questionW + 130, $y, 65, $h, 'NO', 16, $bold, 'center');
        $cell($im, $x + $questionW + 195, $y, 65, $h, '', 16, $font, 'center');
        if (strtolower(trim($value)) === 'yes') { $tick($im, $x + $questionW + 65, $y, 65, $h); }
        if (strtolower(trim($value)) === 'no') { $tick($im, $x + $questionW + 195, $y, 65, $h); }
    };

    $put($im, $asset . '/leea-logo.png', 105, 45, 185, 125);
    $put($im, certificate_runtime_asset('logo.png'), 1370, 45, 220, 125);
    $roundedBox($im, 315, 65, 1305, 155, 18, '#FFFFFF', $line);
    $text($im, 'JUVA-OIL SERVICES (NIG) LIMITED', 810, 125, 38, $bold, 'center');
    $text($im, 'CERTIFICATE OF THOROUGH EXAMINATION', 827, 210, 30, $bold, 'center');
    $status = strtoupper((string) ($p['status'] ?? 'VALID'));
    $statusColor = $status === 'REVOKED' ? '#9B1C1C' : ($status === 'EXPIRED' ? '#8A5600' : '#198754');
    imagefilledrectangle($im, 1364, 180, 1541, 240, certificate_color($im, $statusColor));
    certificate_draw_text($im, $status, 1452, 219, 21, $statusColor, $bold, 'center');
    $reg = [
        "This report complies with the Lifting Equipment Engineers Association's technical requirements",
        'The Lifting Operations and Lifting Equipment Regulations 1998 SI NO.2307',
        'The Supply of Machinery (Safety) Regulation 1992 SI NO.3073',
        'The Provision and Use of Work Equipment Regulations 1998 SI NO.2306',
        'Nigeria Factories Act CAP F1 LFN, 2004',
    ];
    foreach ($reg as $i => $r) { $text($im, $r, 827, 245 + ($i * 22), 14, $font, 'center'); }

    $x = 105; $y = 345; $w = 1444;
    $meta = [$w / 3, $w / 3, $w - (2 * (int) ($w / 3))];
    $cx = $x;
    foreach ([
        "DATE OF THOROUGH EXAMINATION\n" . flat_webbing_sling_date((string) $p['examination_date']),
        "DATE OF REPORT\n" . flat_webbing_sling_date((string) $p['report_date']),
        "CERTIFICATE NUMBER\n" . (string) $p['certificate_number'],
    ] as $i => $value) { $cw = (int) $meta[$i]; $cell($im, $cx, $y, $cw, 78, $value, 18, $bold, 'center'); $cx += $cw; }
    $y += 78;
    $cell($im, $x, $y, 722, 105, "NAME AND ADDRESS OF EMPLOYER FOR WHOM THE THOROUGH EXAMINATION WAS MADE:\n" . strtoupper((string) $p['client_name']) . "\n" . strtoupper((string) $p['client_address']), 18, $font);
    $cell($im, $x + 722, $y, 722, 105, "ADDRESS OF PREMISES WHERE EXAMINATION WAS MADE\n" . strtoupper((string) $p['inspection_location']), 18, $font);
    $y += 105;
    $leftW = 930; $sideW = 171;
    $equipment = "EQUIPMENT: " . strtoupper($field('equipment_type', (string) $p['equipment_name'])) . "\nDESCRIPTION: " . strtoupper($field('equipment_description')) . "\nLENGTH: " . strtoupper($field('sling_length')) . "    WIDTH: " . strtoupper($field('sling_width')) . "    PLY: " . strtoupper($field('number_of_plies')) . "\nMANUFACTURER: " . strtoupper($field('manufacturer')) . "\nIDENTIFICATION NUMBER: " . strtoupper($field('identification_number', (string) $p['asset_code'])) . "\nASSET NUMBER: " . strtoupper($field('asset_number')) . "\nSTANDARD: " . strtoupper($field('standard'));
    imagerectangle($im, $x, $y, $x + $leftW, $y + 205, certificate_color($im, $line));
    $equipmentLines = explode("\n", $equipment);
    $equipmentTitle = array_shift($equipmentLines);
    $fit($im, $equipmentTitle, $x + 8, $y + 7, $leftW - 16, 42, 25, $bold);
    $fit($im, implode("\n", $equipmentLines), $x + 8, $y + 50, $leftW - 16, 147, 18, $font);
    $cell($im, $x + $leftW, $y, $sideW, 205, "SAFE WORKING LOAD\n\n" . strtoupper($field('safe_working_load')), 18, $bold, 'center');
    $cell($im, $x + $leftW + $sideW, $y, $sideW, 205, "DATE OF MANUFACTURE\n\n" . flat_webbing_sling_date($field('date_of_manufacture')), 16, $font, 'center');
    $cell($im, $x + $leftW + ($sideW * 2), $y, $w - $leftW - ($sideW * 2), 205, "DATE OF LAST THOROUGH EXAMINATION\n\n" . flat_webbing_sling_date($field('last_thorough_examination_date')), 15, $font, 'center');
    $y += 205;
    $checkY = $y;
    $half = (int) ($w / 2);
    $yn($im, $x, $checkY, $half, 116, 'Is this the first examination after installation or assembly at a new site or location?', $field('first_examination_after_installation'));
    $yn($im, $x, $checkY + 116, $half, 116, 'If yes, has the equipment been installed correctly?', $field('installed_correctly'));
    $yn($im, $x + $half, $checkY, $w - $half, 58, 'Was the examination carried out within an interval of 6 months?', $field('examined_within_six_months'));
    $yn($im, $x + $half, $checkY + 58, $w - $half, 58, 'Was the examination carried out within an interval of 12 months?', $field('examined_within_twelve_months'));
    $yn($im, $x + $half, $checkY + 116, $w - $half, 58, 'Was the examination carried out in accordance with an examination scheme?', $field('examined_under_scheme'));
    $yn($im, $x + $half, $checkY + 174, $w - $half, 58, 'Was the examination carried out after exceptional circumstances?', $field('examined_after_exceptional_circumstances'));
    $y += 232;

    $cell($im, $x, $y, 440, 88, 'IDENTIFICATION AND DESCRIPTION OF DEFECTS', 17, $bold);
    $cell($im, $x + 440, $y, $w - 440, 88, $field('defect_description', 'NONE'), 18, $font); $y += 88;
    $yn($im, $x, $y, $w, 58, 'Is the above a defect which is of immediate danger to persons?', $field('defect_immediate_danger')); $y += 58;
    $yn($im, $x, $y, $w, 58, 'Is the defect not yet dangerous but could become a danger to persons?', $field('defect_future_danger')); $y += 58;
    $cell($im, $x, $y, 650, 60, 'DATE BY WHICH CORRECTIVE ACTION MUST BE COMPLETED', 17, $bold);
    $cell($im, $x + 650, $y, $w - 650, 60, flat_webbing_sling_date($field('action_due_date')), 18, $font, 'center'); $y += 60;
    foreach ([
        ['REPAIR, RENEWAL OR ALTERATION REQUIRED', 'repair_required', 72],
        ['TESTS CARRIED OUT', 'tests_carried_out', 72],
        ['OBSERVATIONS / ADDITIONAL COMMENTS', 'observations', 82],
    ] as $row) {
        $cell($im, $x, $y, 440, $row[2], $row[0], 17, $bold);
        $cell($im, $x + 440, $y, $w - 440, $row[2], $field($row[1], 'NONE'), 18, $font);
        $y += $row[2];
    }
    $yn($im, $x, $y, $w, 64, 'IS THIS EQUIPMENT FIT FOR PURPOSE?', $field('fit_for_purpose')); $y += 64;    $signW = (int) ($w / 3);
    $cell($im, $x, $y, $signW, 125, "INSPECTOR\n" . strtoupper((string) $p['inspector_name']) . "\n" . strtoupper((string) $p['inspector_qualification']) . "\nSIGNATURE:", 16, $font);
    $cell($im, $x + $signW, $y, $signW, 125, "AUTHENTICATOR\n" . strtoupper($field('authenticator_name')) . "\n" . strtoupper($field('authenticator_qualification')) . "\nSIGNATURE:", 16, $font);
    imagefilledrectangle($im, $x + ($signW * 2), $y, $x + $w, $y + 125, certificate_color($im, '#FDECEC'));
    $cell($im, $x + ($signW * 2), $y, $w - ($signW * 2), 125, "NEXT THOROUGH EXAMINATION DATE\n\n" . flat_webbing_sling_date((string) $p['next_examination_date']), 18, $bold, 'center');
    $authentication = is_array($p['authentication'] ?? null) ? $p['authentication'] : [];
    if (!empty($authentication['inspector_signature_path'])) { $put($im, (string) $authentication['inspector_signature_path'], $x + 250, $y + 60, 185, 55); }
    if (!empty($authentication['authenticator_signature_path'])) { $put($im, (string) $authentication['authenticator_signature_path'], $x + $signW + 250, $y + 60, 185, 55); }
    $y += 125;
    $company = $p['company'];
    $cell($im, $x, $y, $w, 105, strtoupper((string) $company['name']) . "\nREGISTERED: " . (string) $company['registered'] . " | OPERATIONAL: " . (string) $company['operational'] . "\nPHONE: " . (string) $company['phone'] . ' | EMAIL: ' . (string) $company['email'] . ' | WEBSITE: ' . (string) $company['website'], 15, $font);
    if (!empty($authentication['company_stamp_path'])) { $put($im, (string) $authentication['company_stamp_path'], $x + $w - 220, $y + 10, 190, 82); }
    $y += 120;
    $put($im, $asset . '/bsi-logo.png', $x, $y, 125, 80);
    $put($im, $asset . '/leea-logo.png', $x + 145, $y, 115, 80);
    $put($im, $asset . '/asnt-logo.png', $x + 280, $y, 145, 80);

    if (class_exists('QRCode')) {
        $qr = QRCode::getMinimumQRCode((string) $p['verification_url'], QR_ERROR_CORRECT_LEVEL_M);
        $count = (int) $qr->getModuleCount(); $module = 6; $quiet = 5; $size = ($count + 8) * $module;
        $boxPad = 18; $boxW = $size + ($boxPad * 2); $boxH = $size + 92;
        $qx = $x + $w - $boxW + $boxPad; $qy = $y - 12 + $boxPad;
        imagefilledrectangle($im, $qx - $boxPad, $qy - $boxPad, $qx - $boxPad + $boxW, $qy - $boxPad + $boxH, certificate_color($im, '#FFFFFF'));
        imagerectangle($im, $qx - $boxPad, $qy - $boxPad, $qx - $boxPad + $boxW, $qy - $boxPad + $boxH, certificate_color($im, '#555555'));
        imagefilledrectangle($im, $qx, $qy, $qx + $size, $qy + $size, certificate_color($im, '#FFFFFF'));
        for ($r = 0; $r < $count; $r++) { for ($c = 0; $c < $count; $c++) { if ($qr->isDark($r, $c)) { imagefilledrectangle($im, $qx + (($c + $quiet) * $module), $qy + (($r + $quiet) * $module), $qx + (($c + $quiet + 1) * $module) - 1, $qy + (($r + $quiet + 1) * $module) - 1, certificate_color($im, '#000000')); } } }
        $text($im, 'SCAN TO VERIFY AUTHENTICITY', $qx + (int) ($size / 2), $qy + $size + 25, 16, $bold, 'center');
        $text($im, 'Secure live certificate record', $qx + (int) ($size / 2), $qy + $size + 50, 13, $font, 'center');
        $text($im, 'cert.juvaoil.com', $qx + (int) ($size / 2), $qy + $size + 73, 12, $bold, 'center');
    }
    if ($y + 190 > 2295) { throw new RuntimeException('Flat Webbing Sling certificate content exceeds one A4 page.'); }
    $jpg = tempnam(sys_get_temp_dir(), 'juva-fws-');
    if ($jpg === false) { throw new RuntimeException('Unable to create Flat Webbing Sling render workspace.'); }
    imagejpeg($im, $jpg, 94);
    imagedestroy($im);
    certificate_create_jpeg_pdf($path, $jpg);
}
