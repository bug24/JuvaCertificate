<?php
function juva_chain_block_fields(): array
{
    $d = [
        ['report_date','Date of Report','date',0,'certificate'], ['premises_address','Address of Premises Where Examination Was Made','textarea',1,'client'],
        ['equipment_type','Equipment','text',1,'equipment'], ['equipment_description','Description','textarea',1,'equipment'], ['manufacturer','Manufacturer','text',0,'equipment'],
        ['equipment_id_number','ID Number','text',0,'equipment'], ['asset_number','Asset Number','text',0,'equipment'], ['chain_dimensions','Chain Dimensions','text',1,'equipment'],
        ['standard','Standard','text',1,'equipment'], ['safe_working_load','Safe Working Load','text',1,'equipment'], ['date_of_manufacture','Date of Manufacture','date',0,'equipment'],
        ['date_of_last_examination','Date of Last Thorough Examination','date',1,'equipment'],
        ['first_examination_new_site','First Examination After Installation or Assembly at a New Site?','select',1,'checklist'],
        ['installed_correctly','If Yes, Has the Equipment Been Installed Correctly?','select',1,'checklist'], ['exam_within_6_months','Examination Carried Out Within 6 Months?','select',1,'checklist'],
        ['exam_within_12_months','Examination Carried Out Within 12 Months?','select',1,'checklist'], ['exam_scheme','Examination Carried Out in Accordance With an Examination Scheme?','select',1,'checklist'],
        ['exceptional_circumstances','Examination Carried Out After Exceptional Circumstances?','select',1,'checklist'],
        ['defect_description','Identification and Description of Defects','textarea',1,'defects'], ['defect_immediate_danger','Is the Defect of Immediate Danger?','select',0,'defects'],
        ['defect_future_danger','Could the Defect Become Dangerous?','select',0,'defects'], ['action_due_date','Date by Which Action Must Be Taken','date',0,'defects'],
        ['repair_required','Repair, Renewal or Alteration Required','textarea',0,'defects'], ['tests_carried_out','Tests Carried Out','textarea',1,'defects'],
        ['observations','Observations / Additional Comments','textarea',0,'defects'], ['fit_for_purpose','Is This Equipment Fit for Purpose?','select',1,'fitness'],
        ['inspector_name_snapshot','Inspector Name on Certificate','text',0,'signoff'], ['inspector_qualification_snapshot','Inspector Qualifications on Certificate','text',0,'signoff'],
        ['inspector_signature_name','Inspector Signature / Signatory Mark','text',1,'signoff'], ['authenticator_name','Authenticator Name','text',1,'signoff'],
        ['authenticator_qualification','Authenticator Qualifications','text',1,'signoff'], ['authenticator_signature_name','Authenticator Signature / Signatory Mark','text',1,'signoff'],
    ];
    $fields = [];
    foreach ($d as $i => $v) {
        $fields[] = ['field_key'=>$v[0], 'label'=>$v[1], 'field_type'=>$v[2], 'is_required'=>$v[3], 'pdf_section'=>$v[4],
            'sort_order'=>($i+1)*10, 'options_json'=>$v[2] === 'select' ? '["Yes","No"]' : null];
    }
    return $fields;
}
