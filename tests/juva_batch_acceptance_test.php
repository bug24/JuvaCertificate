<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/lib/certificate_service.php';

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $label) use (&$failures): void {
    echo $label . ': ' . ($condition ? 'PASS' : 'FAIL') . PHP_EOL;
    if (!$condition) $failures[] = $label;
};

$workflow = (string) file_get_contents($root . '/src/InspectionWorkflowPage.tsx');
$userApi = (string) file_get_contents($root . '/api/users/user.php');
$signatureService = (string) file_get_contents($root . '/api/lib/signature_service.php');
$download = (string) file_get_contents($root . '/api/certificates/download.php');
$downloadRenderer = (string) file_get_contents($root . '/api/lib/certificate_download_renderer.php');

$assert(strpos($workflow, 'Inspector / Person Making the Report') !== false, 'GLOBAL INSPECTOR SELECTOR');
$assert(strpos($workflow, 'Authenticator / Person Authenticating the Report') !== false, 'GLOBAL AUTHENTICATOR SELECTOR');
$assert(strpos($workflow, 'fields.filter((field) => !isIdentityField(field))') !== false, 'CATEGORY IDENTITY FIELDS ARE NOT DUPLICATED');
$assert(strpos($workflow, 'identity-selection-card') !== false && strpos($workflow, 'Inspector (Prepared by) *') !== false && strpos($workflow, 'Authenticator (Authorized by) *') !== false, 'IDENTITY SELECTORS ARE IN EVIDENCE STEP');
$assert(strpos($workflow, 'stepErrors(3, form, fields, values, sections, items)') !== false, 'BOTH IDENTITIES BLOCK FINAL SUBMISSION');
$assert(strpos($workflow, 'inspector_id: form.inspector_id ? Number(form.inspector_id)') !== false && strpos($workflow, 'authenticator_id: form.authenticator_id ? Number(form.authenticator_id)') !== false, 'GLOBAL IDENTITY IDS ARE SAVED');
$assert(strpos($workflow, 'inspector_id: String(inspection.inspector_id || "")') !== false && strpos($workflow, 'authenticator_id: String(inspection.authenticator_id || "")') !== false, 'DRAFT IDENTITY IDS ARE RELOADED');

$assert(strpos($userApi, 'job_title = ?') !== false && strpos($userApi, 'professional_memberships = ?') !== false && strpos($userApi, 'certificate_signing_role = ?') !== false, 'PROFESSIONAL PROFILE FIELDS ARE MAINTAINABLE');
$assert(strpos($signatureService, '$inspectorPath = $inspectionSignature ?: $profileSignature;') !== false, 'INSPECTION SIGNATURE OVERRIDES PROFILE SIGNATURE');
$assert(strpos($signatureService, "\$inspection['authenticator_id']") !== false && strpos($signatureService, 'approvalStmt') === false, 'AUTHENTICATOR RESOLVES FROM SAVED USER ID');
$assert(strpos($download, 'certificate_render_existing_download($certificate)') !== false, 'EXISTING DOWNLOADS USE DYNAMIC RENDERER');
$assert(strpos($downloadRenderer, 'certificate_revisions WHERE certificate_id=? AND revision=?') !== false && strpos($downloadRenderer, "certificate_effective_status(\$certificate)") !== false, 'DYNAMIC DOWNLOAD PRESERVES SNAPSHOTS AND REFRESHES STATUS');

$font = certificate_find_font(true);
$assert($font !== null, 'CERTIFICATE BOLD FONT AVAILABLE');
if ($font !== null) {
    $image = imagecreatetruecolor(320, 140);
    imagefilledrectangle($image, 0, 0, 319, 139, certificate_color($image, '#000000'));
    certificate_draw_status_badge($image, 'VALID', 10, 10, 140, 50, $font, 21);
    certificate_draw_status_badge($image, 'EXPIRED', 170, 10, 140, 50, $font, 21);
    $validBackground = imagecolorat($image, 12, 12) & 0xFFFFFF;
    $expiredBackground = imagecolorat($image, 172, 12) & 0xFFFFFF;
    $whitePixels = static function ($image, int $x1, int $y1, int $x2, int $y2): int {
        $count = 0;
        for ($y = $y1; $y <= $y2; $y++) for ($x = $x1; $x <= $x2; $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
            if ($r > 235 && $g > 235 && $b > 235) $count++;
        }
        return $count;
    };
    $assert($validBackground === 0x198754, 'VALID BADGE HAS GREEN BACKGROUND');
    $assert($expiredBackground === 0xB42318, 'EXPIRED BADGE HAS RED BACKGROUND');
    $assert($whitePixels($image, 10, 10, 150, 60) > 20, 'VALID BADGE HAS VISIBLE WHITE TEXT');
    $assert($whitePixels($image, 170, 10, 310, 60) > 20, 'EXPIRED BADGE HAS VISIBLE WHITE TEXT');
    imagedestroy($image);
}

exit($failures ? 1 : 0);