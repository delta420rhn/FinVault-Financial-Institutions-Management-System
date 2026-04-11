<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$request_id  = (int)($data['request_id']  ?? 0);
$action      = $data['action']            ?? '';   // 'approve' or 'reject'
$auditor_note = trim($data['auditor_note'] ?? '');
$auditor_id  = 3; // Carol Islam – Auditor user_id

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'request_id and action (approve|reject) are required']);
    exit();
}

$db = getDB();

if ($action === 'approve') {
    // Use the stored procedure
    $stmt = $db->prepare("CALL sp_approve_stock_update(?, ?, ?)");
    $stmt->bind_param('iis', $request_id, $auditor_id, $auditor_note);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'action' => 'approved']);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    $stmt->close();
} else {
    // Reject: mark as rejected, do NOT update actual stock quantity
    $stmt = $db->prepare("
        UPDATE stock_update_requests
        SET  status      = 'Rejected',
             reviewed_by  = ?,
             auditor_note = ?,
             reviewed_at  = NOW()
        WHERE request_id = ? AND status = 'Pending'
    ");
    $stmt->bind_param('isi', $auditor_id, $auditor_note, $request_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        echo json_encode(['success' => true, 'action' => 'rejected']);
    } else {
        echo json_encode(['error' => 'Request not found or already processed']);
    }
}

$db->close();
