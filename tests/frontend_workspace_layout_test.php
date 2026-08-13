<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$certificateUi = (string) file_get_contents($root . '/src/CertificateRegisterPage.tsx');
$inspectionUi = (string) file_get_contents($root . '/src/InspectionWorkflowPage.tsx');
$styles = (string) file_get_contents($root . '/src/styles.css');
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = 'FAIL: ' . $message;
    }
};

$assert(strpos($certificateUi, 'aria-label="Certificate sections"') !== false, 'Certificate workspace navigation must be labelled.');
foreach (['Certificates', 'Archive', 'Print Certificates'] as $label) {
    $assert(strpos($certificateUi, '>' . $label . '</button>') !== false, 'Certificate navigation must include ' . $label . '.');
}
$filterPosition = strpos($certificateUi, 'className="archive-filter-form certificate-section-anchor"');
$toolbarPosition = strpos($certificateUi, 'className={`certificate-batch-bar');
$tablePosition = strpos($certificateUi, 'className="table-scroll archive-table-wrap"');
$assert($filterPosition !== false && $toolbarPosition !== false && $tablePosition !== false && $filterPosition < $toolbarPosition && $toolbarPosition < $tablePosition, 'Batch actions must render after archive filters and before archive results.');
$assert(strpos($certificateUi, 'selectedRows.size === 0') !== false, 'Batch actions must remain visible with an explicit empty selection state.');
$assert(strpos($certificateUi, 'orderedSelectedIds') !== false && strpos($certificateUi, 'setSelectedRows((current)') !== false, 'Cross-page selection state and stable batch ordering must remain intact.');

$assert(strpos($inspectionUi, 'className="inspection-register-table"') !== false, 'Inspection register must use the compact table layout.');
$assert(strpos($inspectionUi, 'className="inspection-actions-cell"') !== false, 'Inspection actions must have a dedicated visible column.');
foreach (['Comment', 'Clone / Renew', 'Revoke', 'Submit & issue', 'Return', 'Approve', 'Edit / Complete Fields'] as $action) {
    $assert(strpos($inspectionUi, $action) !== false, 'Inspection action must remain available: ' . $action . '.');
}
$assert(strpos($styles, '.inspection-register-table{') !== false && strpos($styles, 'table-layout:fixed') !== false, 'Inspection register CSS must constrain desktop columns.');
$assert(strpos($styles, '@media(max-width:1180px)') !== false && strpos($styles, 'overflow-x:auto') !== false, 'Inspection register must preserve responsive horizontal access.');
$assert(strpos($styles, '.batch-archive-table{') !== false && strpos($styles, '.certificate-section-nav{') !== false, 'Certificate archive and navigation styles must be present.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Frontend workspace layout tests passed: navigation, toolbar order, selection persistence, compact registers and action parity." . PHP_EOL;