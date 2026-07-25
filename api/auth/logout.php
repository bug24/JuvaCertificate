<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$user = current_user();
clear_remember_cookie();
if ($user) {
    audit_log((int) $user['id'], 'auth.logout', 'user', (int) $user['id']);
}
clear_auth_session();
respond(['ok' => true]);
