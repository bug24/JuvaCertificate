<?php

function certificate_archive_iso_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function certificate_archive_filters(array $source): array
{
    $rawQueryValue = $source['q'] ?? '';
    $rawQuery = is_scalar($rawQueryValue) ? (preg_replace('/[\x00-\x1F\x7F]/', '', trim((string) $rawQueryValue)) ?? '') : '';
    $filters = [
        'q' => function_exists('mb_substr') ? mb_substr($rawQuery, 0, 200) : substr($rawQuery, 0, 200),
        'client_id' => max(0, (int) ($source['client_id'] ?? 0)),
        'category_id' => max(0, (int) ($source['category_id'] ?? 0)),
        'status' => strtolower(trim((string) ($source['status'] ?? ''))),
        'inspection_from' => trim((string) ($source['inspection_from'] ?? '')),
        'inspection_to' => trim((string) ($source['inspection_to'] ?? '')),
    ];
    $errors = [];
    foreach (['client_id', 'category_id'] as $key) {
        $rawValue = $source[$key] ?? '';
        $rawId = is_scalar($rawValue) ? trim((string) $rawValue) : '__invalid__';
        if ($rawId !== '' && !preg_match('/^[1-9][0-9]*$/', $rawId)) $errors[$key] = 'Use a valid positive ID.';
    }
    if ($filters['status'] !== '' && !in_array($filters['status'], ['valid', 'expired', 'revoked', 'superseded'], true)) {
        $errors['status'] = 'Invalid certificate status.';
    }
    foreach (['inspection_from', 'inspection_to'] as $key) {
        if ($filters[$key] !== '' && !certificate_archive_iso_date($filters[$key])) {
            $errors[$key] = 'Use a valid date in YYYY-MM-DD format.';
        }
    }
    if (!$errors && $filters['inspection_from'] !== '' && $filters['inspection_to'] !== '' && $filters['inspection_from'] > $filters['inspection_to']) {
        $errors['inspection_to'] = 'To date must be on or after From date.';
    }
    return [$filters, $errors];
}

function certificate_archive_scope(array $user, string $inspectionAlias = 'i'): array
{
    if (has_permission($user, 'certificates.view')) return ['', []];
    if (has_permission($user, 'certificates.own.view') && !empty($user['client_id'])) {
        return [$inspectionAlias . '.client_id = ?', [(int) $user['client_id']]];
    }
    return ['1 = 0', []];
}

function certificate_archive_where(array $filters, array $user): array
{
    $where = [];
    $params = [];
    [$scope, $scopeParams] = certificate_archive_scope($user);
    if ($scope !== '') { $where[] = $scope; array_push($params, ...$scopeParams); }
    if ($filters['q'] !== '') {
        $like = '%' . $filters['q'] . '%';
        $where[] = '(cert.certificate_number LIKE ? OR i.reference LIKE ? OR c.name LIKE ? OR e.name LIKE ? OR e.asset_code LIKE ? OR e.serial_number LIKE ? OR cat.name LIKE ? OR u.name LIKE ? OR EXISTS (SELECT 1 FROM inspection_items search_item WHERE search_item.inspection_id = i.id AND search_item.column_key IN (\'identification_number\',\'serial_number\',\'shackle_id\',\'equipment_identifier\') AND search_item.value_text LIKE ?))';
        for ($n = 0; $n < 9; $n++) $params[] = $like;
    }
    foreach (['client_id' => 'i.client_id', 'category_id' => 'i.category_id'] as $key => $column) {
        if ($filters[$key] > 0) { $where[] = $column . ' = ?'; $params[] = $filters[$key]; }
    }
    if ($filters['status'] === 'expired') $where[] = "(cert.status='expired' OR (cert.status='valid' AND cert.expires_at < CURDATE()))";
    elseif ($filters['status'] === 'valid') $where[] = "cert.status='valid' AND (cert.expires_at IS NULL OR cert.expires_at >= CURDATE())";
    elseif ($filters['status'] !== '') { $where[] = 'cert.status = ?'; $params[] = $filters['status']; }
    if ($filters['inspection_from'] !== '') { $where[] = 'i.inspection_date >= ?'; $params[] = $filters['inspection_from']; }
    if ($filters['inspection_to'] !== '') { $where[] = 'i.inspection_date <= ?'; $params[] = $filters['inspection_to']; }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function certificate_batch_ids($raw, int $maximum = 250): array
{
    if (!is_array($raw) || count($raw) < 1) throw new InvalidArgumentException('Select at least one certificate.');
    if (count($raw) > $maximum) throw new InvalidArgumentException('A batch may contain at most ' . $maximum . ' certificates.');
    $ids = [];
    foreach ($raw as $value) {
        if (!(is_int($value) || (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value)))) throw new InvalidArgumentException('Certificate IDs must be positive integers.');
        $id = (int) $value;
        if ($id < 1) throw new InvalidArgumentException('Certificate IDs must be positive integers.');
        if (isset($ids[$id])) throw new InvalidArgumentException('Duplicate certificate IDs are not allowed.');
        $ids[$id] = true;
    }
    return array_keys($ids);
}

function certificate_archive_date_matches(string $date, string $from = '', string $to = ''): bool
{
    return ($from === '' || $date >= $from) && ($to === '' || $date <= $to);
}