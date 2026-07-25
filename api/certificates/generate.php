<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_service.php';

require_method('POST');
$user = require_auth();
consume_rate_limit('certificate_issue', (string) $user['id'], 10, 600);
require_csrf();
$input = json_input();
$inspectionId = isset($input['inspection_id']) ? (int) $input['inspection_id'] : 0;
if ($inspectionId <= 0) {
    api_error('Inspection id is required.', 422, ['inspection_id' => 'Required.']);
}

try {
    $certificate = issue_certificate($user, $inspectionId, $input);
} catch (CertificateIssueException $e) {
    api_error($e->getMessage(), $e->httpStatus(), $e->validation());
}

respond($certificate, 201);
