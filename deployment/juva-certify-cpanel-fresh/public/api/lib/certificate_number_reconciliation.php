<?php

declare(strict_types=1);

const CERTIFICATE_NUMBER_RECONCILIATION_CONFIRMATION = 'RECONCILE-CERTIFICATE-NUMBERS';

function certificate_number_reconciliation_parse(string $number): ?array
{
    $number = strtoupper(trim($number));
    if (!preg_match('/^JUVA\/([^\/]+)\/([^\/]+)\/([0-9]+)$/', $number, $matches)) {
        return null;
    }
    return [
        'number' => $number,
        'client_segment' => $matches[1],
        'category_segment' => $matches[2],
        'sequence_segment' => $matches[3],
        'sequence_number' => (int) $matches[3],
    ];
}

function certificate_number_reconciliation_valid_client_code(string $shortCode): bool
{
    return preg_match('/^(?=.*[A-Z])[A-Z0-9]{2,12}$/', strtoupper(trim($shortCode))) === 1;
}

function certificate_number_reconciliation_valid_category_code(string $shortCode): bool
{
    return preg_match('/^[A-Z0-9]{2,12}$/', strtoupper(trim($shortCode))) === 1;
}

function certificate_number_reconciliation_decision(array $row): array
{
    $inspectionId = (int) ($row['inspection_id'] ?? 0);
    $certificateId = isset($row['certificate_id']) && $row['certificate_id'] !== null ? (int) $row['certificate_id'] : null;
    $clientCode = strtoupper(trim((string) ($row['client_short_code'] ?? '')));
    $categoryCode = strtoupper(trim((string) ($row['category_short_code'] ?? '')));
    $sequenceNumber = (int) ($row['sequence_number'] ?? 0);
    $inspectionNumber = strtoupper(trim((string) ($row['inspection_reference'] ?? '')));
    $certificateNumber = $certificateId !== null ? strtoupper(trim((string) ($row['certificate_number'] ?? ''))) : null;
    $base = [
        'inspection_id' => $inspectionId,
        'certificate_id' => $certificateId,
        'client_id' => (int) ($row['client_id'] ?? 0),
        'client_name' => (string) ($row['client_name'] ?? ''),
        'client_short_code' => $clientCode,
        'category_id' => (int) ($row['category_id'] ?? 0),
        'category_short_code' => $categoryCode,
        'sequence_number' => $sequenceNumber,
        'revision' => isset($row['revision']) ? (int) $row['revision'] : null,
        'revision_count' => (int) ($row['revision_count'] ?? 0),
        'revision_paths_with_number' => (int) ($row['revision_paths_with_number'] ?? 0),
        'verification_token' => $row['verification_token'] ?? null,
        'pdf_path' => $row['pdf_path'] ?? null,
        'barcode_path' => $row['barcode_path'] ?? null,
        'pdf_hash' => $row['pdf_hash'] ?? null,
        'old_inspection_reference' => $inspectionNumber,
        'old_certificate_number' => $certificateNumber,
    ];
    $block = static function (string $reason, string $message) use ($base): array {
        return $base + ['state' => 'blocked', 'reason' => $reason, 'message' => $message];
    };

    if (!certificate_number_reconciliation_valid_client_code($clientCode)) {
        return $block('invalid_client_short_code', 'Configure a 2-12 character client short code containing at least one letter before reconciliation.');
    }
    if (!certificate_number_reconciliation_valid_category_code($categoryCode)) {
        return $block('invalid_category_short_code', 'The relational category short code is missing or invalid.');
    }
    if ($sequenceNumber < 1) {
        return $block('invalid_sequence_number', 'The relational inspection sequence is missing or invalid.');
    }

    $inspectionParts = certificate_number_reconciliation_parse($inspectionNumber);
    if ($inspectionParts === null) {
        return $block('malformed_inspection_reference', 'The inspection reference does not match JUVA/{CLIENT}/{CATEGORY}/{SEQUENCE}.');
    }
    $certificateParts = null;
    if ($certificateId !== null) {
        $certificateParts = certificate_number_reconciliation_parse((string) $certificateNumber);
        if ($certificateParts === null) {
            return $block('malformed_certificate_number', 'The certificate number does not match JUVA/{CLIENT}/{CATEGORY}/{SEQUENCE}.');
        }
        if ($certificateNumber !== $inspectionNumber) {
            return $block('authoritative_number_mismatch', 'Inspection reference and certificate number differ; manual review is required.');
        }
    }

    if ($inspectionParts['category_segment'] !== $categoryCode || ($certificateParts !== null && $certificateParts['category_segment'] !== $categoryCode)) {
        return $block('category_mismatch', 'The current category segment differs from the relational category short code.');
    }
    if ($inspectionParts['sequence_number'] !== $sequenceNumber || ($certificateParts !== null && $certificateParts['sequence_number'] !== $sequenceNumber)) {
        return $block('sequence_mismatch', 'The current sequence differs from the relational inspection sequence.');
    }

    $target = 'JUVA/' . $clientCode . '/' . $inspectionParts['category_segment'] . '/' . $inspectionParts['sequence_segment'];
    if ($inspectionParts['client_segment'] === $clientCode) {
        return $base + [
            'state' => 'canonical',
            'target_inspection_reference' => $target,
            'target_certificate_number' => $certificateId !== null ? $target : null,
        ];
    }
    if (!ctype_digit($inspectionParts['client_segment'])) {
        return $block('noncanonical_client_segment', 'The current client segment is nonnumeric but differs from clients.short_code; manual review is required.');
    }

    return $base + [
        'state' => 'action',
        'legacy_client_segment' => $inspectionParts['client_segment'],
        'target_inspection_reference' => $target,
        'target_certificate_number' => $certificateId !== null ? $target : null,
        'preserves' => [
            'category_segment' => true,
            'sequence_segment' => true,
            'inspection_id' => true,
            'certificate_id' => true,
            'revision' => true,
            'verification_token' => true,
            'pdf_path' => true,
            'barcode_path' => true,
            'pdf_hash' => true,
        ],
    ];
}

function certificate_number_reconciliation_rows(PDO $pdo): array
{
    $sql = <<<'SQL'
SELECT
    i.id AS inspection_id,
    i.reference AS inspection_reference,
    i.sequence_number,
    i.client_id,
    cl.name AS client_name,
    cl.short_code AS client_short_code,
    i.category_id,
    cat.short_code AS category_short_code,
    cert.id AS certificate_id,
    cert.certificate_number,
    cert.revision,
    cert.verification_token,
    cert.pdf_path,
    cert.barcode_path,
    cert.pdf_hash,
    (SELECT COUNT(*) FROM certificate_revisions cr WHERE cr.certificate_id = cert.id) AS revision_count,
    (SELECT COUNT(*) FROM certificate_revisions cr WHERE cr.certificate_id = cert.id AND cr.pdf_path LIKE CONCAT('%', REPLACE(cert.certificate_number, '/', '_'), '%')) AS revision_paths_with_number
FROM inspections i
JOIN clients cl ON cl.id = i.client_id
JOIN certification_categories cat ON cat.id = i.category_id
LEFT JOIN certificates cert ON cert.inspection_id = i.id
ORDER BY i.id
SQL;
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function certificate_number_reconciliation_audit(PDO $pdo): array
{
    $rows = certificate_number_reconciliation_rows($pdo);
    $actions = [];
    $blockers = [];
    $canonical = 0;
    $clientAudit = [];
    $inspectionOwners = [];
    $certificateOwners = [];
    $numericSegmentRows = 0;
    $numericSegmentRevisions = 0;

    $allClients = $pdo->query('SELECT id, name, short_code FROM clients ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($allClients as $client) {
        $shortCode = strtoupper(trim((string) $client['short_code']));
        $clientAudit[(int) $client['id']] = [
            'client_id' => (int) $client['id'],
            'client_name' => (string) $client['name'],
            'short_code' => $shortCode,
            'numeric_only' => $shortCode !== '' && ctype_digit($shortCode),
            'valid_for_certificate_numbers' => certificate_number_reconciliation_valid_client_code($shortCode),
        ];
    }

    foreach ($rows as $row) {
        $inspectionOwners[strtoupper(trim((string) $row['inspection_reference']))] = (int) $row['inspection_id'];
        if (!empty($row['certificate_id'])) {
            $certificateOwners[strtoupper(trim((string) $row['certificate_number']))] = (int) $row['certificate_id'];
        }
        $parts = certificate_number_reconciliation_parse((string) $row['inspection_reference']);
        if ($parts !== null && ctype_digit($parts['client_segment'])) {
            $numericSegmentRows++;
            $numericSegmentRevisions += (int) ($row['revision_count'] ?? 0);
        }
    }

    foreach ($rows as $row) {
        $decision = certificate_number_reconciliation_decision($row);
        if ($decision['state'] === 'action') {
            $actions[] = $decision;
        } elseif ($decision['state'] === 'blocked') {
            $blockers[] = $decision;
        } else {
            $canonical++;
        }
    }

    $targetInspectionOwners = [];
    $targetCertificateOwners = [];
    foreach ($actions as $index => $action) {
        $target = strtoupper((string) $action['target_inspection_reference']);
        $owner = $inspectionOwners[$target] ?? null;
        $plannedOwner = $targetInspectionOwners[$target] ?? null;
        if (($owner !== null && $owner !== $action['inspection_id']) || ($plannedOwner !== null && $plannedOwner !== $action['inspection_id'])) {
            $blockers[] = $action + ['state' => 'blocked', 'reason' => 'duplicate_inspection_target', 'message' => 'The target inspection reference already belongs to another inspection.'];
            unset($actions[$index]);
            continue;
        }
        $targetInspectionOwners[$target] = $action['inspection_id'];
        if ($action['certificate_id'] !== null) {
            $certificateTarget = strtoupper((string) $action['target_certificate_number']);
            $certificateOwner = $certificateOwners[$certificateTarget] ?? null;
            $plannedCertificateOwner = $targetCertificateOwners[$certificateTarget] ?? null;
            if (($certificateOwner !== null && $certificateOwner !== $action['certificate_id']) || ($plannedCertificateOwner !== null && $plannedCertificateOwner !== $action['certificate_id'])) {
                $blockers[] = $action + ['state' => 'blocked', 'reason' => 'duplicate_certificate_target', 'message' => 'The target certificate number already belongs to another certificate.'];
                unset($actions[$index]);
                continue;
            }
            $targetCertificateOwners[$certificateTarget] = $action['certificate_id'];
        }
    }
    $actions = array_values($actions);

    $verificationCount = 0;
    $auditReferenceCount = 0;
    if ($actions) {
        $verificationStmt = $pdo->prepare('SELECT COUNT(*) FROM verification_logs WHERE UPPER(searched_reference) IN (?, ?)');
        $auditStmt = $pdo->prepare('SELECT COUNT(*) FROM audit_logs WHERE metadata_json LIKE ? OR metadata_json LIKE ?');
        foreach ($actions as &$action) {
            $oldInspection = (string) $action['old_inspection_reference'];
            $oldCertificate = (string) ($action['old_certificate_number'] ?? $oldInspection);
            $verificationStmt->execute([strtoupper($oldInspection), strtoupper($oldCertificate)]);
            $action['historical_verification_log_count'] = (int) $verificationStmt->fetchColumn();
            $verificationCount += $action['historical_verification_log_count'];
            $auditStmt->execute(['%' . $oldInspection . '%', '%' . $oldCertificate . '%']);
            $action['historical_audit_log_reference_count'] = (int) $auditStmt->fetchColumn();
            $auditReferenceCount += $action['historical_audit_log_reference_count'];
            $archiveSlug = preg_replace('/[^A-Z0-9_-]/i', '_', $oldCertificate) ?? '';
            $action['archive_dependency'] = [
                'pdf_path_contains_old_number' => $archiveSlug !== '' && stripos((string) ($action['pdf_path'] ?? ''), $archiveSlug) !== false,
                'barcode_path_contains_old_number' => $archiveSlug !== '' && stripos((string) ($action['barcode_path'] ?? ''), $archiveSlug) !== false,
                'revision_paths_with_old_number' => (int) ($action['revision_paths_with_number'] ?? 0),
                'artifacts_preserved' => !empty($action['pdf_path']) || !empty($action['barcode_path']) || $action['revision_count'] > 0,
            ];
        }
        unset($action);
    }

    $clients = array_values($clientAudit);
    return [
        'mode' => 'dry-run',
        'canonical_rule' => 'JUVA/{CLIENT_SHORT_CODE}/{CATEGORY_SHORT_CODE}/{SEQUENCE}',
        'ready_to_apply' => count($blockers) === 0,
        'counts' => [
            'inspections_scanned' => count($rows),
            'canonical_rows' => $canonical,
            'actions' => count($actions),
            'blockers' => count($blockers),
            'clients_scanned' => count($clients),
            'numeric_only_clients' => count(array_filter($clients, static function (array $client): bool { return (bool) $client['numeric_only']; })),
            'invalid_client_short_codes' => count(array_filter($clients, static function (array $client): bool { return !$client['valid_for_certificate_numbers']; })),
            'numeric_client_segment_rows' => $numericSegmentRows,
            'revisions_linked_to_numeric_segment_rows' => $numericSegmentRevisions,
            'certificate_revisions_preserved' => array_sum(array_column($actions, 'revision_count')),
            'historical_verification_logs_preserved' => $verificationCount,
            'historical_audit_references_preserved' => $auditReferenceCount,
        ],
        'clients' => $clients,
        'actions' => $actions,
        'blockers' => $blockers,
        'invariants' => [
            'updates_only' => ['inspections.reference', 'certificates.certificate_number'],
            'immutable_identity' => ['inspections.id', 'certificates.id', 'certificate_revisions.id', 'certificates.verification_token'],
            'unchanged_artifacts' => ['certificates.pdf_path', 'certificates.barcode_path', 'certificates.pdf_hash', 'certificate_revisions.pdf_path', 'certificate_revisions.barcode_path', 'certificate_revisions.pdf_hash'],
            'historical_logs_are_not_rewritten' => true,
        ],
    ];
}

function certificate_number_reconciliation_apply(PDO $pdo, int $actorUserId): array
{
    if ($actorUserId < 1) {
        throw new InvalidArgumentException('A valid actor user ID is required.');
    }
    $pdo->beginTransaction();
    try {
        $report = certificate_number_reconciliation_audit($pdo);
        if (!$report['ready_to_apply']) {
            throw new RuntimeException('Reconciliation is blocked; resolve every reported blocker and run dry-run again.');
        }
        $inspectionUpdate = $pdo->prepare('UPDATE inspections SET reference = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND reference = ?');
        $certificateUpdate = $pdo->prepare('UPDATE certificates SET certificate_number = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND certificate_number = ?');
        $auditInsert = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, NULL, NULL, CURRENT_TIMESTAMP)');
        foreach ($report['actions'] as $action) {
            $inspectionUpdate->execute([$action['target_inspection_reference'], $action['inspection_id'], $action['old_inspection_reference']]);
            if ($inspectionUpdate->rowCount() !== 1) {
                throw new RuntimeException('Inspection changed during reconciliation; transaction rolled back.');
            }
            if ($action['certificate_id'] !== null) {
                $certificateUpdate->execute([$action['target_certificate_number'], $action['certificate_id'], $action['old_certificate_number']]);
                if ($certificateUpdate->rowCount() !== 1) {
                    throw new RuntimeException('Certificate changed during reconciliation; transaction rolled back.');
                }
            }
            $metadata = [
                'inspection_id' => $action['inspection_id'],
                'certificate_id' => $action['certificate_id'],
                'old_inspection_reference' => $action['old_inspection_reference'],
                'new_inspection_reference' => $action['target_inspection_reference'],
                'old_certificate_number' => $action['old_certificate_number'],
                'new_certificate_number' => $action['target_certificate_number'],
                'client_id' => $action['client_id'],
                'client_short_code' => $action['client_short_code'],
                'category_id' => $action['category_id'],
                'category_short_code' => $action['category_short_code'],
                'sequence_number' => $action['sequence_number'],
                'revision_preserved' => true,
                'verification_token_preserved' => true,
                'archive_paths_preserved' => true,
            ];
            $auditInsert->execute([
                $actorUserId,
                'certificates.number_reconciled',
                $action['certificate_id'] !== null ? 'certificate' : 'inspection',
                $action['certificate_id'] ?? $action['inspection_id'],
                json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ]);
        }
        $applied = count($report['actions']);
        $postApply = certificate_number_reconciliation_audit($pdo);
        if ($postApply['counts']['actions'] !== 0 || $postApply['counts']['blockers'] !== 0) {
            throw new RuntimeException('Post-apply idempotency verification failed; transaction rolled back.');
        }
        $pdo->commit();
        return [
            'mode' => 'apply',
            'applied' => $applied,
            'idempotent' => true,
            'post_apply' => $postApply,
        ];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
