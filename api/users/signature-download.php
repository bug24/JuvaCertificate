<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('GET');
$actor = require_auth();
$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) $actor['id'];
if ($id !== (int) $actor['id'] && !has_permission($actor, 'users.manage') && !has_permission($actor, 'users.view_signatures')) api_error('Forbidden.', 403);
$stmt = db()->prepare('SELECT signature_path,signature_original_name,signature_mime_type FROM users WHERE id=? LIMIT 1');
$stmt->execute([$id]); $row = $stmt->fetch();
$path = $row ? resolve_storage_path((string) $row['signature_path']) : null;
if (!$path) api_error('Signature not found.', 404);
stream_download($path, (string) ($row['signature_original_name'] ?: 'signature.png'), (string) ($row['signature_mime_type'] ?: 'image/png'), true);
