<?php

return [
    'app_env' => 'production',
    'app_url' => 'https://cert.juvaoil.com',
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => 'juva_certify',
    'db_user' => 'juva_certify',
    'db_password' => 'replace-with-cpanel-database-password',
    'session_name' => 'juva_certify_session',
    // Optional. Leave blank to use <private_storage_path>/sessions.
    'session_save_path' => '',
    'session_hours' => 8,
    'remember_days' => 30,
    'allowed_origin' => 'https://cert.juvaoil.com',
    'security_salt' => 'replace-with-a-long-random-secret',
    'cron_key' => 'replace-with-a-long-random-cron-key',
    'reminder_days' => 30,
    'admin_notice_email' => 'juvaoil@gmail.com',
    'company_name' => 'JUVA-OIL SERVICES (NIG) LIMITED',
    'company_registered_address' => '52 Rumuolumeni Road, P.O Box 1997 Mile 1, Obio/Akpor, Port Harcourt',
    'company_operational_address' => 'Plot 127A Trans Amadi, Ind. Layout, Port Harcourt',
    'company_phone' => '08065164945',
    'company_email' => 'juvaoil@gmail.com',
    'company_website' => 'www.juvaoil.com',

    // Fresh install only: remove this from config.local.php after the first Super Admin is created.
    'setup_key' => 'replace-with-a-temporary-random-setup-key',

    // cPanel can usually deliver with mail(). Use mail_transport => log for local testing.
    'mail_transport' => 'smtp',
    'mail_reply_to' => 'juvaoil@gmail.com',
    'smtp_host' => 'mail.cert.juvaoil.com',
    'smtp_port' => 465,
    'smtp_encryption' => 'ssl',
    'smtp_username' => 'certificates@cert.juvaoil.com',
    'smtp_password' => 'SET_IN_CPANEL_PRIVATE_CONFIG',
    'smtp_timeout' => 15,
    'certificate_notifications_enabled' => true,
    'mail_from' => 'no-reply@cert.juvaoil.com',
    'mail_from_name' => 'JUVA Certify Manager',
];
