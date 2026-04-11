<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$trade_id   = (int)($data['trade_id']  ?? 0);
$action     = $data['action']          ?? '';   // 'clear' or 'flag'
$auditor_id = 3; // Carol Islam

if (!$trade_id || !in_array($action, ['clear', 'flag'])) {
    http_response_code(400);
    echo json_encode(['error' => 'trade_id and action (clear|flag) are required']);
    exit();
}

$new_auditor_status = $action === 'clear' ? 'Cleared' : 'Flagged';
$new_trade_status   = $action === 'flag'  ? 'Suspended' : null;

$db = getDB();

if ($new_trade_status) {
    $stmt = $db->prepare("
        UPDATE trade_records
        SET auditor_status = ?, trade_status = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE trade_id = ?
    ");
    $stmt->bind_param('ssii', $new_auditor_status, $new_trade_status, $auditor_id, $trade_id);
} else {
    $stmt = $db->prepare("
        UPDATE trade_records
        SET auditor_status = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE trade_id = ?
    ");
    $stmt->bind_param('sii', $new_auditor_status, $auditor_id, $trade_id);
}

$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$db->close();

if ($affected > 0) {
    echo json_encode(['success' => true, 'action' => $action]);
} else {
    echo json_encode(['error' => 'Trade not found']);
}
