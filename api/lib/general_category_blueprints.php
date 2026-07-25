<?php

declare(strict_types=1);

function general_lifting_accessories_blueprint(): array
{
    $field = static function (string $key, string $label, string $type, bool $required, int $sort, string $section, ?string $help = null, ?array $options = null): array {
        return ['field_key'=>$key,'label'=>$label,'field_type'=>$type,'is_required'=>$required ? 1 : 0,'sort_order'=>$sort,'pdf_section'=>$section,'help_text'=>$help,'options'=>$options];
    };
    $column = static function (string $key, string $label, string $type, bool $required, int $sort, ?array $options = null): array {
        return ['column_key'=>$key,'label'=>$label,'column_type'=>$type,'is_required'=>$required ? 1 : 0,'sort_order'=>$sort,'options'=>$options];
    };
    return [
        'code'=>'GENLIFTACC','short_code'=>'GLACC','name'=>'General Lifting Accessories',
        'description'=>'Controlled landscape certificate for lifting accessories without an approved dedicated category. Use a dedicated category whenever one exists.',
        'validity_months'=>6,'certificate_template'=>'General Lifting Accessories Thorough Examination',
        'template_family'=>'general_lifting_accessory','layout_key'=>'general-lifting-accessory-v1',
        'source_sample'=>'Controlled JUVA landscape accessory layout','schema_version'=>1,'theme_color'=>'#285943','identifier_label'=>'Certificate Number',
        'fields'=>[
            $field('colour_code','Colour Code','text',true,10,'certificate'),
            $field('standard','Standard','text',true,20,'certificate'),
            $field('inspector_name_snapshot','Inspector Name on Certificate','text',false,100,'signoff','Defaults to the assigned inspector.'),
            $field('inspector_qualification_snapshot','Inspector Qualifications on Certificate','text',false,110,'signoff','Defaults to the inspector profile.'),
            $field('authenticator_name','Authenticator Name','text',false,120,'signoff'),
            $field('authenticator_qualification','Authenticator Qualifications','text',false,130,'signoff'),
            $field('defect_observation_sheet_attached','Defect / Observation Sheet Attached?','select',true,140,'signoff',null,['Yes','No']),
        ],
        'sections'=>[[
            'section_key'=>'eye_bolt_items','label'=>'General Lifting Accessory Register',
            'help_text'=>'Add each accessory separately. Use a dedicated category where one exists.',
            'min_rows'=>1,'max_rows'=>6,'pdf_section'=>'equipment_register','sort_order'=>200,
            'columns'=>[
                $column('serial_number','S/N','text',false,10),
                $column('identification_number','Identification Number','text',true,20),
                $column('description','Equipment / Accessory Type and Description','text',true,30),
                $column('working_load_limit','WLL or SWL','text',true,40),
                $column('last_thorough_examination_date','Last Thorough Examination','date',false,50),
                $column('manufacturer','Manufacturer','text',true,60),
                $column('next_thorough_examination_date','Next Thorough Examination','date',true,70),
                $column('reason_for_examination_code','Reason Code','select',true,80,['A','B','C','D','E']),
                $column('test_details','Details of Test','text',true,90),
                $column('status_code','Status Code','select',true,100,['ND','SDR','NF','OBS']),
                $column('safe_to_use','Safe to Use','select',true,110,['Yes','No']),
            ],
        ]],
    ];
}

function general_thorough_examination_blueprint(): array
{
    $yesNo = ['Yes','No'];
    $field = static function (string $key, string $label, string $type, bool $required, int $sort, string $section, ?string $help = null, ?array $options = null): array {
        return ['field_key'=>$key,'label'=>$label,'field_type'=>$type,'is_required'=>$required ? 1 : 0,'sort_order'=>$sort,'pdf_section'=>$section,'help_text'=>$help,'options'=>$options];
    };
    return [
        'code'=>'GENTHREXAM','short_code'=>'GTEX','name'=>'General Thorough Examination',
        'description'=>'Controlled portrait thorough-examination certificate for unusual equipment without an approved dedicated category.',
        'validity_months'=>6,'certificate_template'=>'General Certificate of Thorough Examination',
        'template_family'=>'general_thorough_examination','layout_key'=>'general-thorough-examination-v1',
        'source_sample'=>'Controlled JUVA portrait thorough-examination layout','schema_version'=>1,'theme_color'=>'#3D5366','identifier_label'=>'Certificate Number',
        'fields'=>[
            $field('report_date','Date of Report','date',false,10,'certificate','Defaults to examination date.'),
            $field('premises_address','Inspection Premises','textarea',true,20,'client'),
            $field('equipment_type','Equipment Type','text',true,30,'equipment','Enter the actual equipment type.'),
            $field('equipment_description','Description','textarea',true,40,'equipment'),
            $field('manufacturer','Manufacturer','text',true,50,'equipment'),
            $field('equipment_id_number','Identification Number','text',true,60,'equipment'),
            $field('asset_number','Asset Number','text',false,70,'equipment'),
            $field('chain_dimensions','Dimensions or Specifications','text',false,80,'equipment'),
            $field('standard','Standard','text',true,90,'equipment'),
            $field('safe_working_load','Safe Working Load','text',true,100,'equipment'),
            $field('date_of_manufacture','Date of Manufacture','date',false,110,'equipment'),
            $field('date_of_last_examination','Date of Last Thorough Examination','date',false,120,'equipment'),
            $field('first_examination_after_installation','First Examination After Installation or Assembly?','select',true,200,'checklist',null,$yesNo),
            $field('installed_correctly','If Yes, Installed Correctly?','select',true,210,'checklist',null,$yesNo),
            $field('examined_within_six_months','Examined Within Six Months?','select',true,220,'checklist',null,$yesNo),
            $field('examined_within_twelve_months','Examined Within Twelve Months?','select',true,230,'checklist',null,$yesNo),
            $field('examined_under_scheme','In Accordance With an Examination Scheme?','select',true,240,'checklist',null,$yesNo),
            $field('examined_after_exceptional_circumstances','After Exceptional Circumstances?','select',true,250,'checklist',null,$yesNo),
            $field('defect_description','Defect Description','textarea',true,300,'defects','Enter NONE when no defect exists.'),
            $field('defect_immediate_danger','Immediate Danger?','select',false,310,'defects',null,$yesNo),
            $field('defect_future_danger','Could Become Dangerous?','select',false,320,'defects',null,$yesNo),
            $field('action_due_date','Corrective-action Date','date',false,330,'defects'),
            $field('repair_required','Repair, Renewal or Alteration Required','textarea',true,340,'results','Enter NONE where no action is required.'),
            $field('tests_carried_out','Tests Carried Out','textarea',true,350,'results'),
            $field('observations','Observations / Comments','textarea',false,360,'results'),
            $field('fit_for_purpose','Fit for Purpose?','select',true,400,'fitness',null,$yesNo),
            $field('inspector_name_snapshot','Inspector','text',false,500,'signoff','Defaults to the assigned inspector.'),
            $field('inspector_qualification_snapshot','Inspector Qualifications','text',false,510,'signoff'),
            $field('authenticator_name','Authenticator','text',false,520,'signoff'),
            $field('authenticator_qualification','Authenticator Qualifications','text',false,530,'signoff'),
        ],
        'sections'=>[],
    ];
}
