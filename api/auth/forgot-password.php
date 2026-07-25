<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$input = json_input();
$errors = validate_required($input, ['email']);
if ($errors) {
    api_error('Please provide your email address.', 422, $errors);
}
$email = strtolower(trim((string) $input['email']));
rate_limit_or_fail('forgot_password', $email, 5, 3600);
$user = fetch_user_by_identifier($email);
record_auth_attempt('forgot_password', $email, true, null);

if ($user && $user['status'] === 'active') {
    $token = create_auth_token((int) $user['id'], 'password_reset', 3600, null);
    $link = app_url('/reset-password?token=' . urlencode($token));
    send_app_mail($user['email'], 'Reset your JUVA Certify Manager password', "Use this secure link to reset your password. It expires in 1 hour.\n\n{$link}\n\nIf you did not request this, ignore this email.");
    audit_log((int) $user['id'], 'auth.password_reset_requested', 'user', (int) $user['id']);
}

respond(['message' => 'If the email exists and is active, a reset link has been sent.']);
