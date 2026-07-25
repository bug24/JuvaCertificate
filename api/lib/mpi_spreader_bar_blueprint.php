<?php

declare(strict_types=1);

function mpi_spreader_bar_blueprint(): array
{
    $field = static function (string $key, string $label, string $type, bool $required, int $sort, string $section, ?string $help = null, ?array $options = null): array {
        return ['field_key'=>$key,'label'=>$label,'field_type'=>$type,'is_required'=>$required?1:0,'sort_order'=>$sort,'pdf_section'=>$section,'help_text'=>$help,'options'=>$options];
    };
    return [
        'code'=>'MPISB','short_code'=>'MPISBAR','name'=>'MPI / NDT Spreader Bar',
        'description'=>'Dedicated visual and magnetic particle inspection certificate for spreader bars and lifting beams.',
        'validity_months'=>6,'certificate_template'=>'Visual/Magnetic Particle Inspection Certificate',
        'template_family'=>'mpi_spreader_bar','layout_key'=>'mpi-spreader-bar-v1',
        'source_sample'=>'MPI-SPREADER BAR_1.jpg','schema_version'=>2,'theme_color'=>'#334E75','identifier_label'=>'Report Number',
        'fields'=>[
            $field('report_date','Date of Report','date',true,10,'certificate'),
            $field('premises_address','Address of Premises Where Examination Was Made','textarea',true,20,'client'),
            $field('item_inspected','Item Inspected','text',true,30,'item'),
            $field('serial_number','Serial Number','text',true,40,'item'),
            $field('material_type','Type of Material','text',true,50,'item'),
            $field('inspection_area_surface_condition','Areas Inspected / Surface Condition','textarea',true,60,'item'),
            $field('standard_used','Standard Used','textarea',true,70,'item'),
            $field('acceptance_limits','Acceptance Limits','textarea',true,80,'item'),
            $field('safe_working_load','Safe Working Load','text',true,90,'item'),
            $field('dimension','Dimension','text',true,100,'item'),
            $field('magnetic_particle_equipment','Magnetic Particle Equipment','checkbox',true,200,'matrix','Select every item used.', ['Coil','Prods','Yoke','UV Light']),
            $field('magnetic_particle_medium','Magnetic Particle Medium','checkbox',true,210,'matrix','Select every applicable medium.', ['Dry','Visible','Wet','Fluorescent']),
            $field('magnetizing_current','Magnetizing Current','checkbox',true,220,'matrix','Select every current used.', ['AC','HWDC','DC']),
            $field('magnetizing_process','Magnetizing Process','checkbox',true,230,'matrix','Select the process used.', ['Continuous','Residual']),
            $field('dye_penetrant','Dye Penetrant','text',false,240,'matrix','Enter N/A or the product and batch/reference.'),
            $field('dye_developer','Dye Developer','text',false,250,'matrix','Enter N/A or the product and batch/reference.'),
            $field('dye_solvent_cleaner','Dye Solvent / Cleaner','text',false,260,'matrix','Enter N/A or the product and batch/reference.'),
            $field('nde_procedure_reference','NDE Procedure Reference Number','text',true,300,'nde'),
            $field('inspection_method','Method','text',true,310,'nde'),
            $field('remarks','Remarks','textarea',true,320,'nde'),
            $field('equipment_safe_for_use','Is This Equipment Safe for Use?','checkbox',true,400,'decision'),
            $field('inspector_name_snapshot','Inspector Name on Certificate','text',false,500,'signoff','Defaults to the assigned inspector.'),
            $field('inspector_qualification_snapshot','Inspector Qualifications on Certificate','text',false,510,'signoff','Defaults to the inspector profile.'),
            $field('authenticator_name','Authenticator Name','text',false,520,'signoff'),
            $field('authenticator_qualification','Authenticator Qualifications','text',false,530,'signoff'),
        ],
        'sections'=>[],
    ];
}
