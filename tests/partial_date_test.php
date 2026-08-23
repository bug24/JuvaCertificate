<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/lib/partial_dates.php';

function partial_date_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "PARTIAL DATE TEST FAILED: {$message}\n");
        exit(1);
    }
}

partial_date_assert(partial_date_precision('2029-06-23') === 'day' && format_juva_partial_date('2029-06-23') === '23/06/2029', '1. full date remains exact');
partial_date_assert(partial_date_precision('2029-06') === 'month' && format_juva_partial_date('2029-06') === '06/2029', '2. month/year remains month/year');
partial_date_assert(partial_date_precision('2029') === 'year' && format_juva_partial_date('2029') === '2029', '3. year remains year-only');
partial_date_assert(format_juva_partial_date('2029') !== '01/01/2029', '4. no fake January 1 is introduced');
partial_date_assert(supports_partial_historical_date_key('date_last_examined'), '5. certificate historical date is classified for precision preservation');
partial_date_assert(valid_partial_date('2024-02-29') && !valid_partial_date('2023-02-29'), '6. existing complete dates remain validated');
partial_date_assert(partial_date_precision('2029') !== 'day', '7. partial dates cannot enter exact-date calculations');
$cloneSource = file_get_contents(__DIR__ . '/../api/inspections/clone.php');
partial_date_assert(is_string($cloneSource) && strpos($cloneSource, 'value_text') !== false && strpos($cloneSource, 'strtotime($value') === false, '8. clone/renew copies stored text without expanding precision');
$equipmentSource = file_get_contents(__DIR__ . '/../api/equipment/equipment.php');
partial_date_assert(is_string($equipmentSource) && strpos($equipmentSource, 'manufacture_date_value') !== false && strpos($equipmentSource, 'manufacture_date_precision') !== false, '9. equipment edit retains value and precision');
$workflowSource = file_get_contents(__DIR__ . '/../src/InspectionWorkflowPage.tsx');
partial_date_assert(is_string($workflowSource) && strpos($workflowSource, 'manufacture_date_value') !== false && strpos($workflowSource, 'previous_examination_date') !== false, '10. inspection auto-population preserves equipment date precision');
echo "PARTIAL DATE REGRESSION TESTS: PASS\n";
