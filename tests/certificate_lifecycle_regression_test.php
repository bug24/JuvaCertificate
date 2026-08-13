<?php

declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once __DIR__ . '/../api/lib/certificate_service.php';

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $label) use (&$failures): void {
    echo $label . ': ' . ($condition ? 'PASS' : 'FAIL') . PHP_EOL;
    if (!$condition) $failures[] = $label;
};

$engine = (string) file_get_contents($root . '/api/lib/certificate_engine.php');
$renderer = (string) file_get_contents($root . '/api/lib/certificate_download_renderer.php');
$download = (string) file_get_contents($root . '/api/certificates/download.php');
$status = (string) file_get_contents($root . '/api/inspections/status.php');
$clientApi = (string) file_get_contents($root . '/api/clients/index.php');

$assert(valid_company_short_code('JUKOSO'), 'COMPANY SHORT NAME ACCEPTED');
$assert(valid_company_short_code('AVEON2'), 'ALPHANUMERIC COMPANY SHORT NAME ACCEPTED');
$assert(!valid_company_short_code('254'), 'NUMERIC INTERNAL TOKEN REJECTED');
$assert(format_scoped_reference('JUKOSO', 'FLTWBSL', 1) === 'JUVA/JUKOSO/FLTWBSL/001', 'FLAT WEBBING NUMBER USES COMPANY SHORT NAME');
$assert(format_scoped_reference('AVEON', 'SHK', 2) === 'JUVA/AVEON/SHK/002', 'SHACKLES NUMBER USES COMPANY SHORT NAME');

$assert(strpos($clientApi, 'valid_company_short_code($shortCode)') !== false, 'CLIENT ADMIN ENFORCES COMPANY SHORT NAME');
$assert(strpos($renderer, '$temporaryBase . \'pdf\'') === false, 'DYNAMIC DOWNLOAD SOURCE IS NOT MALFORMED');
$assert(strpos($renderer, '$temporaryBase . \'.pdf\'') !== false, 'DYNAMIC DOWNLOAD USES PDF-SUFFIXED WORKSPACE');
$assert(strpos($renderer, 'certificate_archive_hash($path);') !== false, 'DYNAMIC DOWNLOAD VALIDATES GENERATED PDF');
$assert(strpos($download, 'if (!$path) {') !== false && strpos($download, "api_error('File not found.', 404);") !== false, 'MISSING HISTORICAL ARCHIVE IS CONTROLLED');

$archiveCheck = strpos($engine, 'certificate_archive_hash($pp)');
$issuedTransaction = strpos($engine, '$db->beginTransaction()');
$assert($archiveCheck !== false && $issuedTransaction !== false && $archiveCheck < $issuedTransaction, 'PDF ARCHIVE VERIFIED BEFORE ISSUED TRANSACTION');
$assert(strpos($engine, 'if(!$old&&(string)$i[\'reference\']!==$no)') !== false, 'UNISSUED REFERENCE CANONICALIZED AT ISSUANCE');
$assert(strpos($engine, 'if($old){$no=normalize_certificate_number($old[\'certificate_number\']);}') !== false, 'ISSUED HISTORICAL NUMBER PRESERVED');
$assert(strpos($engine, 'catch(Throwable $notificationError)') !== false, 'POST-COMMIT NOTIFICATION FAILURE IS ISOLATED');
$assert(strpos($status, '$e instanceof CertificateIssueException') !== false, 'ISSUANCE ERRORS REMAIN JSON SAFE');

$validPdf = tempnam(sys_get_temp_dir(), 'juva-pdf-valid-');
$invalidPdf = tempnam(sys_get_temp_dir(), 'juva-pdf-invalid-');
if ($validPdf === false || $invalidPdf === false) exit(1);
file_put_contents($validPdf, "%PDF-1.4\n%%EOF");
file_put_contents($invalidPdf, 'not a pdf');
$assert((bool) preg_match('/^[a-f0-9]{64}$/', certificate_archive_hash($validPdf)), 'VALID PDF ARCHIVE HASHED');
$invalidRejected = false;
try { certificate_archive_hash($invalidPdf); } catch (CertificateIssueException $error) { $invalidRejected = $error->httpStatus() === 500; }
$assert($invalidRejected, 'INVALID PDF CANNOT BE MARKED ISSUED');
@unlink($validPdf);
@unlink($invalidPdf);

exit($failures ? 1 : 0);