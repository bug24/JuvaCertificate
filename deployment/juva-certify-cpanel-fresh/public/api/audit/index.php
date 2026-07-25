<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
$user = require_auth();
if (!has_permission($user, '*') && !has_permission($user, 'audit.view') && $user['role']['slug'] !== 'operations-admin') { api_error('You do not have permission to view audit logs.', 403); }
$stmt = db()->query('SELECT a.id, COALESCE(u.name, "System") AS user_name, a.action, COALESCE(a.entity_type, "system") AS module, a.entity_id, a.ip_address, a.ip_hash, a.metadata_json, a.created_at FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 300');
respond(['events' => $stmt->fetchAll()]);
