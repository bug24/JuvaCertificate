<?php

declare(strict_types=1);

require_once __DIR__.'/../api/lib/certificate_number_reconciliation.php';

function reconciliation_test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "CERTIFICATE NUMBER RECONCILIATION TEST FAILED: {$message}\n");
        exit(1);
    }
}

function reconciliation_test_row(array $overrides=[]): array
{
    return array_merge([
        'inspection_id'=>11,
        'inspection_reference'=>'JUVA/254/SHK/001',
        'sequence_number'=>1,
        'client_id'=>21,
        'client_name'=>'Jukoso Global Services',
        'client_short_code'=>'JUKOSO',
        'category_id'=>31,
        'category_short_code'=>'SHK',
        'certificate_id'=>41,
        'certificate_number'=>'JUVA/254/SHK/001',
        'revision'=>3,
        'revision_count'=>3,
        'verification_token'=>str_repeat('a',64),
        'pdf_path'=>'certificates/JUVA_254_SHK_001/rev-3.pdf',
        'barcode_path'=>'certificates/JUVA_254_SHK_001/rev-3-qr.svg',
        'pdf_hash'=>str_repeat('b',64),
    ],$overrides);
}

$shackles=certificate_number_reconciliation_decision(reconciliation_test_row());
reconciliation_test_assert($shackles['state']==='action','numeric Shackles client segment should be reconcilable');
reconciliation_test_assert($shackles['target_certificate_number']==='JUVA/JUKOSO/SHK/001','Shackles should use the relational JUKOSO short code');
reconciliation_test_assert($shackles['category_short_code']==='SHK'&&$shackles['sequence_number']===1,'category and sequence must remain unchanged');
reconciliation_test_assert($shackles['inspection_id']===11&&$shackles['certificate_id']===41&&$shackles['revision']===3,'inspection, certificate, and revision identities must remain unchanged');
reconciliation_test_assert($shackles['verification_token']===str_repeat('a',64),'verification token and QR identity must remain unchanged');
reconciliation_test_assert($shackles['pdf_path']==='certificates/JUVA_254_SHK_001/rev-3.pdf','archived PDF path must remain unchanged');

$flat=certificate_number_reconciliation_decision(reconciliation_test_row([
    'inspection_reference'=>'JUVA/254/FLTWBSL/001',
    'certificate_number'=>'JUVA/254/FLTWBSL/001',
    'category_short_code'=>'FLTWBSL',
]));
reconciliation_test_assert($flat['target_certificate_number']==='JUVA/JUKOSO/FLTWBSL/001','Flat Webbing Sling should preserve its category and sequence');

$aveon=certificate_number_reconciliation_decision(reconciliation_test_row([
    'inspection_id'=>12,
    'certificate_id'=>42,
    'client_id'=>22,
    'client_name'=>'Aveon Offshore Limited',
    'client_short_code'=>'AVEON',
    'inspection_reference'=>'JUVA/254/CHBLK/007',
    'certificate_number'=>'JUVA/254/CHBLK/007',
    'category_short_code'=>'CHBLK',
    'sequence_number'=>7,
]));
reconciliation_test_assert($aveon['target_certificate_number']==='JUVA/AVEON/CHBLK/007','each row must use its own relational client short code');
reconciliation_test_assert($aveon['target_certificate_number']!==$shackles['target_certificate_number'],'different clients must not share a guessed replacement');

$canonical=certificate_number_reconciliation_decision(reconciliation_test_row([
    'inspection_reference'=>'JUVA/JUKOSO/SHK/001',
    'certificate_number'=>'JUVA/JUKOSO/SHK/001',
]));
reconciliation_test_assert($canonical['state']==='canonical','a second run must produce no action for an already canonical row');

$numericClient=certificate_number_reconciliation_decision(reconciliation_test_row(['client_short_code'=>'254']));
reconciliation_test_assert($numericClient['state']==='blocked'&&$numericClient['reason']==='invalid_client_short_code','numeric-only configured client code must block reconciliation');
$emptyClient=certificate_number_reconciliation_decision(reconciliation_test_row(['client_short_code'=>'']));
reconciliation_test_assert($emptyClient['state']==='blocked'&&$emptyClient['reason']==='invalid_client_short_code','empty configured client code must block reconciliation');
$categoryMismatch=certificate_number_reconciliation_decision(reconciliation_test_row(['category_short_code'=>'FLTWBSL']));
reconciliation_test_assert($categoryMismatch['state']==='blocked'&&$categoryMismatch['reason']==='category_mismatch','category mismatch must block instead of changing category');
$sequenceMismatch=certificate_number_reconciliation_decision(reconciliation_test_row(['sequence_number'=>2]));
reconciliation_test_assert($sequenceMismatch['state']==='blocked'&&$sequenceMismatch['reason']==='sequence_mismatch','sequence mismatch must block instead of changing sequence');
$numberMismatch=certificate_number_reconciliation_decision(reconciliation_test_row(['certificate_number'=>'JUVA/254/SHK/002']));
reconciliation_test_assert($numberMismatch['state']==='blocked'&&$numberMismatch['reason']==='authoritative_number_mismatch','different authoritative values must require manual review');
$slashClient=certificate_number_reconciliation_decision(reconciliation_test_row(['client_short_code'=>'BAD/CODE']));
reconciliation_test_assert($slashClient['state']==='blocked','slash-containing client code must block reconciliation');

reconciliation_test_assert(certificate_number_reconciliation_parse('JUVA/JUKOSO/SHK/001')['sequence_segment']==='001','parser must preserve the exact sequence segment');
reconciliation_test_assert(CERTIFICATE_NUMBER_RECONCILIATION_CONFIRMATION==='RECONCILE-CERTIFICATE-NUMBERS','apply confirmation phrase must remain explicit');

echo "CERTIFICATE NUMBER RECONCILIATION: PASS\n";
