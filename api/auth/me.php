<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$user = current_user();
if (!$user) {
    api_error('Authentication required.', 401);
}
respond(['user' => $user, 'csrf_token' => csrf_token()]);
