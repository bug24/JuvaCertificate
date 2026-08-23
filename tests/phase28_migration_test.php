<?php
declare(strict_types=1);

function phase28_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "PHASE 28 MIGRATION TEST FAILED: {$message}\n");
        exit(1);
    }
}

$root = dirname(__DIR__);
$migrationPath = $root . '/database/migrations/phase28_partial_dates_shackles_reengineering.sql';
$deploymentPath = $root . '/deployment/juva-certify-cpanel-fresh/database/phase28_partial_dates_shackles_reengineering.sql';
$sql = file_get_contents($migrationPath);
$deploymentSql = file_get_contents($deploymentPath);
phase28_assert(is_string($sql) && $sql !== '', 'migration SQL must be readable');
phase28_assert($deploymentSql === $sql, 'deployment migration must exactly match the source migration');

$contains = static function (string $needle) use ($sql): bool { return strpos($sql, $needle) !== false; };
$position = static function (string $needle) use ($sql): int {
    $found = strpos($sql, $needle);
    phase28_assert($found !== false, "missing SQL contract: {$needle}");
    return (int) $found;
};

$phase28Fields = ['colour_code','standard','reason_for_examination_code','pin_condition','body_distortion_check','defect_observation_sheet_attached'];
$phase28Columns = ['identification_number','description','swl_wll','date_last_examined','manufacturer','test_details','status','safe_to_use'];

phase28_assert($contains("GET_LOCK(@phase28_lock_name, 60)"), 'concurrent runs must use a named database lock');
phase28_assert($contains('PHASE28_ERROR_MIGRATION_LOCK_UNAVAILABLE'), 'lock failure must stop before migration work');
phase28_assert($contains('v_columns=1'), 'one companion column must be rejected');
phase28_assert($contains("column_type='enum(''day'',''month'',''year'')'"), 'precision definition must be checked');
phase28_assert($contains('v_compatible<>2'), 'incompatible companion definitions must be rejected');
phase28_assert($contains("COUNT(*) INTO v_count FROM certification_categories WHERE code='SHK'"), 'SHK cardinality must be checked');
phase28_assert(!$contains("WHERE code='SHK' ORDER BY id LIMIT 1"), 'SHK validation must not hide duplicates');
phase28_assert($contains('SHK category is not Phase 27 compatible'), 'Phase 27 metadata must be validated');
phase28_assert($contains('multiple active SHK templates found'), 'multiple active templates must be rejected');
phase28_assert($contains('partial Phase 28 template found'), 'partial templates must be rejected');
phase28_assert($contains('ALREADY APPLIED / NO ACTION REQUIRED'), 'successful reruns must be no-op');
phase28_assert($contains("status='draft'"), 'replacement must be constructed as draft');
phase28_assert($contains('FOR UPDATE'), 'version allocation must lock rows');
phase28_assert($contains('DECLARE EXIT HANDLER FOR SQLEXCEPTION'), 'failures must have a handler');
phase28_assert($contains('IF v_transaction=1 THEN ROLLBACK'), 'transactional failures must rollback');
phase28_assert($position('INSERT INTO form_repeatable_columns') < $position("SET status='archived'"), 'archive must follow child insertion');
phase28_assert($position('new column verification failed') < $position("SET status='archived'"), 'archive must follow verification');
phase28_assert($position("SET status='archived'") < $position("SET status='active'"), 'activation order must be safe');
phase28_assert(substr_count($sql, 'START TRANSACTION;') === 2, 'backfill and template work require separate transactions');
phase28_assert($position('ALTER TABLE equipment') < $position('START TRANSACTION;'), 'DDL must precede transactions');

foreach ($phase28Fields as $field) {
    phase28_assert(substr_count($sql, "'{$field}'") >= 2, "missing Phase 28 field {$field}");
}
foreach ($phase28Columns as $column) {
    phase28_assert(substr_count($sql, "'{$column}'") >= 2, "missing Phase 28 column {$column}");
}

phase28_assert($contains("manufacture_date_precision='year'"), 'year-only values must be recognized');
phase28_assert($contains("manufacture_date_precision='month'"), 'month/year values must be recognized');
phase28_assert($contains("manufacture_date_precision='day'"), 'full dates must be recognized');
phase28_assert($contains('AND manufacture_date_precision IS NULL'), 'backfill must only fill empty companions');
phase28_assert(!$contains('SET manufacture_date=STR_TO_DATE'), 'manufacture_date must never be fabricated');
phase28_assert(!$contains('MODIFY COLUMN manufacture_date'), 'legacy DATE must not be converted');
phase28_assert(!$contains('DROP COLUMN manufacture_date'), 'legacy DATE must not be dropped');

$protected = ['certificates','certificate_revisions','certificate_sequences','inspection_values','inspection_items','inspections','users'];
foreach ($protected as $table) {
    $pattern = '/\b(?:ALTER\s+TABLE|UPDATE|DELETE\s+FROM|INSERT\s+INTO)\s+' . preg_quote($table, '/') . '\b/i';
    phase28_assert(!preg_match($pattern, $sql), "migration must not mutate {$table}");
}

function phase28_decision(array $state): string
{
    if (($state['columns'] ?? 0) === 1) return 'refuse_one_column';
    if (($state['columns'] ?? 0) === 2 && !($state['compatible'] ?? false)) return 'refuse_definition';
    if (($state['shk_categories'] ?? 0) !== 1) return 'refuse_category';
    if (!($state['phase27_metadata'] ?? false)) return 'refuse_metadata';
    if (($state['active_templates'] ?? 0) > 1) return 'refuse_multiple_active';
    if (($state['partial_phase28'] ?? 0) > 0) return 'refuse_partial_template';
    if (($state['complete_phase28_archived'] ?? 0) > 0) return 'refuse_archived_phase28';
    if (($state['complete_phase28_active'] ?? 0) === 1) {
        return ($state['columns'] ?? 0) === 2 ? 'already_applied' : 'refuse_cross_state';
    }
    if (($state['active_templates'] ?? 0) !== 1 || !($state['complete_phase27'] ?? false)) return 'refuse_phase27_template';
    if (($state['inconsistent_dates'] ?? 0) > 0) return 'refuse_dates';
    return 'apply';
}

$fresh = [
    'columns'=>0,'compatible'=>true,'shk_categories'=>1,'phase27_metadata'=>true,
    'active_templates'=>1,'complete_phase27'=>true,'partial_phase28'=>0,
    'complete_phase28_active'=>0,'complete_phase28_archived'=>0,'inconsistent_dates'=>0,
];
phase28_assert(phase28_decision($fresh)==='apply', '1. fresh Phase 27 state must apply');
$applied=$fresh; $applied['columns']=2; $applied['complete_phase28_active']=1; $applied['complete_phase27']=false;
phase28_assert(phase28_decision($applied)==='already_applied', '2/9. complete active Phase 28 must be no-op');
phase28_assert($contains("DATE_FORMAT(manufacture_date,'%Y-%m-%d')"), '3. full dates must be backfilled exactly');
phase28_assert($contains("manufacture_date_precision IN ('month','year')"), '4/5. partial values must survive rerun');
$case=$fresh; $case['columns']=1;
phase28_assert(phase28_decision($case)==='refuse_one_column', '6. one companion column must be refused');
$case=$fresh; $case['columns']=2; $case['compatible']=false;
phase28_assert(phase28_decision($case)==='refuse_definition', '7. bad definitions must be refused');
$case=$fresh; $case['columns']=2; $case['partial_phase28']=1;
phase28_assert(phase28_decision($case)==='refuse_partial_template', '8. partial template must be refused');
$case=$fresh; $case['active_templates']=2;
phase28_assert(phase28_decision($case)==='refuse_multiple_active', '10. multiple active templates must be refused');
$case=$fresh; $case['shk_categories']=0;
phase28_assert(phase28_decision($case)==='refuse_category', '11. missing category must be refused');
$case=$fresh; $case['shk_categories']=2;
phase28_assert(phase28_decision($case)==='refuse_category', '12. multiple categories must be refused');
phase28_assert($position('ROLLBACK') < $position('RESIGNAL'), '13. insertion failure must rollback');
phase28_assert(!$contains('DELETE FROM form_templates'), '14/16. old templates and inspection links must remain');
phase28_assert(!$contains('verification_token') && !$contains('certificate_number'), '15. certificate identities must remain untouched');
phase28_assert($contains("WHERE id=v_new_template AND category_id=v_category_id AND status='draft'"), '17. new inspections must receive the new active template');

echo "PHASE 28 MIGRATION HARDENING CONTRACT: PASS\n";
