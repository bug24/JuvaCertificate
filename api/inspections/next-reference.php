<?php
require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
require_permission('inspections.create');

$clientId = isset($_GET['client_id']) ? (int) $_GET['client_id'] : 0;
$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
if ($clientId <= 0 || $categoryId <= 0) {
    api_error('Client and category are required.', 422, [
        'client_id' => $clientId <= 0 ? 'Select a client.' : null,
        'category_id' => $categoryId <= 0 ? 'Select a category.' : null,
    ]);
}

$clientStmt = db()->prepare('SELECT id, short_code FROM clients WHERE id = ? LIMIT 1');
$clientStmt->execute([$clientId]);
$client = $clientStmt->fetch();
if (!$client) {
    api_error('Selected client was not found.', 422, ['client_id' => 'Select a valid client.']);
}
if (empty($client['short_code'])) {
    api_error('Selected client is missing a short code.', 422, ['client_id' => 'Update the client short code first.']);
}

$categoryStmt = db()->prepare('SELECT id, short_code FROM certification_categories WHERE id = ? AND status = ? LIMIT 1');
$categoryStmt->execute([$categoryId, 'active']);
$category = $categoryStmt->fetch();
if (!$category) {
    api_error('Selected category was not found.', 422, ['category_id' => 'Select a valid active category.']);
}
if (empty($category['short_code'])) {
    api_error('Selected category is missing a short code.', 422, ['category_id' => 'Update the category short code first.']);
}

$preview = preview_scoped_reference(db(), $clientId, $categoryId, (string) $client['short_code'], (string) $category['short_code']);
respond(['preview' => $preview]);
