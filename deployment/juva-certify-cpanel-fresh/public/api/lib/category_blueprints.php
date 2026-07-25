<?php

require_once __DIR__ . '/eye_bolt_blueprint.php';
require_once __DIR__ . '/hook_blueprint.php';
require_once __DIR__ . '/chain_block_blueprint.php';
require_once __DIR__ . '/endless_round_webbing_sling_blueprint.php';
require_once __DIR__ . '/lever_hoist_blueprint.php';
require_once __DIR__ . '/flat_webbing_sling_blueprint.php';
require_once __DIR__ . '/mpi_spreader_bar_blueprint.php';
require_once __DIR__ . '/general_category_blueprints.php';
function juva_category_blueprints(): array
{
    $coreFields = [
        [
            'field_key' => 'client_name',
            'label' => 'Client Name',
            'help_text' => 'Client or asset owner shown on the certificate.',
            'placeholder_text' => 'Client company name',
            'field_type' => 'text',
            'is_required' => 1,
            'sort_order' => 10,
            'pdf_section' => 'summary',
        ],
        [
            'field_key' => 'client_address',
            'label' => 'Client Address',
            'help_text' => 'Site or office address where required by the certificate.',
            'placeholder_text' => 'Trans Amadi, Port Harcourt',
            'field_type' => 'textarea',
            'is_required' => 0,
            'sort_order' => 20,
            'pdf_section' => 'summary',
        ],
        [
            'field_key' => 'inspection_location',
            'label' => 'Inspection Location',
            'help_text' => 'Location where the examination was performed.',
            'placeholder_text' => 'Operational Yard',
            'field_type' => 'text',
            'is_required' => 0,
            'sort_order' => 30,
            'pdf_section' => 'summary',
        ],
        [
            'field_key' => 'equipment_description',
            'label' => 'Equipment Description',
            'help_text' => 'Describe the inspected equipment as it should appear on the certificate.',
            'placeholder_text' => '25T Bow Shackle',
            'field_type' => 'textarea',
            'is_required' => 1,
            'sort_order' => 40,
            'pdf_section' => 'equipment',
        ],
        [
            'field_key' => 'equipment_identifier',
            'label' => 'Equipment ID / Tag Number',
            'help_text' => 'Tag, asset number, serial or ID used by the client.',
            'placeholder_text' => 'AOL-SHK-014',
            'field_type' => 'text',
            'is_required' => 1,
            'sort_order' => 50,
            'pdf_section' => 'equipment',
        ],
        [
            'field_key' => 'manufacturer',
            'label' => 'Manufacturer',
            'help_text' => 'Original manufacturer where known.',
            'placeholder_text' => 'Crosby',
            'field_type' => 'text',
            'is_required' => 0,
            'sort_order' => 60,
            'pdf_section' => 'equipment',
        ],
        [
            'field_key' => 'date_of_manufacture',
            'label' => 'Date of Manufacture',
            'help_text' => 'Manufacture date shown on the certificate where available.',
            'field_type' => 'date',
            'is_required' => 0,
            'sort_order' => 70,
            'pdf_section' => 'equipment',
        ],
        [
            'field_key' => 'date_of_previous_examination',
            'label' => 'Date of Previous Examination',
            'help_text' => 'Previous thorough examination date.',
            'field_type' => 'date',
            'is_required' => 0,
            'sort_order' => 80,
            'pdf_section' => 'examination',
        ],
        [
            'field_key' => 'date_of_current_examination',
            'label' => 'Date of Current Examination',
            'help_text' => 'Current examination date as shown on the PDF.',
            'field_type' => 'date',
            'is_required' => 1,
            'sort_order' => 90,
            'pdf_section' => 'examination',
        ],
        [
            'field_key' => 'safe_working_load',
            'label' => 'SWL / WLL / Capacity',
            'help_text' => 'Rated safe working load, working load limit or capacity.',
            'placeholder_text' => '25T',
            'field_type' => 'text',
            'is_required' => 0,
            'sort_order' => 100,
            'pdf_section' => 'examination',
        ],
        [
            'field_key' => 'result_summary',
            'label' => 'Result Summary',
            'help_text' => 'Overall condition and decision statement.',
            'placeholder_text' => 'Safe to use',
            'field_type' => 'pass_fail',
            'is_required' => 1,
            'sort_order' => 110,
            'pdf_section' => 'decision',
        ],
        [
            'field_key' => 'defects_affecting_safety',
            'label' => 'Defects Affecting Safety',
            'help_text' => 'Defects found that affect safe use.',
            'field_type' => 'textarea',
            'is_required' => 0,
            'sort_order' => 120,
            'pdf_section' => 'decision',
        ],
        [
            'field_key' => 'repairs_or_tests_required',
            'label' => 'Repairs / Tests Required',
            'help_text' => 'Repairs, further tests or conditions before return to service.',
            'field_type' => 'textarea',
            'is_required' => 0,
            'sort_order' => 130,
            'pdf_section' => 'decision',
        ],
        [
            'field_key' => 'additional_remarks',
            'label' => 'Additional Remarks',
            'help_text' => 'Any extra remarks to appear on the certificate.',
            'field_type' => 'textarea',
            'is_required' => 0,
            'sort_order' => 140,
            'pdf_section' => 'remarks',
        ],
        [
            'field_key' => 'inspector_name',
            'label' => 'Competent Person / Inspector Name',
            'field_type' => 'text',
            'is_required' => 1,
            'sort_order' => 150,
            'pdf_section' => 'signoff',
        ],
        [
            'field_key' => 'inspector_qualification',
            'label' => 'Inspector Qualification',
            'field_type' => 'text',
            'is_required' => 0,
            'sort_order' => 160,
            'pdf_section' => 'signoff',
        ],
        [
            'field_key' => 'next_due_date',
            'label' => 'Next Due Date',
            'field_type' => 'date',
            'is_required' => 1,
            'sort_order' => 170,
            'pdf_section' => 'signoff',
        ],
    ];

    return [
        [
            'code' => 'CCU',
            'short_code' => 'CCUVIS',
            'name' => 'CCU Visual Inspection',
            'description' => 'Visual examination certificate for cargo carrying units, lifting sets, shackles, load-test records and MPI/visual inspection details.',
            'validity_months' => 6,
            'certificate_template' => 'Certificate of Visual Examination',
            'template_family' => 'ccu_visual',
            'layout_key' => 'ccu-visual-v1',
            'source_sample' => null,
            'schema_version' => 2,
            'theme_color' => '#0F4C81',
            'identifier_label' => 'Certificate Number',
            'fields' => [
                ['field_key' => 'client_name', 'label' => 'Certificate Client Name Override', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 10, 'pdf_section' => 'client'],
                ['field_key' => 'client_address', 'label' => 'Certificate Client Address Override', 'field_type' => 'textarea', 'is_required' => 0, 'sort_order' => 20, 'pdf_section' => 'client'],
                ['field_key' => 'location_line_1', 'label' => 'Location of Inspection - Line 1', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 30, 'pdf_section' => 'client'],
                ['field_key' => 'location_line_2', 'label' => 'Location of Inspection - Line 2', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 40, 'pdf_section' => 'client'],
                ['field_key' => 'customer_order_number', 'label' => 'Customer Order Number', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 50, 'pdf_section' => 'metadata'],
                ['field_key' => 'unit_id_number', 'label' => 'Unit ID Number', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 100, 'pdf_section' => 'unit'],
                ['field_key' => 'asset_number', 'label' => 'Asset Number', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 110, 'pdf_section' => 'unit'],
                ['field_key' => 'unit_quantity', 'label' => 'Unit Quantity', 'field_type' => 'number', 'is_required' => 1, 'sort_order' => 120, 'pdf_section' => 'unit'],
                ['field_key' => 'description_of_unit', 'label' => 'Description of Unit', 'field_type' => 'textarea', 'is_required' => 1, 'sort_order' => 130, 'pdf_section' => 'unit'],
                ['field_key' => 'unit_tare_weight', 'label' => 'Tare Weight', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 140, 'pdf_section' => 'unit'],
                ['field_key' => 'unit_swl_payload', 'label' => 'SWL / Payload', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 150, 'pdf_section' => 'unit'],
                ['field_key' => 'unit_gross_weight', 'label' => 'Gross Weight', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 160, 'pdf_section' => 'unit'],
                ['field_key' => 'sling_id', 'label' => 'Sling ID', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 200, 'pdf_section' => 'sling'],
                ['field_key' => 'sling_quantity', 'label' => 'Sling Quantity', 'field_type' => 'number', 'is_required' => 1, 'sort_order' => 210, 'pdf_section' => 'sling'],
                ['field_key' => 'sling_quantity_unit', 'label' => 'Sling Quantity Unit', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 220, 'pdf_section' => 'sling'],
                ['field_key' => 'sling_details', 'label' => 'Sling Details', 'field_type' => 'textarea', 'is_required' => 1, 'sort_order' => 230, 'pdf_section' => 'sling'],
                ['field_key' => 'sling_tare_na', 'label' => 'Sling Tare / N/A', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 240, 'pdf_section' => 'sling'],
                ['field_key' => 'sling_swl', 'label' => 'Sling SWL', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 250, 'pdf_section' => 'sling'],
                ['field_key' => 'sling_gross_na', 'label' => 'Sling Gross / N/A', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 260, 'pdf_section' => 'sling'],
                ['field_key' => 'load_unit_id', 'label' => 'Load Test - Unit ID', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 300, 'pdf_section' => 'load_test'],
                ['field_key' => 'load_tested_by', 'label' => 'Load Test - Tested By', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 310, 'pdf_section' => 'load_test'],
                ['field_key' => 'load_test_certificate_number', 'label' => 'Load Test Certificate Number', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 320, 'pdf_section' => 'load_test'],
                ['field_key' => 'load_proof_load_test', 'label' => 'Proof Load Test', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 330, 'pdf_section' => 'load_test'],
                ['field_key' => 'load_test_date', 'label' => 'Load Test Date', 'field_type' => 'date', 'is_required' => 1, 'sort_order' => 340, 'pdf_section' => 'load_test'],
                ['field_key' => 'sling_test_sling_id', 'label' => 'Sling Test - Sling ID', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 350, 'pdf_section' => 'load_test'],
                ['field_key' => 'sling_manufacturer', 'label' => 'Sling Manufacturer', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 360, 'pdf_section' => 'load_test'],
                ['field_key' => 'sling_oem_certificate_number', 'label' => 'Sling OEM Certificate Number', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 370, 'pdf_section' => 'load_test'],
                ['field_key' => 'sling_proof_load_test', 'label' => 'Sling Proof Load Test / Dash', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 380, 'pdf_section' => 'load_test'],
                ['field_key' => 'sling_test_date', 'label' => 'Sling Test Date', 'field_type' => 'date', 'is_required' => 1, 'sort_order' => 390, 'pdf_section' => 'load_test'],
                ['field_key' => 'standards', 'label' => 'Standards', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 400, 'pdf_section' => 'inspection'],
                ['field_key' => 'equipment_type', 'label' => 'Equipment Type', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 410, 'pdf_section' => 'inspection'],
                ['field_key' => 'contrast_media', 'label' => 'Contrast Media', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 420, 'pdf_section' => 'inspection'],
                ['field_key' => 'test_procedure', 'label' => 'Test Procedure', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 430, 'pdf_section' => 'inspection'],
                ['field_key' => 'pole_spacing', 'label' => 'Pole Spacing', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 440, 'pdf_section' => 'inspection'],
                ['field_key' => 'indicator', 'label' => 'Indicator', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 450, 'pdf_section' => 'inspection'],
                ['field_key' => 'technique', 'label' => 'Technique', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 460, 'pdf_section' => 'inspection'],
                ['field_key' => 'test_method', 'label' => 'Test Method', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 470, 'pdf_section' => 'inspection'],
                ['field_key' => 'test_result', 'label' => 'Test Result', 'field_type' => 'textarea', 'is_required' => 1, 'sort_order' => 480, 'pdf_section' => 'inspection'],
                ['field_key' => 'demagnetization', 'label' => 'Demagnetization', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 500, 'pdf_section' => 'inspection'],
                ['field_key' => 'colour_code', 'label' => 'Colour Code', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 510, 'pdf_section' => 'inspection'],
                ['field_key' => 'inspector_name', 'label' => 'Inspector Name Override', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 600, 'pdf_section' => 'signatures'],
                ['field_key' => 'inspector_qualifications', 'label' => 'Inspector Qualifications', 'field_type' => 'textarea', 'is_required' => 1, 'sort_order' => 610, 'pdf_section' => 'signatures'],
                ['field_key' => 'authenticator_name', 'label' => 'Authenticator Name', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 620, 'pdf_section' => 'signatures'],
                ['field_key' => 'authenticator_qualifications', 'label' => 'Authenticator Qualifications', 'field_type' => 'textarea', 'is_required' => 1, 'sort_order' => 630, 'pdf_section' => 'signatures'],
            ],
            'sections' => [
                [
                    'section_key' => 'shackle_details',
                    'label' => 'Shackle Details',
                    'help_text' => 'Add each shackle ID separately as shown on the CCU certificate.',
                    'min_rows' => 1,
                    'max_rows' => 12,
                    'pdf_section' => 'shackle_table',
                    'sort_order' => 10,
                    'columns' => [
                        ['column_key' => 'shackle_id', 'label' => 'Shackle ID', 'column_type' => 'text', 'is_required' => 1, 'sort_order' => 10],
                        ['column_key' => 'quantity', 'label' => 'Quantity', 'column_type' => 'number', 'is_required' => 1, 'sort_order' => 20],
                        ['column_key' => 'quantity_unit', 'label' => 'Quantity Unit', 'column_type' => 'text', 'is_required' => 1, 'sort_order' => 30],
                        ['column_key' => 'shackle_description', 'label' => 'Shackle Description', 'column_type' => 'textarea', 'is_required' => 1, 'sort_order' => 40],
                        ['column_key' => 'tare_na', 'label' => 'Tare / N/A', 'column_type' => 'text', 'is_required' => 0, 'sort_order' => 50],
                        ['column_key' => 'swl', 'label' => 'SWL', 'column_type' => 'text', 'is_required' => 1, 'sort_order' => 60],
                        ['column_key' => 'gross_na', 'label' => 'Gross / N/A', 'column_type' => 'text', 'is_required' => 0, 'sort_order' => 70],
                    ],
                ],
            ],
        ],
        [
            'code' => 'CHBLK',
            'short_code' => 'CHBLK',
            'name' => 'Chain Block',
            'description' => 'Thorough examination of chain blocks and associated operating components.',
            'validity_months' => 6,
            'certificate_template' => 'Report of Thorough Examination of Lifting Equipment',
            'template_family' => 'chain_block',
            'layout_key' => 'chain-block-v1',
            'source_sample' => 'CHAIN BLOCK.pdf',
            'schema_version' => 2,
            'theme_color' => '#7A1F1F',
            'identifier_label' => 'Report Number',
            'fields' => juva_chain_block_fields(),
            'sections' => [],
        ],
        lever_hoist_blueprint(),
        flat_webbing_sling_blueprint(),
        endless_round_webbing_sling_blueprint(),
        [
            'code' => 'WRS',
            'short_code' => 'WRSLNG',
            'name' => 'Wire Rope Sling',
            'description' => 'Thorough examination of wire rope slings, fittings and rope condition.',
            'validity_months' => 6,
            'certificate_template' => 'Report of Thorough Examination of Lifting Equipment',
            'template_family' => 'rigging_accessory',
            'layout_key' => 'rigging-accessory-v1',
            'source_sample' => 'WIRE ROPE SLING.pdf',
            'schema_version' => 1,
            'theme_color' => '#1E8449',
            'identifier_label' => 'Report Number',
            'fields' => array_merge($coreFields, [
                ['field_key' => 'rope_diameter', 'label' => 'Rope Diameter', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 180, 'pdf_section' => 'equipment'],
                ['field_key' => 'rope_construction', 'label' => 'Rope Construction', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 190, 'pdf_section' => 'equipment'],
                ['field_key' => 'leg_count', 'label' => 'Leg Count', 'field_type' => 'number', 'is_required' => 0, 'sort_order' => 200, 'pdf_section' => 'equipment'],
                ['field_key' => 'termination_type', 'label' => 'Ferrule / Splice Type', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 210, 'pdf_section' => 'equipment'],
            ]),
            'sections' => [
                [
                    'section_key' => 'wire_rope_components',
                    'label' => 'Wire Rope Sling Components',
                    'help_text' => 'Capture rope legs, end fittings or sub-components inspected on the sling.',
                    'min_rows' => 1,
                    'max_rows' => 12,
                    'pdf_section' => 'inspection_table',
                    'sort_order' => 10,
                    'columns' => [
                        ['column_key' => 'serial_number', 'label' => 'Serial / Tag Number', 'column_type' => 'text', 'is_required' => 0, 'sort_order' => 10],
                        ['column_key' => 'description', 'label' => 'Description', 'column_type' => 'text', 'is_required' => 1, 'sort_order' => 20],
                        ['column_key' => 'wll', 'label' => 'SWL / WLL', 'column_type' => 'text', 'is_required' => 0, 'sort_order' => 30],
                        ['column_key' => 'date_examined', 'label' => 'Date Examined', 'column_type' => 'date', 'is_required' => 0, 'sort_order' => 40],
                        ['column_key' => 'remarks', 'label' => 'Remarks', 'column_type' => 'textarea', 'is_required' => 0, 'sort_order' => 50],
                        ['column_key' => 'status', 'label' => 'Status', 'column_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 60],
                    ],
                ],
            ],
        ],
        [
            'code' => 'SHK',
            'short_code' => 'SHKACC',
            'name' => 'Shackles and Accessories',
            'description' => 'Thorough examination of shackles, pins and related lifting accessories.',
            'validity_months' => 6,
            'certificate_template' => 'Report of Thorough Examination of Lifting Equipment',
            'template_family' => 'rigging_accessory',
            'layout_key' => 'rigging-accessory-v1',
            'source_sample' => 'SHACKLES AND ACCESSORIES.pdf',
            'schema_version' => 1,
            'theme_color' => '#18794E',
            'identifier_label' => 'Report Number',
            'fields' => array_merge($coreFields, [
                ['field_key' => 'accessory_type', 'label' => 'Accessory Type', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 180, 'pdf_section' => 'equipment'],
                ['field_key' => 'size_marking', 'label' => 'Size / Marking', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 190, 'pdf_section' => 'equipment'],
                ['field_key' => 'pin_condition', 'label' => 'Pin / Thread Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 200, 'pdf_section' => 'inspection'],
                ['field_key' => 'body_distortion_check', 'label' => 'Body Distortion Check', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 210, 'pdf_section' => 'inspection'],
            ]),
            'sections' => [
                [
                    'section_key' => 'accessory_items',
                    'label' => 'Accessory Inspection Items',
                    'help_text' => 'Use when one certificate covers multiple shackles or accessories.',
                    'min_rows' => 1,
                    'max_rows' => 20,
                    'pdf_section' => 'inspection_table',
                    'sort_order' => 10,
                    'columns' => [
                        ['column_key' => 'serial_number', 'label' => 'Serial / ID Number', 'column_type' => 'text', 'is_required' => 0, 'sort_order' => 10],
                        ['column_key' => 'description', 'label' => 'Description', 'column_type' => 'text', 'is_required' => 1, 'sort_order' => 20],
                        ['column_key' => 'swl_wll', 'label' => 'SWL / WLL', 'column_type' => 'text', 'is_required' => 0, 'sort_order' => 30],
                        ['column_key' => 'manufacturer', 'label' => 'Manufacturer', 'column_type' => 'text', 'is_required' => 0, 'sort_order' => 40],
                        ['column_key' => 'remarks', 'label' => 'Remarks', 'column_type' => 'textarea', 'is_required' => 0, 'sort_order' => 50],
                        ['column_key' => 'status', 'label' => 'Status', 'column_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 60],
                    ],
                ],
            ],
        ],
        eye_bolt_blueprint(),
        hook_blueprint(),
        [
            'code' => 'HCLMP',
            'short_code' => 'HCLAMP',
            'name' => 'Horizontal Clamp',
            'description' => 'Thorough examination of horizontal lifting clamps and gripping surfaces.',
            'validity_months' => 6,
            'certificate_template' => 'Report of Thorough Examination of Lifting Equipment',
            'template_family' => 'sling_clamp_general',
            'layout_key' => 'clamp-general-v1',
            'source_sample' => 'HORIZONTAL CLAMPS.pdf',
            'schema_version' => 1,
            'theme_color' => '#455A64',
            'identifier_label' => 'Report Number',
            'fields' => array_merge($coreFields, [
                ['field_key' => 'jaw_opening_range', 'label' => 'Jaw Opening Range', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 180, 'pdf_section' => 'equipment'],
                ['field_key' => 'plate_thickness_range', 'label' => 'Plate Thickness Range', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 190, 'pdf_section' => 'equipment'],
                ['field_key' => 'lock_condition', 'label' => 'Locking Mechanism Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 200, 'pdf_section' => 'inspection'],
            ]),
            'sections' => [],
        ],
        [
            'code' => 'VCLMP',
            'short_code' => 'VCLAMP',
            'name' => 'Vertical Clamp',
            'description' => 'Thorough examination of vertical lifting clamps and jaw condition.',
            'validity_months' => 6,
            'certificate_template' => 'Report of Thorough Examination of Lifting Equipment',
            'template_family' => 'sling_clamp_general',
            'layout_key' => 'clamp-general-v1',
            'source_sample' => 'VERTICAL CLAMP.pdf',
            'schema_version' => 1,
            'theme_color' => '#455A64',
            'identifier_label' => 'Report Number',
            'fields' => array_merge($coreFields, [
                ['field_key' => 'jaw_opening_range', 'label' => 'Jaw Opening Range', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 180, 'pdf_section' => 'equipment'],
                ['field_key' => 'lock_condition', 'label' => 'Locking Mechanism Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 190, 'pdf_section' => 'inspection'],
                ['field_key' => 'jaw_tooth_condition', 'label' => 'Jaw / Tooth Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 200, 'pdf_section' => 'inspection'],
            ]),
            'sections' => [],
        ],
        [
            'code' => 'UCLMP',
            'short_code' => 'UCLAMP',
            'name' => 'Universal Clamp',
            'description' => 'Thorough examination of universal clamps and locking assemblies.',
            'validity_months' => 6,
            'certificate_template' => 'Report of Thorough Examination of Lifting Equipment',
            'template_family' => 'sling_clamp_general',
            'layout_key' => 'clamp-general-v1',
            'source_sample' => 'UNIVERSAL CLAMP.pdf',
            'schema_version' => 1,
            'theme_color' => '#455A64',
            'identifier_label' => 'Report Number',
            'fields' => array_merge($coreFields, [
                ['field_key' => 'jaw_opening_range', 'label' => 'Jaw Opening Range', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 180, 'pdf_section' => 'equipment'],
                ['field_key' => 'lock_condition', 'label' => 'Locking Mechanism Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 190, 'pdf_section' => 'inspection'],
                ['field_key' => 'body_condition', 'label' => 'Body Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 200, 'pdf_section' => 'inspection'],
            ]),
            'sections' => [],
        ],
        [
            'code' => 'LFMAG',
            'short_code' => 'LFTMAG',
            'name' => 'Lifting Magnet',
            'description' => 'Thorough examination of lifting magnets, face condition and holding test results.',
            'validity_months' => 6,
            'certificate_template' => 'Report of Thorough Examination of Lifting Equipment',
            'template_family' => 'sling_clamp_general',
            'layout_key' => 'magnet-general-v1',
            'source_sample' => 'LIFTING MAGNET.pdf',
            'schema_version' => 1,
            'theme_color' => '#2E7D32',
            'identifier_label' => 'Report Number',
            'fields' => array_merge($coreFields, [
                ['field_key' => 'rated_capacity', 'label' => 'Rated Capacity', 'field_type' => 'text', 'is_required' => 1, 'sort_order' => 180, 'pdf_section' => 'equipment'],
                ['field_key' => 'contact_face_condition', 'label' => 'Contact Face Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 190, 'pdf_section' => 'inspection'],
                ['field_key' => 'holding_test_result', 'label' => 'Holding / Proof Test Result', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 200, 'pdf_section' => 'inspection'],
            ]),
            'sections' => [],
        ],
        [
            'code' => 'PLTTRK',
            'short_code' => 'PLTTRK',
            'name' => 'Pallet Truck',
            'description' => 'Functional and safety inspection of pallet trucks and hydraulic assemblies.',
            'validity_months' => 12,
            'certificate_template' => 'Report of Thorough Examination of Lifting Equipment',
            'template_family' => 'sling_clamp_general',
            'layout_key' => 'pallet-truck-v1',
            'source_sample' => 'PALLET TRUCK.pdf',
            'schema_version' => 1,
            'theme_color' => '#8E44AD',
            'identifier_label' => 'Report Number',
            'fields' => array_merge($coreFields, [
                ['field_key' => 'fork_length', 'label' => 'Fork Length', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 180, 'pdf_section' => 'equipment'],
                ['field_key' => 'hydraulic_condition', 'label' => 'Hydraulic Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 190, 'pdf_section' => 'inspection'],
                ['field_key' => 'wheel_roller_condition', 'label' => 'Wheel / Roller Condition', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 200, 'pdf_section' => 'inspection'],
                ['field_key' => 'functional_test_result', 'label' => 'Functional Test Result', 'field_type' => 'pass_fail', 'is_required' => 1, 'sort_order' => 210, 'pdf_section' => 'inspection'],
            ]),
            'sections' => [],
        ],
        mpi_spreader_bar_blueprint(),
        general_lifting_accessories_blueprint(),
        general_thorough_examination_blueprint(),
    ];
}

function seed_category_blueprints(PDO $pdo): array
{
    $created = 0;
    $updated = 0;
    $blueprints = juva_category_blueprints();

    foreach ($blueprints as $blueprint) {
        $select = $pdo->prepare('SELECT * FROM certification_categories WHERE code = ? LIMIT 1');
        $select->execute([$blueprint['code']]);
        $category = $select->fetch();

        if ($category) {
            $categoryId = (int) $category['id'];
            $needsRefresh = (int) ($category['schema_version'] ?? 0) !== (int) $blueprint['schema_version']
                || (string) ($category['template_family'] ?? '') !== (string) $blueprint['template_family']
                || (string) ($category['layout_key'] ?? '') !== (string) $blueprint['layout_key'];

            $updateCategory = $pdo->prepare('UPDATE certification_categories SET short_code = ?, name = ?, description = ?, validity_months = ?, certificate_template = ?, template_family = ?, layout_key = ?, source_sample = ?, schema_version = ?, certificate_prefix = ?, identifier_label = ?, theme_color = ?, requires_review = ?, status = ?, updated_at = ? WHERE id = ?');
            $updateCategory->execute([
                $blueprint['short_code'],
                $blueprint['name'],
                $blueprint['description'],
                $blueprint['validity_months'],
                $blueprint['certificate_template'],
                $blueprint['template_family'],
                $blueprint['layout_key'],
                $blueprint['source_sample'],
                $blueprint['schema_version'],
                'JUVA-' . $blueprint['code'],
                $blueprint['identifier_label'],
                $blueprint['theme_color'],
                1,
                (string) ($category['status'] ?? 'active'),
                now_sql(),
                $categoryId,
            ]);
            if ($needsRefresh) {
                $updated++;
            }
        } else {
            $insertCategory = $pdo->prepare('INSERT INTO certification_categories (code, short_code, name, description, validity_months, certificate_template, template_family, layout_key, source_sample, schema_version, certificate_prefix, identifier_label, theme_color, requires_review, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insertCategory->execute([
                $blueprint['code'],
                $blueprint['short_code'],
                $blueprint['name'],
                $blueprint['description'],
                $blueprint['validity_months'],
                $blueprint['certificate_template'],
                $blueprint['template_family'],
                $blueprint['layout_key'],
                $blueprint['source_sample'],
                $blueprint['schema_version'],
                'JUVA-' . $blueprint['code'],
                $blueprint['identifier_label'],
                $blueprint['theme_color'],
                1,
                'active',
                now_sql(),
                now_sql(),
            ]);
            $categoryId = (int) $pdo->lastInsertId();
            $created++;
        }

        $activeTemplateStmt = $pdo->prepare('SELECT * FROM form_templates WHERE category_id = ? AND status = ? ORDER BY version DESC LIMIT 1');
        $activeTemplateStmt->execute([$categoryId, 'active']);
        $activeTemplate = $activeTemplateStmt->fetch();

        if ($activeTemplate && (int) ($category['schema_version'] ?? 0) === (int) $blueprint['schema_version']) {
            $fieldKeyStmt = $pdo->prepare('SELECT field_key FROM form_fields WHERE template_id = ? ORDER BY field_key');
            $fieldKeyStmt->execute([(int) $activeTemplate['id']]);
            $currentFieldKeys = $fieldKeyStmt->fetchAll(PDO::FETCH_COLUMN);
            $blueprintFieldKeys = array_column($blueprint['fields'] ?? [], 'field_key');
            sort($blueprintFieldKeys);

            if ($currentFieldKeys === $blueprintFieldKeys) {
                continue;
            }
        }
        $templateId = null;
        $canReuseActiveTemplate = false;

        if ($activeTemplate) {
            $usageStmt = $pdo->prepare('SELECT COUNT(*) AS linked_rows FROM inspections WHERE form_template_id = ?');
            $usageStmt->execute([(int) $activeTemplate['id']]);
            $linkedRows = (int) (($usageStmt->fetch()['linked_rows'] ?? 0));
            $canReuseActiveTemplate = $linkedRows === 0 && (int) ($category['schema_version'] ?? 0) === (int) $blueprint['schema_version'];
        }

        if ($canReuseActiveTemplate) {
            $templateId = (int) $activeTemplate['id'];
        } else {
            $versionStmt = $pdo->prepare('SELECT MAX(version) AS max_version FROM form_templates WHERE category_id = ?');
            $versionStmt->execute([$categoryId]);
            $maxVersion = (int) (($versionStmt->fetch()['max_version'] ?? 0));

            $pdo->prepare('UPDATE form_templates SET status = ? WHERE category_id = ? AND status = ?')
                ->execute(['archived', $categoryId, 'active']);

            $insertTemplate = $pdo->prepare('INSERT INTO form_templates (category_id, name, version, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
            $insertTemplate->execute([
                $categoryId,
                $blueprint['name'] . ' Form',
                $maxVersion + 1,
                'active',
                now_sql(),
                now_sql(),
            ]);
            $templateId = (int) $pdo->lastInsertId();
        }

        $pdo->prepare('DELETE FROM form_fields WHERE template_id = ?')->execute([$templateId]);
        $insertField = $pdo->prepare('INSERT INTO form_fields (template_id, field_key, label, help_text, placeholder_text, field_type, is_required, appears_on_pdf, editable_after_approval, pdf_section, repeatable_group, options_json, validation_json, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($blueprint['fields'] as $field) {
            $insertField->execute([
                $templateId,
                $field['field_key'],
                $field['label'],
                $field['help_text'] ?? null,
                $field['placeholder_text'] ?? null,
                $field['field_type'],
                (int) ($field['is_required'] ?? 0),
                (int) ($field['appears_on_pdf'] ?? 1),
                (int) ($field['editable_after_approval'] ?? 0),
                $field['pdf_section'] ?? null,
                $field['repeatable_group'] ?? null,
                isset($field['options']) ? json_encode($field['options'], JSON_UNESCAPED_SLASHES) : null,
                isset($field['validation']) ? json_encode($field['validation'], JSON_UNESCAPED_SLASHES) : null,
                (int) $field['sort_order'],
                now_sql(),
            ]);
        }

        $sectionIds = [];
        $pdo->prepare('DELETE c FROM form_repeatable_columns c JOIN form_repeatable_sections s ON s.id = c.section_id WHERE s.template_id = ?')->execute([$templateId]);
        $pdo->prepare('DELETE FROM form_repeatable_sections WHERE template_id = ?')->execute([$templateId]);
        $insertSection = $pdo->prepare('INSERT INTO form_repeatable_sections (template_id, section_key, label, help_text, min_rows, max_rows, pdf_section, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertColumn = $pdo->prepare('INSERT INTO form_repeatable_columns (section_id, column_key, label, column_type, is_required, options_json, validation_json, placeholder_text, appears_on_pdf, editable_after_approval, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        foreach ($blueprint['sections'] as $section) {
            $insertSection->execute([
                $templateId,
                $section['section_key'],
                $section['label'],
                $section['help_text'] ?? null,
                (int) ($section['min_rows'] ?? 0),
                $section['max_rows'] ?? null,
                $section['pdf_section'] ?? null,
                (int) ($section['sort_order'] ?? 0),
                now_sql(),
                now_sql(),
            ]);
            $sectionId = (int) $pdo->lastInsertId();
            $sectionIds[] = $sectionId;
            foreach ($section['columns'] as $column) {
                $insertColumn->execute([
                    $sectionId,
                    $column['column_key'],
                    $column['label'],
                    $column['column_type'],
                    (int) ($column['is_required'] ?? 0),
                    isset($column['options']) ? json_encode($column['options'], JSON_UNESCAPED_SLASHES) : null,
                    isset($column['validation']) ? json_encode($column['validation'], JSON_UNESCAPED_SLASHES) : null,
                    $column['placeholder_text'] ?? null,
                    (int) ($column['appears_on_pdf'] ?? 1),
                    (int) ($column['editable_after_approval'] ?? 0),
                    (int) ($column['sort_order'] ?? 0),
                    now_sql(),
                    now_sql(),
                ]);
            }
        }
    }

    return ['created' => $created, 'updated' => $updated, 'total' => count($blueprints)];
}



