<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
require_permission('inspections.create');
$reference = isset($_GET['reference']) ? strtoupper(normalize_text_input((string) $_GET['reference'])) : '';
if ($reference === '') {
    api_error('Reference is required.', 422, ['reference' => 'Required.']);
}
if (!preg_match('/^[A-Z0-9][A-Z0-9._\/-]{5,149}$/', $reference)) {
    api_error('Reference format is invalid.', 422, ['reference' => 'Invalid format.']);
}
$stmt = db()->prepare('SELECT id, reference, status FROM inspections WHERE reference = ? LIMIT 1');
$stmt->execute([$reference]);
$row = $stmt->fetch();
respond(['available' => !$row, 'inspection' => $row ?: null]);
