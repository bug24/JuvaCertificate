<?php

function endless_round_webbing_sling_blueprint(): array
{
    $yesNo = ['Yes', 'No'];
    $field = static function (string $key, string $label, string $type, bool $required, int $sort, string $section, ?string $help = null, ?array $options = null): array {
        return ['field_key'=>$key,'label'=>$label,'field_type'=>$type,'is_required'=>$required ? 1 : 0,'sort_order'=>$sort,'pdf_section'=>$section,'help_text'=>$help,'options'=>$options];
    };
    return [
        'code'=>'ERS','short_code'=>'ERWSLNG','name'=>'Endless Round Webbing Sling',
        'description'=>'Dedicated thorough examination certificate for endless round webbing slings.',
        'validity_months'=>6,'certificate_template'=>'Certificate of Thorough Examination',
        'template_family'=>'endless_round_webbing_sling','layout_key'=>'endless-round-webbing-sling-v1',
        'source_sample'=>'ENDLESS ROUND  WEBBING SLING.jpg','schema_version'=>2,'theme_color'=>'#E67E22','identifier_label'=>'Certificate Number',
        'fields'=>[
            $field('report_date','Date of Report','date',false,10,'certificate','Defaults to examination date.'),
            $field('premises_address','Address of Premises Where Examination Was Made','textarea',true,20,'client'),
            $field('equipment_type','Equipment Type','text',true,30,'equipment'),
            $field('length','Length','text',true,40,'equipment'),
            $field('equipment_description','Description','textarea',true,50,'equipment'),
            $field('manufacturer','Manufacturer','text',true,60,'equipment'),
            $field('equipment_id_number','ID Number','text',false,70,'equipment','Defaults to equipment asset code.'),
            $field('asset_number','Asset Number','text',false,80,'equipment'),
            $field('standard','Standard','text',true,90,'equipment'),
            $field('safe_working_load','Safe Working Load','text',true,100,'equipment'),
            $field('date_of_manufacture','Date of Manufacture','date',false,110,'equipment'),
            $field('date_of_last_examination','Date of Last Thorough Examination','date',false,120,'equipment'),
            $field('first_examination_after_installation','First Examination After Installation or Assembly at a New Site?','select',true,200,'checklist',null,$yesNo),
            $field('installed_correctly','If Yes, Has the Equipment Been Installed Correctly?','select',true,210,'checklist',null,$yesNo),
            $field('examined_within_six_months','Examination Carried Out Within 6 Months?','select',true,220,'checklist',null,$yesNo),
            $field('examined_within_twelve_months','Examination Carried Out Within 12 Months?','select',true,230,'checklist',null,$yesNo),
            $field('examined_under_scheme','Examination Carried Out Under an Examination Scheme?','select',true,240,'checklist',null,$yesNo),
            $field('examined_after_exceptional_circumstances','Examination Carried Out After Exceptional Circumstances?','select',true,250,'checklist',null,$yesNo),
            $field('defect_description','Defect Identification and Description','textarea',true,300,'defects','Enter NONE when no defect exists.'),
            $field('defect_immediate_danger','Is the Defect of Immediate Danger?','select',false,310,'defects',null,$yesNo),
            $field('defect_future_danger','Could the Defect Become Dangerous?','select',false,320,'defects',null,$yesNo),
            $field('action_due_date','Date by Which Corrective Action Must Be Completed','date',false,330,'defects'),
            $field('repair_required','Repair, Renewal or Alteration Required','textarea',true,340,'results','Enter NONE where no action is required.'),
            $field('tests_carried_out','Tests Carried Out','textarea',true,350,'results','Enter NONE where no tests were carried out.'),
            $field('observations','Observations / Additional Comments','textarea',false,360,'results'),
            $field('fit_for_purpose','Is This Equipment Fit for Purpose?','select',true,400,'fitness',null,$yesNo),
            $field('inspector_name_snapshot','Inspector Name on Certificate','text',false,500,'signoff','Defaults to assigned inspector.'),
            $field('inspector_qualification_snapshot','Inspector Qualifications on Certificate','text',false,510,'signoff','Defaults to inspector profile.'),
            $field('authenticator_name','Authenticator Name','text',false,520,'signoff'),
            $field('authenticator_qualification','Authenticator Qualifications','text',false,530,'signoff'),
        ],
        'sections'=>[],
    ];
}
