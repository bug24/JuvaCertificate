<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$actor = require_permission('inspections.create');
$kind = isset($_GET['kind']) ? (string) $_GET['kind'] : 'inspector';
$roles = $kind === 'authenticator'
    ? ['super-admin', 'operations-admin', 'reviewer']
    : ['super-admin', 'operations-admin', 'inspector'];
$placeholders = implode(',', array_fill(0, count($roles), '?'));
$stmt = db()->prepare("SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.permissions_json FROM users u JOIN roles r ON r.id=u.role_id WHERE u.status='active' AND r.slug IN ($placeholders) ORDER BY u.name");
$stmt->execute($roles);
$items = [];
foreach ($stmt->fetchAll() as $row) {
    $items[] = public_user($row);
}
respond(['users' => $items, 'kind' => $kind]);
