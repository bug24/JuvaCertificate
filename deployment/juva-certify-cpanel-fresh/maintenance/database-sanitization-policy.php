<?php

function juva_preserved_tables()
{
    return [
        'roles',
        'certificate_branding_settings',
        'certification_categories',
        'form_templates',
        'form_fields',
        'form_repeatable_sections',
        'form_repeatable_columns',
    ];
}

function juva_purge_order()
{
    return [
        'email_notifications', 'verification_logs', 'certificate_revisions',
        'inspection_attachments', 'inspection_comments', 'approvals',
        'inspection_items', 'inspection_values', 'legacy_inspection_details',
        'certificates', 'inspections', 'certificate_sequences',
        'auth_tokens', 'otp_challenges', 'remember_tokens', 'auth_attempts',
        'audit_logs', 'equipment', 'users', 'clients', 'legacy_migration_runs',
        'juva_equip_insp_check_list', 'juva_equipment_insp_calibration',
        'juva_equipment_inspt_ccu', 'juva_equipment_inspt_excavator',
        'juva_equipment_inspt_gen_nde', 'juva_equipment_inspt_mpi',
        'juva_equipment_inspt_shackle', 'juva_equipment_inspt_sub',
        'juva_equipment_inspt_master', 'juva_equipment_master',
        'juva_client_master', 'user_access', 'user_profile', 'code_param_desc',
        'code_param_tab', 'department_tab', 'folder_grouping',
        'message_master_sub', 'message_master_tab', 'system_configuration_add',
        'system_configuration', 'audit_trail',
    ];
}

function juva_table_groups()
{
    return [
        'roles' => 'C', 'users' => 'C',
        'certificate_branding_settings' => 'D',
        'certification_categories' => 'E', 'form_templates' => 'E',
        'form_fields' => 'E', 'form_repeatable_sections' => 'E',
        'form_repeatable_columns' => 'E',
        'clients' => 'F', 'equipment' => 'F',
        'inspections' => 'G', 'inspection_values' => 'G',
        'inspection_items' => 'G', 'inspection_comments' => 'G',
        'approvals' => 'G',
        'certificates' => 'H', 'certificate_revisions' => 'H',
        'certificate_sequences' => 'H',
        'inspection_attachments' => 'I',
        'verification_logs' => 'J',
        'audit_logs' => 'K', 'audit_trail' => 'K',
        'auth_attempts' => 'L', 'auth_tokens' => 'L',
        'otp_challenges' => 'L', 'remember_tokens' => 'L',
        'legacy_inspection_details' => 'M', 'legacy_migration_runs' => 'M',
        'juva_client_master' => 'M', 'juva_equipment_master' => 'M',
        'juva_equipment_inspt_master' => 'M', 'juva_equip_insp_check_list' => 'M',
        'juva_equipment_insp_calibration' => 'M', 'juva_equipment_inspt_ccu' => 'M',
        'juva_equipment_inspt_excavator' => 'M', 'juva_equipment_inspt_gen_nde' => 'M',
        'juva_equipment_inspt_mpi' => 'M', 'juva_equipment_inspt_shackle' => 'M',
        'juva_equipment_inspt_sub' => 'M', 'user_profile' => 'M',
        'user_access' => 'M', 'code_param_tab' => 'M', 'code_param_desc' => 'M',
        'department_tab' => 'M', 'folder_grouping' => 'M',
        'message_master_tab' => 'M', 'message_master_sub' => 'M',
        'system_configuration' => 'M', 'system_configuration_add' => 'M',
        'email_notifications' => 'N',
    ];
}

