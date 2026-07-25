<?php

declare(strict_types=1);

function ccu_field_map(array $fieldRows): array
{
    $map = [];
    foreach ($fieldRows as $row) {
        if (!isset($row['field_key'])) {
            continue;
        }
        $map[(string) $row['field_key']] = trim((string) ($row['value_text'] ?? ''));
    }
    return $map;
}

function ccu_item_groups(array $itemRows): array
{
    $groups = [];
    foreach ($itemRows as $row) {
        $section = (string) ($row['section_key'] ?? '');
        $index = (int) ($row['row_index'] ?? 0);
        $column = (string) ($row['column_key'] ?? '');
        if ($section === '' || $column === '') {
            continue;
        }
        if (!isset($groups[$section])) {
            $groups[$section] = [];
        }
        if (!isset($groups[$section][$index])) {
            $groups[$section][$index] = [];
        }
        $groups[$section][$index][$column] = trim((string) ($row['value_text'] ?? ''));
    }
    foreach ($groups as $section => $rows) {
        ksort($rows);
        $groups[$section] = array_values($rows);
    }
    return $groups;
}

function ccu_value(array $payload, string $key, string $fallback = ''): string
{
    $value = trim((string) ($payload['ccu_fields'][$key] ?? ''));
    return $value !== '' ? $value : $fallback;
}

function ccu_positive_or_na(string $value): bool
{
    $value = trim(strtoupper($value));
    if ($value === '' || in_array($value, ['N/A', 'NA', '-', 'NIL'], true)) {
        return true;
    }
    $number = preg_replace('/[^0-9.]/', '', $value);
    return $number !== '' && is_numeric($number) && (float) $number > 0;
}

function ccu_certificate_readiness(array $inspection, array $fieldRows, array $itemRows, string $certificateNumber, string $issuedAt, string $expiry): array
{
    $fields = ccu_field_map($fieldRows);
    $items = ccu_item_groups($itemRows);
    $missingFields = [];
    $missingSections = [];
    $validationErrors = [];
    foreach ($fieldRows as $field) {
        if ((int) ($field['is_required'] ?? 0) === 1 && trim((string) ($field['value_text'] ?? '')) === '') {
            $missingFields[] = ['field_key' => (string) $field['field_key'], 'label' => (string) $field['label'], 'section' => (string) ($field['pdf_section'] ?? '')];
        }
    }
    if (trim($certificateNumber) === '') {
        $missingFields[] = ['field_key' => 'certificate_number', 'label' => 'Certificate Number', 'section' => 'metadata'];
    }
    $examDate = (string) ($inspection['inspection_date'] ?? $issuedAt);
    if (!valid_iso_date($examDate)) {
        $validationErrors['inspection_date'] = 'Date of Examination must be valid.';
    }
    if (!valid_iso_date($expiry) || $expiry <= $examDate) {
        $validationErrors['next_due_date'] = 'Next Due Date must be after Date of Examination.';
    }
    foreach (['unit_quantity', 'sling_quantity'] as $key) {
        if (($fields[$key] ?? '') !== '' && (!is_numeric($fields[$key]) || (float) $fields[$key] <= 0)) {
            $validationErrors[$key] = ucwords(str_replace('_', ' ', $key)) . ' must be numeric and greater than zero.';
        }
    }
    foreach (['unit_tare_weight', 'unit_swl_payload', 'unit_gross_weight', 'sling_tare_na', 'sling_swl', 'sling_gross_na'] as $key) {
        if (($fields[$key] ?? '') !== '' && !ccu_positive_or_na((string) $fields[$key])) {
            $validationErrors[$key] = 'Enter a positive value or N/A.';
        }
    }
    $shackles = $items['shackle_details'] ?? [];
    if (!$shackles) {
        $missingSections[] = ['section_key' => 'shackle_details', 'label' => 'Shackle Details'];
    }
    foreach ($shackles as $index => $row) {
        foreach (['shackle_id' => 'Shackle ID', 'quantity' => 'Quantity', 'quantity_unit' => 'Quantity Unit', 'shackle_description' => 'Shackle Description', 'swl' => 'SWL'] as $key => $label) {
            if (trim((string) ($row[$key] ?? '')) === '') {
                $missingFields[] = ['field_key' => 'shackle_details.' . $index . '.' . $key, 'label' => 'Shackle row ' . ($index + 1) . ': ' . $label, 'section' => 'shackle_details'];
            }
        }
        if (($row['quantity'] ?? '') !== '' && (!is_numeric((string) $row['quantity']) || (float) $row['quantity'] <= 0)) {
            $validationErrors['shackle_' . $index . '_quantity'] = 'Shackle row ' . ($index + 1) . ' quantity must be greater than zero.';
        }
    }
    return ['ready' => !$missingFields && !$missingSections && !$validationErrors, 'missing_fields' => $missingFields, 'missing_sections' => $missingSections, 'validation_errors' => $validationErrors, 'warnings' => []];
}

function ccu_validate_payload(array $inspection, array $fieldRows, array $itemRows, string $certificateNumber, string $issuedAt, string $expiry): void
{
    $readiness = ccu_certificate_readiness($inspection, $fieldRows, $itemRows, $certificateNumber, $issuedAt, $expiry);
    if (!$readiness['ready']) {
        $labels = array_map(static function (array $field): string { return (string) $field['label']; }, $readiness['missing_fields']);
        $labels = array_merge($labels, array_map(static function (array $section): string { return (string) $section['label']; }, $readiness['missing_sections']));
        $message = $labels ? 'Certificate cannot be issued. Complete the following fields: ' . implode(', ', $labels) . '.' : 'Certificate cannot be issued. Correct the validation errors shown.';
        throw new CertificateIssueException($message, 422, ['missing_fields' => $readiness['missing_fields'], 'missing_sections' => $readiness['missing_sections'], 'validation_errors' => $readiness['validation_errors']]);
    }
}
function ccu_payload(array $inspection, array $fieldRows, array $itemRows, string $certificateNumber, int $revision, string $verifyUrl, string $issuedAt, string $expiry, string $fingerprint): array
{
    $fields = ccu_field_map($fieldRows);
    $items = ccu_item_groups($itemRows);
    if (trim((string) ($fields['client_name'] ?? '')) === '') { $fields['client_name'] = (string) ($inspection['client_name'] ?? ''); }
    if (trim((string) ($fields['client_address'] ?? '')) === '') { $fields['client_address'] = (string) ($inspection['client_address'] ?? ''); }
    if (trim((string) ($fields['inspector_name'] ?? '')) === '') { $fields['inspector_name'] = (string) ($inspection['inspector_name'] ?? ''); }
    if (trim((string) ($fields['inspector_qualifications'] ?? '')) === '') { $fields['inspector_qualifications'] = (string) ($inspection['inspector_qualification'] ?? ''); }
    global $config;
    return [
        'ccu_fields' => $fields,
        'ccu_items' => $items,
        'certificate_number' => $certificateNumber,
        'revision' => $revision,
        'verification_url' => $verifyUrl,
        'verification_code' => strtoupper(substr($fingerprint, 0, 8)) . '-' . strtoupper(substr($fingerprint, 8, 8)),
        'issue_date' => (string) ($inspection['inspection_date'] ?? $issuedAt),
        'expiry_date' => (string) ($inspection['next_due_date'] ?? $expiry),
        'client_name' => ccu_value(['ccu_fields' => $fields], 'client_name', (string) $inspection['client_name']),
        'client_address' => ccu_value(['ccu_fields' => $fields], 'client_address'),
        'location' => trim(ccu_value(['ccu_fields' => $fields], 'location_line_1') . "\n" . ccu_value(['ccu_fields' => $fields], 'location_line_2')),
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

function ccu_draw_text_fit($image, string $text, int $x, int $y, int $w, int $h, int $size, string $hex, ?string $font, bool $bold = false, string $align = 'left'): void
{
    $text = trim($text);
    if ($text === '') {
        $text = '-';
    }
    $min = 13;
    $paddingX = 7;
    $paddingY = 5;
    do {
        $lines = certificate_wrap_text($text, $w - ($paddingX * 2), $size, $font);
        $lineHeight = (int) ceil($size * 1.16);
        if (count($lines) * $lineHeight <= $h - ($paddingY * 2) || $size <= $min) {
            break;
        }
        $size--;
    } while (true);
    $lines = array_slice($lines ?: ['-'], 0, max(1, (int) floor(($h - ($paddingY * 2)) / max(1, $lineHeight))));
    $textHeight = count($lines) * $lineHeight;
    $cursor = $y + max($paddingY, (int) floor(($h - $textHeight) / 2)) + $size;
    foreach ($lines as $line) {
        $drawX = $x + $paddingX;
        if ($align === 'center') {
            $drawX = $x + (int) floor($w / 2);
        } elseif ($align === 'right') {
            $drawX = $x + $w - $paddingX;
        }
        certificate_draw_text($image, $line, $drawX, $cursor, $size, $hex, $font, $align);
        $cursor += $lineHeight;
    }
}

function ccu_display_date(string $value): string
{
    $timestamp = strtotime($value);
    return $timestamp === false ? $value : date('d/m/Y', $timestamp);
}

function ccu_cell($image, int $x, int $y, int $w, int $h, string $text, ?string $font, ?string $boldFont, bool $header = false, string $align = 'left'): void
{
    $bg = $header ? '#EEF2FF' : '#FFFFFF';
    imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, certificate_color($image, $bg));
    imagerectangle($image, $x, $y, $x + $w, $y + $h, certificate_color($image, '#111111'));
    ccu_draw_text_fit($image, $text, $x, $y, $w, $h, $header ? 10 : 11, '#111111', $header ? $boldFont : $font, $header, $align);
}

function ccu_draw_row($image, array $widths, int $x, int $y, int $h, array $values, ?string $font, ?string $boldFont, bool $header = false): void
{
    $cursor = $x;
    foreach ($widths as $index => $w) {
        ccu_cell($image, $cursor, $y, $w, $h, (string) ($values[$index] ?? ''), $font, $boldFont, $header, $index === 1 ? 'center' : 'left');
        $cursor += $w;
    }
}

function ccu_render_certificate_pdf(string $path, array $payload): void
{
    $width = 1654;
    $height = 2339;
    $canvas = imagecreatetruecolor($width, $height);
    imagefilledrectangle($canvas, 0, 0, $width, $height, certificate_color($canvas, '#FFFFFF'));

    $font = certificate_find_font(false);
    $bold = certificate_find_font(true) ?: $font;
    $ink = '#111111';
    $blue = '#193A7A';
    $muted = '#333333';
    $line = '#111111';
    $assetDir = dirname(__DIR__, 3) . '/public/assets/certificates/ccu';

    $sx = function (float $value): int { return (int) round($value * 1654 / 791); };
    $sy = function (float $value): int { return (int) round($value * 2339 / 1024); };

    $putPng = function (string $file, int $x, int $y, int $w, int $h, int $opacity = 100) use ($canvas): void {
        if (!is_file($file)) {
            return;
        }
        $bytes = is_file($file) ? @file_get_contents($file) : false;
        $img = $bytes !== false ? @imagecreatefromstring($bytes) : false;
        if (!$img) {
            return;
        }
        imagealphablending($img, true);
        imagesavealpha($img, true);
        if ($opacity >= 100) {
            imagecopyresampled($canvas, $img, $x, $y, 0, 0, $w, $h, imagesx($img), imagesy($img));
        } else {
            $tmp = imagecreatetruecolor($w, $h);
            imagefilledrectangle($tmp, 0, 0, $w, $h, certificate_color($tmp, '#FFFFFF'));
            imagecopyresampled($tmp, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
            imagecopymerge($canvas, $tmp, $x, $y, 0, 0, $w, $h, $opacity);
            imagedestroy($tmp);
        }
        imagedestroy($img);
    };

    $drawQr = function (string $data, int $x, int $y, int $size) use ($canvas): void {
        if (!class_exists('QRCode')) {
            return;
        }
        $qr = QRCode::getMinimumQRCode($data, QR_ERROR_CORRECT_LEVEL_M);
        $count = (int) $qr->getModuleCount();
        $quiet = 4;
        $module = max(2, (int) floor($size / ($count + ($quiet * 2))));
        $actual = ($count + ($quiet * 2)) * $module;
        imagefilledrectangle($canvas, $x, $y, $x + $actual, $y + $actual, certificate_color($canvas, '#FFFFFF'));
        imagerectangle($canvas, $x, $y, $x + $actual, $y + $actual, certificate_color($canvas, '#111111'));
        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($qr->isDark($r, $c)) {
                    $px = $x + (($c + $quiet) * $module);
                    $py = $y + (($r + $quiet) * $module);
                    imagefilledrectangle($canvas, $px, $py, $px + $module - 1, $py + $module - 1, certificate_color($canvas, '#000000'));
                }
            }
        }
    };

    $cell = function (int $x, int $y, int $w, int $h, string $text, int $size = 17, bool $isBold = false, string $align = 'left') use ($canvas, $font, $bold, $ink, $line): void {
        imagerectangle($canvas, $x, $y, $x + $w, $y + $h, certificate_color($canvas, $line));
        ccu_draw_text_fit($canvas, $text, $x + 2, $y + 1, $w - 4, $h - 2, $size, $ink, $isBold ? $bold : $font, $isBold, $align);
    };

    $textAt = function (string $text, int $x, int $y, int $size, string $color = '#111111', bool $isBold = false, string $align = 'left') use ($canvas, $font, $bold): void {
        certificate_draw_text($canvas, $text, $x, $y, $size, $color, $isBold ? $bold : $font, $align);
    };

    $watermark = $assetDir . '/juva-oil-watermark.png';
    $putPng($watermark, $sx(150), $sy(260), $sx(500), $sy(500), 100);

    $logoPath = dirname(__DIR__, 3) . '/public/logo.png';
    imagerectangle($canvas, $sx(116), $sy(36), $sx(169), $sy(98), certificate_color($canvas, $line));
    if (is_file($logoPath)) {
        $logoInfo = @getimagesize($logoPath);
        if ($logoInfo) {
            $boxW = $sx(46);
            $boxH = $sy(52);
            $scale = min($boxW / max(1, $logoInfo[0]), $boxH / max(1, $logoInfo[1]));
            $logoW = max(1, (int) round($logoInfo[0] * $scale));
            $logoH = max(1, (int) round($logoInfo[1] * $scale));
            $logoX = $sx(116) + (int) floor(($sx(53) - $logoW) / 2);
            $logoY = $sy(36) + (int) floor(($sy(62) - $logoH) / 2);
            $putPng($logoPath, $logoX, $logoY, $logoW, $logoH, 100);
        }
    }

    imagefilledrectangle($canvas, $sx(176), $sy(48), $sx(584), $sy(79), certificate_color($canvas, '#2B2B2B'));
    imagefilledrectangle($canvas, $sx(171), $sy(42), $sx(579), $sy(73), certificate_color($canvas, '#FFFFFF'));
    imagerectangle($canvas, $sx(171), $sy(42), $sx(579), $sy(73), certificate_color($canvas, $line));
    $textAt((string) $payload['company']['name'], $sx(376), $sy(63), 35, '#2D2D2D', true, 'center');
    $putPng($assetDir . '/leea-logo.png', $sx(596), $sy(47), $sx(61), $sy(46), 100);

    $effectiveStatus = strtoupper((string) ($payload['status'] ?? 'valid'));
    $statusColor = $effectiveStatus === 'REVOKED' ? '#A61B1B' : ($effectiveStatus === 'EXPIRED' ? '#8A5A00' : '#08775E');
    $statusX = $sx(655);
    $statusY = $sy(103);
    $statusW = $sx(52);
    $statusH = $sy(20);
    imagerectangle($canvas, $statusX, $statusY, $statusX + $statusW, $statusY + $statusH, certificate_color($canvas, $statusColor));
    ccu_draw_text_fit($canvas, $effectiveStatus, $statusX, $statusY, $statusW, $statusH, 15, $statusColor, $bold, true, 'center');

    $textAt('CERTIFICATE OF VISUAL EXAMINATION', $sx(396), $sy(108), 28, $ink, true, 'center');
    $regLines = [
        "This report complies with the Lifting Equipment Engineers Association's technical requirements",
        'The Lifting Operations and Lifting Equipment Regulations 1998 STATUTORY INSTRUMENTS 1998 NO.2307',
        'The Supply of Machinery (Safety) Regulation 1992 STATUTORY INSTRUMENTS 1992 NO.3073',
        'The Provision and Use of Work Equipment Regulations 1998 STATUTORY INSTRUMENTS 1998 NO.2306',
    ];
    $regY = 125;
    foreach ($regLines as $lineText) {
        $textAt($lineText, $sx(396), $sy($regY), 14, $ink, false, 'center');
        $regY += 10;
    }

    $x = $sx(93);
    $y = $sy(173);
    $w = $sx(614);
    $clientW = $sx(331);
    $locW = $w - $clientW;
    $cell($x, $y, $clientW, $sy(45), 'CLIENT: ' . strtoupper($payload['client_name']) . "\n" . strtoupper(str_replace("\n", ' ', (string) $payload['client_address'])), 18, true);
    $cell($x + $clientW, $y, $locW, $sy(45), 'LOCATION OF INSPECTION:' . "\n" . strtoupper((string) $payload['location']), 14, true);

    $y += $sy(45);
    $metaW = [$sx(132), $sx(146), $sx(132), $w - $sx(132) - $sx(146) - $sx(132)];
    $cx = $x;
    $meta = [
        'CERTIFICATE NO:' . "\n" . $payload['certificate_number'],
        'CUSTOMER ORDER NO' . "\n" . ccu_value($payload, 'customer_order_number'),
        'DATE OF EXAMINATION' . "\n" . ccu_display_date((string) $payload['issue_date']),
        'NEXT DUE DATE' . "\n" . ccu_display_date((string) $payload['expiry_date']),
    ];
    foreach ($metaW as $i => $cw) {
        $cell($cx, $y, $cw, $sy(31), $meta[$i], 17, $i === 0 || $i === 3, $i >= 2 ? 'center' : 'left');
        $cx += $cw;
    }

    $y += $sy(31);
    $col = [$sx(94), $sx(42), $sx(262), $sx(70), $sx(74), $w - $sx(94) - $sx(42) - $sx(262) - $sx(70) - $sx(74)];
    $cx = $x;
    foreach (['UNIT ID' . "\n" . 'NUMBER', 'QTY', 'DESCRIPTION OF UNIT', 'TARE' . "\n" . 'WEIGHT', 'SWL' . "\n" . '(PAYLOAD)', 'GROSS' . "\n" . 'WEIGHT'] as $i => $heading) {
        $cell($cx, $y, $col[$i], $sy(38), $heading, 17, true, $i === 1 || $i >= 3 ? 'center' : 'left');
        $cx += $col[$i];
    }
    $y += $sy(38);
    $unitH = $sy(58);
    $unitVals = [ccu_value($payload, 'unit_id_number') . "\n\nASSET\nNO:" . ccu_value($payload, 'asset_number'), ccu_value($payload, 'unit_quantity'), ccu_value($payload, 'description_of_unit'), ccu_value($payload, 'unit_tare_weight'), ccu_value($payload, 'unit_swl_payload'), ccu_value($payload, 'unit_gross_weight')];
    $cx = $x;
    foreach ($unitVals as $i => $value) { $cell($cx, $y, $col[$i], $unitH, $value, 18, $i === 2, $i === 1 || $i >= 3 ? 'center' : 'left'); $cx += $col[$i]; }

    $y += $unitH;
    $cx = $x;
    $slingVals = ['SLING ID' . "\n" . ccu_value($payload, 'sling_id'), trim(ccu_value($payload, 'sling_quantity') . "\n" . ccu_value($payload, 'sling_quantity_unit')), 'SLING DETAILS' . "\n" . ccu_value($payload, 'sling_details'), ccu_value($payload, 'sling_tare_na'), ccu_value($payload, 'sling_swl'), ccu_value($payload, 'sling_gross_na')];
    $slingH = $sy(62);
    foreach ($slingVals as $i => $value) { $cell($cx, $y, $col[$i], $slingH, $value, 17, $i === 2, $i === 1 || $i >= 3 ? 'center' : 'left'); $cx += $col[$i]; }

    $y += $slingH;
    $shackles = $payload['ccu_items']['shackle_details'] ?? [];
    $ids = [];
    $description = '';
    $qty = '';
    $tare = 'N/A';
    $swl = '';
    $gross = 'N/A';
    foreach ($shackles as $row) {
        if (!empty($row['shackle_id'])) { $ids[] = (string) $row['shackle_id']; }
        if ($description === '' && !empty($row['shackle_description'])) { $description = (string) $row['shackle_description']; }
        if ($qty === '' && !empty($row['quantity'])) { $qty = trim((string) $row['quantity'] . "\n" . (string) ($row['quantity_unit'] ?? '')); }
        if (!empty($row['tare_na'])) { $tare = (string) $row['tare_na']; }
        if ($swl === '' && !empty($row['swl'])) { $swl = (string) $row['swl']; }
        if (!empty($row['gross_na'])) { $gross = (string) $row['gross_na']; }
    }
    $cx = $x;
    $shackleH = $sy(61);
    $shackleVals = ['SHACKLE ID' . "\n" . implode("\n", array_slice($ids, 0, 5)), $qty, 'SHACKLE DETAILS' . "\n" . $description, $tare, $swl, $gross];
    foreach ($shackleVals as $i => $value) { $cell($cx, $y, $col[$i], $shackleH, $value, 17, $i === 2, $i === 1 || $i >= 3 ? 'center' : 'left'); $cx += $col[$i]; }

    $y += $shackleH + $sy(10);
    $textAt('LOAD TEST DETAILS', $x, $y - $sy(2), 18, $blue, true);
    $y += $sy(15);
    $loadW = [$sx(116), $sx(104), $sx(122), $sx(128), $w - $sx(116) - $sx(104) - $sx(122) - $sx(128)];
    foreach ([['UNIT ID', 'TESTED BY', 'CERTIFICATE NO', 'PROOF LOAD TEST', 'TEST DATE'], [ccu_value($payload, 'load_unit_id'), ccu_value($payload, 'load_tested_by'), ccu_value($payload, 'load_test_certificate_number'), ccu_value($payload, 'load_proof_load_test'), ccu_value($payload, 'load_test_date')], ['SLING ID', 'SLING MANU.', 'OEM CERT NO', '', 'TEST DATE'], [ccu_value($payload, 'sling_test_sling_id'), ccu_value($payload, 'sling_manufacturer'), ccu_value($payload, 'sling_oem_certificate_number'), ccu_value($payload, 'sling_proof_load_test'), ccu_value($payload, 'sling_test_date')]] as $ri => $row) {
        $cx = $x;
        foreach ($loadW as $i => $cw) { $cell($cx, $y, $cw, $sy(15), $row[$i], 15, $ri % 2 === 0, 'center'); $cx += $cw; }
        $y += $sy(15);
    }

    $textAt('INSPECTION DETAILS', $x, $y + $sy(5), 18, $blue, true);
    $y += $sy(18);
    $inspRows = [
        ['STANDARDS, ' . ccu_value($payload, 'standards'), 'EQUIPMENT TYPE:' . ccu_value($payload, 'equipment_type'), 'CONTRAST MEDIA: ' . ccu_value($payload, 'contrast_media')],
        ['TEST PROCEDURE: ' . ccu_value($payload, 'test_procedure'), 'POLE SPACING:' . ccu_value($payload, 'pole_spacing'), 'INDICATOR: ' . ccu_value($payload, 'indicator')],
        ['TECHNIQUE: ' . ccu_value($payload, 'technique'), '', 'TEST METHOD:' . ccu_value($payload, 'test_method')],
    ];
    foreach ($inspRows as $row) {
        $cell($x, $y, $sx(310), $sy(15), $row[0], 15, true);
        $cell($x + $sx(310), $y, $sx(150), $sy(15), $row[1], 15, true);
        $cell($x + $sx(460), $y, $w - $sx(460), $sy(15), $row[2], 15, true);
        $y += $sy(15);
    }
    $cell($x, $y, $sx(400), $sy(42), 'TEST RESULT: ' . ccu_value($payload, 'test_result'), 15, true);
    $cell($x + $sx(400), $y, $w - $sx(400), $sy(42), 'DUE DATE: ' . ccu_display_date((string) $payload['expiry_date']), 23, true, 'center');
    $y += $sy(42);
    $cell($x, $y, $sx(150), $sy(18), 'DEMAGNETIZATION:' . ccu_value($payload, 'demagnetization'), 11, true);
    $cell($x + $sx(150), $y, $w - $sx(150), $sy(18), 'COLOUR CODE: ' . ccu_value($payload, 'colour_code'), 11, true);

    $y += $sy(18);
    $cell($x, $y, $w, $sy(27), 'I hereby certify that the equipment described on the test certificate have been examined and found to be free from flaws, other weld defects.', 16, true);

    $y += $sy(27);
    $cell($x, $y, $sx(200), $sy(52), "Name & Qualifications of person making this report:\n" . ccu_value($payload, 'inspector_name') . "\n" . ccu_value($payload, 'inspector_qualifications') . "\nSIGNATURE:", 14, true);
    $cell($x + $sx(200), $y, $w - $sx(200), $sy(52), "Name of person signing or authenticating this report on behalf of the author:\n" . ccu_value($payload, 'authenticator_name') . "\n" . ccu_value($payload, 'authenticator_qualifications') . "\nSIGNATURE:", 14, true);
    $authentication = is_array($payload['authentication'] ?? null) ? $payload['authentication'] : [];
    if (!empty($authentication['inspector_signature_path'])) { $putPng((string) $authentication['inspector_signature_path'], $x + $sx(12), $y + $sy(28), $sx(105), $sy(20), 100); }
    if (!empty($authentication['authenticator_signature_path'])) { $putPng((string) $authentication['authenticator_signature_path'], $x + $sx(220), $y + $sy(28), $sx(120), $sy(20), 100); }

    $y += $sy(52);
    $cell($x, $y, $sx(280), $sy(48), "REGISTERED ADDRESS:\n" . (string) $payload['company']['registered'] . "\nPhone: " . (string) $payload['company']['phone'], 15, false);
    $cell($x + $sx(280), $y, $w - $sx(280), $sy(48), "OPERATIONAL ADDRESS:\n" . (string) $payload['company']['operational'] . "\nEmail: " . (string) $payload['company']['email'] . ", Website: " . (string) $payload['company']['website'] . "\nPhone: " . (string) $payload['company']['phone'], 15, false);
    if (!empty($authentication['company_stamp_path'])) { $putPng((string) $authentication['company_stamp_path'], $x + $w - $sx(95), $y + $sy(5), $sx(80), $sy(38), 100); }

    $footerY = $y + $sy(48) + $sy(10);
    $putPng($assetDir . '/bsi-logo.png', $sx(94), $footerY + $sy(6), $sx(61), $sy(47), 100);
    $putPng($assetDir . '/asnt-logo.png', $sx(155), $footerY, $sx(60), $sy(58), 100);
    $putPng($assetDir . '/acebi-logo.png', $sx(260), $footerY + $sy(7), $sx(73), $sy(47), 100);

    $qrBoxX = $sx(586);
    $qrBoxY = $footerY;
    $qrBoxW = $sx(121);
    $qrBoxH = $sy(82);
    imagerectangle($canvas, $qrBoxX, $qrBoxY, $qrBoxX + $qrBoxW, $qrBoxY + $qrBoxH, certificate_color($canvas, '#777777'));
    $drawQr((string) $payload['verification_url'], $qrBoxX + $sx(34), $qrBoxY + $sy(4), $sx(54));
    $textAt('SCAN TO VERIFY', $qrBoxX + (int) floor($qrBoxW / 2), $qrBoxY + $sy(66), 12, $ink, true, 'center');
    $textAt('cert.juvaoil.com', $qrBoxX + (int) floor($qrBoxW / 2), $qrBoxY + $sy(77), 10, $muted, false, 'center');

    $jpegPath = preg_replace('/\.pdf$/i', '.jpg', $path) ?: $path . '.jpg';
    imagejpeg($canvas, $jpegPath, 92);
    imagedestroy($canvas);
    certificate_create_jpeg_pdf($path, $jpegPath);
}






