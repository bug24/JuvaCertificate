<?php

function eye_bolt_blueprint(): array
{
    $field = static function (string $key, string $label, string $type, bool $required, int $sort, string $section, ?string $help = null, ?array $options = null): array {
        return ['field_key'=>$key,'label'=>$label,'field_type'=>$type,'is_required'=>$required ? 1 : 0,'sort_order'=>$sort,'pdf_section'=>$section,'help_text'=>$help,'options'=>$options];
    };
    $column = static function (string $key, string $label, string $type, bool $required, int $sort, ?array $options = null): array {
        return ['column_key'=>$key,'label'=>$label,'column_type'=>$type,'is_required'=>$required ? 1 : 0,'sort_order'=>$sort,'options'=>$options];
    };

    return [
        'code'=>'EYEBLT','short_code'=>'EYEBLT','name'=>'Eye Bolt',
        'description'=>'Dedicated landscape register certificate for thorough examination of eye bolts.',
        'validity_months'=>6,'certificate_template'=>'Certificate of Thorough Examination',
        'template_family'=>'eye_bolt','layout_key'=>'eye-bolt-landscape-v1',
        'source_sample'=>'EYE BOLT.jpg','schema_version'=>2,'theme_color'=>'#1565C0','identifier_label'=>'Certificate Number',
        'fields'=>[
            $field('colour_code','Colour Code','text',true,10,'certificate'),
            $field('standard','Standard','text',true,20,'certificate','Example: BS EN 13889.'),
            $field('inspector_name_snapshot','Inspector Name on Certificate','text',false,100,'signoff','Defaults to the assigned inspector.'),
            $field('inspector_qualification_snapshot','Inspector Qualifications on Certificate','text',false,110,'signoff','Defaults to the inspector profile.'),
            $field('authenticator_name','Authenticator Name','text',false,120,'signoff'),
            $field('authenticator_qualification','Authenticator Qualifications','text',false,130,'signoff'),
            $field('defect_observation_sheet_attached','Defect / Observation Sheet Attached?','select',true,140,'signoff',null,['Yes','No']),
        ],
        'sections'=>[[
            'section_key'=>'eye_bolt_items','label'=>'Eye Bolt Examination Register',
            'help_text'=>'Add one row for each eye bolt covered by this certificate. Maximum six rows per one-page certificate.',
            'min_rows'=>1,'max_rows'=>6,'pdf_section'=>'equipment_register','sort_order'=>200,
            'columns'=>[
                $column('serial_number','S/N','text',false,10),
                $column('identification_number','Identification Number','text',true,20),
                $column('description','Description','text',true,30),
                $column('working_load_limit','WLL or SWL','text',true,40),
                $column('last_thorough_examination_date','Last Thorough Examination','date',false,50),
                $column('manufacturer','Manufacturer','text',true,60),
                $column('next_thorough_examination_date','Next Thorough Examination','date',true,70),
                $column('reason_for_examination_code','Reason Code','select',true,80,['A','B','C','D','E']),
                $column('test_details','Test Details','text',true,90),
                $column('status_code','Status','select',true,100,['ND','SDR','NF','OBS']),
                $column('safe_to_use','Safe to Use','select',true,110,['Yes','No']),
            ],
        ]],
    ];
}
