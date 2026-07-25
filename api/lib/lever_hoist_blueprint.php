<?php
require_once __DIR__ . '/endless_round_webbing_sling_blueprint.php';

function lever_hoist_blueprint(): array
{
    $blueprint = endless_round_webbing_sling_blueprint();
    $blueprint['code'] = 'LVHST';
    $blueprint['short_code'] = 'LVHST';
    $blueprint['name'] = 'Lever Hoist';
    $blueprint['description'] = 'Dedicated thorough examination certificate for lever hoists.';
    $blueprint['template_family'] = 'lever_hoist';
    $blueprint['layout_key'] = 'lever-hoist-v1';
    $blueprint['source_sample'] = 'LEVER HOIST.jpg';
    $blueprint['schema_version'] = 2;
    $blueprint['theme_color'] = '#7A1F1F';

    $fields = [];
    foreach ($blueprint['fields'] as $field) {
        if (in_array($field['field_key'], ['length', 'asset_number'], true)) continue;
        if ($field['field_key'] === 'equipment_type') {
            $field['label'] = 'Equipment Name';
            $field['help_text'] = 'Enter LEVER HOIST or the precise equipment title.';
        }
        $fields[] = $field;
        if ($field['field_key'] === 'equipment_id_number') {
            $fields[] = [
                'field_key'=>'chain_dimensions','label'=>'Chain Dimension',
                'field_type'=>'text','is_required'=>1,'sort_order'=>75,
                'pdf_section'=>'equipment','help_text'=>null,'options'=>null,
            ];
        }
        if ($field['field_key'] === 'defect_future_danger') {
            $fields[] = [
                'field_key'=>'future_danger_description',
                'label'=>'Not Yet Dangerous Defect Description',
                'field_type'=>'textarea','is_required'=>0,'sort_order'=>325,
                'pdf_section'=>'defects',
                'help_text'=>'Enter NONE when there is no future danger.',
                'options'=>null,
            ];
        }
    }
    $blueprint['fields'] = $fields;
    return $blueprint;
}
