<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$user = require_auth();
if (!has_permission($user, 'users.manage') && !has_permission($user, 'roles.manage')) {
    api_error('You do not have permission to view roles.', 403);
}
$stmt = db()->query('SELECT id, name, slug, permissions_json, created_at FROM roles ORDER BY id');
$roles = [];
foreach ($stmt->fetchAll() as $row) {
    $roles[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'slug' => $row['slug'],
        'permissions' => decode_permissions($row['permissions_json']),
        'created_at' => $row['created_at'],
    ];
}
respond(['roles' => $roles]);
