<?php

declare(strict_types=1);

function chain_block_field_map(array $rows): array
{
    $map = [];
    foreach ($rows as $row) {
        $map[(string) ($row['field_key'] ?? '')] = trim((string) ($row['value_text'] ?? ''));
    }
    return $map;
}

function chain_block_readiness(array $inspection, array $rows, string $number, string $issuedAt, string $expiry, array $attachments = [], bool $requireEvidence = false, int $minimumEvidenceFiles = 0): array
{
    $fields = chain_block_field_map($rows);
    $missing = [];
    $errors = [];
    foreach ($rows as $row) {
        if ((int) ($row['is_required'] ?? 0) === 1 && trim((string) ($row['value_text'] ?? '')) === '') {
            $missing[] = ['field_key' => (string) $row['field_key'], 'label' => (string) $row['label'], 'section' => (string) ($row['pdf_section'] ?? '')];
        }
    }
    foreach ([
        'certificate_number' => ['Certificate Number', $number],
        'inspection_date' => ['Date of Thorough Examination', (string) ($inspection['inspection_date'] ?? '')],
        'next_due_date' => ['Next Thorough Examination Date', (string) ($inspection['next_due_date'] ?? '')],
    ] as $key => $item) {
        if (trim($item[1]) === '') {
            $missing[] = ['field_key' => $key, 'label' => $item[0], 'section' => 'certificate'];
        }
    }
    $exam = (string) ($inspection['inspection_date'] ?? '');
    if ($exam !== '' && !valid_iso_date($exam)) {
        $errors['inspection_date'] = 'Date of Thorough Examination must be valid.';
    }
    if (!valid_iso_date($expiry) || ($exam !== '' && $expiry <= $exam)) {
        $errors['next_due_date'] = 'Next Thorough Examination Date must be after Date of Thorough Examination.';
    }
    foreach (['first_examination_new_site','installed_correctly','exam_within_6_months','exam_within_12_months','exam_scheme','exceptional_circumstances','defect_immediate_danger','defect_future_danger','fit_for_purpose'] as $key) {
        if (isset($fields[$key]) && $fields[$key] !== '' && !in_array(strtolower($fields[$key]), ['yes','no'], true)) {
            $errors[$key] = ucwords(str_replace('_', ' ', $key)) . ' must be Yes or No.';
        }
    }
    $defects = strtoupper(trim((string) ($fields['defect_description'] ?? '')));
    if ($defects !== '' && !in_array($defects, ['NONE','NIL','N/A'], true)) {
        if (($fields['defect_immediate_danger'] ?? '') === '' || ($fields['defect_future_danger'] ?? '') === '') {
            $errors['defect_status'] = 'Select both defect danger answers when defects are recorded.';
        }
    }
    $evidenceCount = 0;
    foreach ($attachments as $attachment) {
        if ((string) ($attachment['attachment_type'] ?? 'evidence') === 'evidence') { $evidenceCount++; }
    }
    $requiredEvidenceCount = $requireEvidence ? max(1, $minimumEvidenceFiles) : 0;
    if ($evidenceCount < $requiredEvidenceCount) {
        $missing[] = ['field_key' => 'inspection_evidence', 'label' => 'Inspection Evidence Attachment (' . $requiredEvidenceCount . ' required)', 'section' => 'evidence'];
    }    return ['ready' => !$missing && !$errors, 'missing_fields' => $missing, 'missing_sections' => [], 'validation_errors' => $errors, 'warnings' => [], 'inspection_id' => (int) ($inspection['id'] ?? 0)];
}

function chain_block_validate(array $inspection, array $rows, string $number, string $issuedAt, string $expiry, array $attachments = [], bool $requireEvidence = false, int $minimumEvidenceFiles = 0): void
{
    $result = chain_block_readiness($inspection, $rows, $number, $issuedAt, $expiry, $attachments, $requireEvidence, $minimumEvidenceFiles);
    if ($result['ready']) {
        return;
    }
    $labels = array_map(static function (array $field): string { return (string) $field['label']; }, $result['missing_fields']);
    $message = $labels ? "Chain Block certificate cannot be issued. Complete:\n- " . implode("\n- ", $labels) : 'Chain Block certificate cannot be issued. Correct the validation errors shown.';
    throw new CertificateIssueException($message, 422, $result);
}

function chain_block_payload(array $inspection, array $rows, string $number, int $revision, string $verifyUrl, string $issuedAt, string $expiry): array
{
    global $config;
    $fields = chain_block_field_map($rows);
    return [
        'fields' => $fields,
        'certificate_number' => $number,
        'revision' => $revision,
        'verification_url' => $verifyUrl,
        'examination_date' => (string) ($inspection['inspection_date'] ?? ''),
        'report_date' => ($fields['report_date'] ?? '') ?: $issuedAt,
        'next_examination_date' => (string) ($inspection['next_due_date'] ?? $expiry),
        'client_name' => (string) ($inspection['client_name'] ?? ''),
        'client_address' => (string) ($inspection['client_address'] ?? ''),
        'equipment_name' => (string) ($inspection['equipment_name'] ?? 'CHAIN BLOCK'),
        'asset_code' => (string) ($inspection['asset_code'] ?? ''),
        'inspector_name' => ($fields['inspector_name_snapshot'] ?? '') ?: (string) ($inspection['inspector_name'] ?? ''),
        'inspector_qualification' => ($fields['inspector_qualification_snapshot'] ?? '') ?: (string) ($inspection['inspector_qualification'] ?? ''),
        'company' => [
            'name' => (string) $config['company_name'],
            'registered' => (string) $config['company_registered_address'],
            'operational' => (string) $config['company_operational_address'],
            'phone' => (string) $config['company_phone'],
            'email' => (string) $config['company_email'],
            'website' => (string) $config['company_website'],
        ],
    ];
}

function chain_block_date(string $value): string
{
    $time = strtotime($value);
    return $time === false ? $value : date('d/m/Y', $time);
}

function chain_block_render_certificate_pdf(string $path, array $p): void
{
    $im = imagecreatetruecolor(1654, 2339);
    imagefilledrectangle($im, 0, 0, 1654, 2339, certificate_color($im, '#FFFFFF'));
    $font = certificate_find_font(false);
    $bold = certificate_find_font(true) ?: $font;
    $ink = '#242424';
    $line = '#333333';
    $asset = certificate_runtime_asset('assets/certificates/chain-block');
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
    $tick = static function ($im, int $x, int $y, int $w, int $h): void {
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

    $put($im, certificate_runtime_asset('logo.png'), 50, 25, 330, 170);
    $put($im, $asset . '/leea-logo.png', 1360, 60, 175, 105);
    imagefilledrectangle($im, 400, 65, 1250, 155, certificate_color($im, '#FFFFFF'));
    imagerectangle($im, 400, 65, 1250, 155, certificate_color($im, $line));
    $text($im, 'JUVA-OIL SERVICES (NIG) LIMITED', 810, 125, 38, $bold, 'center');
    $text($im, 'CERTIFICATE OF THOROUGH EXAMINATION', 827, 210, 30, $bold, 'center');
    $status = strtoupper((string) ($p['status'] ?? 'VALID'));
    certificate_draw_status_badge($im, $status, 1364, 180, 177, 60, $bold, 21);
    $reg = [
        "This report complies with the Lifting Equipment Engineers Association's technical requirements",
        'Lifting Operations and Lifting Equipment Regulations 1998; Supply of Machinery (Safety) Regulations 1992',
        'Provision and Use of Work Equipment Regulations 1998; Nigeria Factories Act CAP F1 LFN, 2004',
    ];
    foreach ($reg as $i => $r) { $text($im, $r, 827, 245 + ($i * 25), 16, $font, 'center'); }

    $x = 105; $y = 345; $w = 1444;
    $meta = [$w / 3, $w / 3, $w - (2 * (int) ($w / 3))];
    $cx = $x;
    foreach ([
        "DATE OF THOROUGH EXAMINATION\n" . chain_block_date((string) $p['examination_date']),
        "DATE OF REPORT\n" . chain_block_date((string) $p['report_date']),
        "CERTIFICATE NUMBER\n" . (string) $p['certificate_number'],
    ] as $i => $value) { $cw = (int) $meta[$i]; $cell($im, $cx, $y, $cw, 78, $value, 18, $bold, 'center'); $cx += $cw; }
    $y += 78;
    $cell($im, $x, $y, 722, 105, "NAME AND ADDRESS OF EMPLOYER FOR WHOM THE THOROUGH EXAMINATION WAS MADE:\n" . strtoupper((string) $p['client_name']) . "\n" . strtoupper((string) $p['client_address']), 18, $font);
    $cell($im, $x + 722, $y, 722, 105, "ADDRESS OF PREMISES WHERE EXAMINATION WAS MADE\n" . strtoupper($field('premises_address')), 18, $font);
    $y += 105;
    $leftW = 930; $sideW = 171;
    $equipment = "EQUIPMENT: " . strtoupper($field('equipment_type', (string) $p['equipment_name'])) . "\nDESCRIPTION: " . strtoupper($field('equipment_description')) . "\nMANUFACTURER: " . strtoupper($field('manufacturer')) . "\nID NUMBER: " . strtoupper($field('equipment_id_number', (string) $p['asset_code'])) . "\nASSET NUMBER: " . strtoupper($field('asset_number')) . "\nCHAIN DIMENSIONS: " . strtoupper($field('chain_dimensions')) . "\nSTANDARD: " . strtoupper($field('standard'));
    imagerectangle($im, $x, $y, $x + $leftW, $y + 205, certificate_color($im, $line));
    $equipmentLines = explode("\n", $equipment);
    $equipmentTitle = array_shift($equipmentLines);
    $fit($im, $equipmentTitle, $x + 8, $y + 7, $leftW - 16, 42, 25, $bold);
    $fit($im, implode("\n", $equipmentLines), $x + 8, $y + 50, $leftW - 16, 147, 18, $font);
    $cell($im, $x + $leftW, $y, $sideW, 205, "SAFE WORKING LOAD\n\n" . strtoupper($field('safe_working_load')), 18, $bold, 'center');
    $cell($im, $x + $leftW + $sideW, $y, $sideW, 205, "DATE OF MANUFACTURE\n\n" . chain_block_date($field('date_of_manufacture')), 16, $font, 'center');
    $cell($im, $x + $leftW + ($sideW * 2), $y, $w - $leftW - ($sideW * 2), 205, "DATE OF LAST THOROUGH EXAMINATION\n\n" . chain_block_date($field('date_of_last_examination')), 15, $font, 'center');
    $y += 205;
    $questions = [
        ['first_examination_new_site','Is this the first examination after installation or assembly at a new site or location?'],
        ['installed_correctly','If yes, has the equipment been installed correctly?'],
        ['exam_within_6_months','Was the examination carried out within an interval of 6 months?'],
        ['exam_within_12_months','Was the examination carried out within an interval of 12 months?'],
        ['exam_scheme','Was the examination carried out in accordance with an examination scheme?'],
        ['exceptional_circumstances','Was the examination carried out after exceptional circumstances?'],
    ];
    foreach ($questions as $q) { $yn($im, $x, $y, $w, 58, $q[1], $field($q[0])); $y += 58; }
    $rows = [
        ['IDENTIFICATION AND DESCRIPTION OF DEFECTS', 'defect_description', 88],
        ['REPAIR, RENEWAL OR ALTERATION REQUIRED', 'repair_required', 72],
        ['TESTS CARRIED OUT', 'tests_carried_out', 72],
        ['OBSERVATIONS / ADDITIONAL COMMENTS', 'observations', 82],
    ];
    foreach ($rows as $r) { $cell($im, $x, $y, 440, $r[2], $r[0], 17, $bold); $cell($im, $x + 440, $y, $w - 440, $r[2], $field($r[1], 'NONE'), 18, $font); $y += $r[2]; }
    $yn($im, $x, $y, $w, 58, 'Is the defect of immediate danger?', $field('defect_immediate_danger')); $y += 58;
    $yn($im, $x, $y, $w, 58, 'Is the defect not yet dangerous but could become dangerous?', $field('defect_future_danger')); $y += 58;
    $cell($im, $x, $y, 650, 60, 'DATE BY WHICH ACTION MUST BE TAKEN', 17, $bold);
    $cell($im, $x + 650, $y, $w - 650, 60, chain_block_date($field('action_due_date')), 18, $font, 'center'); $y += 60;
    $yn($im, $x, $y, $w, 64, 'Is this equipment fit for purpose?', $field('fit_for_purpose')); $y += 64;
    $signW = (int) ($w / 3);
    $cell($im, $x, $y, $signW, 125, "INSPECTOR\n" . strtoupper((string) $p['inspector_name']) . "\n" . strtoupper((string) $p['inspector_qualification']) . "\nSIGNATURE: " . strtoupper($field('inspector_signature_name')), 16, $font);
    $cell($im, $x + $signW, $y, $signW, 125, "AUTHENTICATOR\n" . strtoupper($field('authenticator_name')) . "\n" . strtoupper($field('authenticator_qualification')) . "\nSIGNATURE: " . strtoupper($field('authenticator_signature_name')), 16, $font);
    imagefilledrectangle($im, $x + ($signW * 2), $y, $x + $w, $y + 125, certificate_color($im, '#FDECEC'));
    $cell($im, $x + ($signW * 2), $y, $w - ($signW * 2), 125, "NEXT THOROUGH EXAMINATION DATE\n\n" . chain_block_date((string) $p['next_examination_date']), 18, $bold, 'center');
    $authentication = is_array($p['authentication'] ?? null) ? $p['authentication'] : [];
    if (!empty($authentication['inspector_signature_path'])) { $put($im, (string) $authentication['inspector_signature_path'], $x + 250, $y + 60, 185, 55); }
    if (!empty($authentication['authenticator_signature_path'])) { $put($im, (string) $authentication['authenticator_signature_path'], $x + $signW + 250, $y + 60, 185, 55); }
    $y += 125;
    $company = $p['company'];
    $cell($im, $x, $y, $w, 105, strtoupper((string) $company['name']) . "\nREGISTERED: " . (string) $company['registered'] . " | OPERATIONAL: " . (string) $company['operational'] . "\nPHONE: " . (string) $company['phone'] . ' | EMAIL: ' . (string) $company['email'] . ' | WEBSITE: ' . (string) $company['website'], 15, $font);
    if (!empty($authentication['company_stamp_path'])) { $put($im, (string) $authentication['company_stamp_path'], $x + $w - 220, $y + 10, 190, 82); }
    $y += 120;
    $put($im, $asset . '/bsi-logo.png', $x, $y, 125, 80);
    $put($im, $asset . '/asnt-logo.png', $x + 135, $y, 125, 80);
    $put($im, $asset . '/acebi-logo.png', $x + 280, $y, 145, 80);

    if (class_exists('QRCode')) {
        $qr = QRCode::getMinimumQRCode((string) $p['verification_url'], QR_ERROR_CORRECT_LEVEL_M);
        $count = (int) $qr->getModuleCount(); $module = 6; $quiet = 5; $size = ($count + 8) * $module;
        $boxPad = 18; $boxW = $size + ($boxPad * 2); $boxH = $size + 92;
        $qx = $x + $w - $boxW + $boxPad; $qy = $y - 12 + $boxPad;
        imagefilledrectangle($im, $qx - $boxPad, $qy - $boxPad, $qx - $boxPad + $boxW, $qy - $boxPad + $boxH, certificate_color($im, '#FFFFFF'));
        imagerectangle($im, $qx - $boxPad, $qy - $boxPad, $qx - $boxPad + $boxW, $qy - $boxPad + $boxH, certificate_color($im, '#555555'));
        imagefilledrectangle($im, $qx, $qy, $qx + $size, $qy + $size, certificate_color($im, '#FFFFFF'));
        for ($r = 0; $r < $count; $r++) { for ($c = 0; $c < $count; $c++) { if ($qr->isDark($r, $c)) { imagefilledrectangle($im, $qx + (($c + $quiet) * $module), $qy + (($r + $quiet) * $module), $qx + (($c + $quiet + 1) * $module) - 1, $qy + (($r + $quiet + 1) * $module) - 1, certificate_color($im, '#000000')); } } }
        $text($im, 'VERIFY WITH INTEGRITY', $qx + (int) ($size / 2), $qy + $size + 25, 16, $bold, 'center');
        $text($im, 'Scan to verify JUVA Oil live records', $qx + (int) ($size / 2), $qy + $size + 50, 13, $font, 'center');
        $text($im, 'cert.juvaoil.com', $qx + (int) ($size / 2), $qy + $size + 73, 12, $bold, 'center');
    }
    if ($y + 190 > 2295) { throw new RuntimeException('Chain Block certificate content exceeds one A4 page.'); }
    $jpg = preg_replace('/\.pdf$/i', '.jpg', $path) ?: $path . '.jpg';
    imagejpeg($im, $jpg, 94);
    imagedestroy($im);
    certificate_create_jpeg_pdf($path, $jpg);
}
