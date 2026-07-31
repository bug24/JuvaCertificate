<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/certificate_engine.php';

require_method('POST');
$user = require_any_permission(['inspections.edit', 'inspections.review', 'certificates.issue']);
require_csrf();
$input = json_input();
$errors = validate_required($input, ['id', 'status']);
if ($errors) {
    api_error('Please correct the highlighted fields.', 422, $errors);
}

$id = (int) $input['id'];
$status = (string) $input['status'];
$allowed = ['draft', 'submitted', 'correction', 'approved', 'revoked', 'expired'];
if (!in_array($status, $allowed, true)) {
    api_error('Invalid inspection status.', 422, ['status' => 'Invalid status.']);
}

$inspection = fetch_inspection_for_user($id, $user);
if (!$inspection) {
    api_error('Inspection not found.', 404);
}

$current = (string) $inspection['status'];
$transitions = [
    'draft' => ['submitted'],
    'submitted' => ['correction', 'approved'],
    'correction' => ['submitted'],
    'approved' => ['correction', 'revoked', 'expired'],
    'issued' => ['revoked', 'expired'],
    'revoked' => [],
    'expired' => [],
];
if (!in_array($status, $transitions[$current] ?? [], true)) {
    api_error('This status change is not allowed from the current inspection state.', 422, ['status' => 'Invalid workflow transition.']);
}

$isPrivilegedSubmitter = $status === 'submitted' && privileged_role((string) ($user['role']['slug'] ?? ''));

if ($status === 'submitted' && !has_permission($user, 'inspections.edit')) {
    api_error('You do not have permission to submit this inspection.', 403);
}
if (in_array($status, ['correction', 'approved'], true) && !has_permission($user, 'inspections.review')) {
    api_error('You do not have permission to review this inspection.', 403);
}
if (in_array($status, ['revoked', 'expired'], true) && !has_permission($user, 'certificates.issue')) {
    api_error('You do not have permission to apply this status.', 403);
}
if (in_array($status, ['correction', 'approved'], true) && (int) $inspection['inspector_id'] === (int) $user['id'] && !privileged_role((string) ($user['role']['slug'] ?? ''))) {
    api_error('Inspectors cannot approve or review their own inspections.', 403);
}

$commentText = isset($input['comment']) ? normalize_text_input((string) $input['comment'], true) : '';
if ($commentText !== '' && (contains_markup($commentText) || text_length($commentText) > 3000)) {
    api_error('Please correct the highlighted fields.', 422, ['comment' => 'Comments must be plain text up to 3000 characters.']);
}

$submittedAt = $status === 'submitted' && empty($inspection['submitted_at']) ? now_sql() : $inspection['submitted_at'];
$approvedAt = $status === 'approved' ? now_sql() : $inspection['approved_at'];
$nextStatus = $status;
$decision = null;
$commentKind = 'status';
$activityMessage = 'Inspection status updated.';

if ($isPrivilegedSubmitter) {
    $nextStatus = 'approved';
    $approvedAt = now_sql();
    $decision = 'approved';
    $commentKind = 'review';
    if ($commentText === '') {
        $commentText = 'Privileged admin submitter approved this inspection and triggered automatic certificate issuance.';
    }
}

$pdo = db();
$pdo->beginTransaction();
try {
    $update = $pdo->prepare('UPDATE inspections SET status = ?, submitted_at = ?, approved_at = ?, updated_at = ? WHERE id = ? AND status = ?');
    $update->execute([$nextStatus, $submittedAt, $approvedAt, now_sql(), $id, $current]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('concurrent_status_change');
    }

    if ($decision !== null || in_array($status, ['correction', 'approved'], true)) {
        $approvalDecision = $decision ?? ($status === 'approved' ? 'approved' : 'correction');
        $approval = $pdo->prepare('INSERT INTO approvals (inspection_id, reviewer_id, decision, comment, created_at) VALUES (?, ?, ?, ?, ?)');
        $approval->execute([$id, (int) $user['id'], $approvalDecision, $commentText !== '' ? $commentText : null, now_sql()]);
    }

    if ($commentText !== '') {
        if (!$isPrivilegedSubmitter) {
            $commentKind = $status === 'correction' ? 'correction' : ($status === 'approved' ? 'review' : 'status');
        }
        $comment = $pdo->prepare('INSERT INTO inspection_comments (inspection_id, user_id, comment_type, comment_text, created_at) VALUES (?, ?, ?, ?, ?)');
        $comment->execute([$id, (int) $user['id'], $commentKind, $commentText, now_sql()]);
    }

    audit_log((int) $user['id'], $isPrivilegedSubmitter ? 'inspections.corrected_resubmitted_and_approved' : 'inspections.status_changed', 'inspection', $id, ['from' => $current, 'requested' => $status, 'to' => $nextStatus]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getMessage() === 'concurrent_status_change') {
        api_error('This inspection was already changed by another request. Reload it before retrying.', 409);
    }
    api_error('Unable to update inspection status.', 500);
}

$response = [
    'message' => $activityMessage,
    'inspection_status' => $nextStatus,
    'auto_issued' => false,
    'certificate' => null,
    'partial_error' => null,
];

if ($isPrivilegedSubmitter) {
    try {
        $certificate = issue_certificate($user, $id, ['certificate_number' => (string) $inspection['reference'], 'create_revision' => $current === 'correction'], ['allow_self_issue' => true]);
        $response['message'] = 'Inspection approved and certificate issued automatically.';
        $response['inspection_status'] = 'issued';
        $response['auto_issued'] = true;
        $response['certificate'] = $certificate;
        audit_log((int) $user['id'], 'inspections.auto_issued', 'inspection', $id, ['from' => $current, 'to' => 'issued']);
    } catch (CertificateIssueException $e) {
        $repair = db();
        $repair->beginTransaction();
        try {
            $now = now_sql();
            $repair->prepare('UPDATE inspections SET status=?, approved_at=NULL, updated_at=? WHERE id=?')->execute(['correction', $now, $id]);
            $repair->prepare('INSERT INTO inspection_comments (inspection_id,user_id,comment_type,comment_text,created_at) VALUES (?,?,?,?,?)')->execute([$id, (int) $user['id'], 'correction', $e->getMessage(), $now]);
            audit_log((int) $user['id'], 'inspections.auto_issue_failed', 'inspection', $id, ['from' => $current, 'to' => 'correction', 'error' => $e->getMessage()]);
            $repair->commit();
        } catch (Throwable $repairError) {
            if ($repair->inTransaction()) { $repair->rollBack(); }
        }
        api_error('Certificate was not issued. ' . $e->getMessage() . ' The inspection was preserved for correction.', $e->httpStatus(), $e->validation());
    }
}

respond($response);
