<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$request_id = (int)($data['request_id'] ?? 0);
$action     = $data['action']     ?? '';   // 'confirm' or 'reject'
$sm_note    = $data['sm_note']    ?? '';
$confirmed_by = 1;  // Stock Manager user_id (Alice Rahman)

if (!$request_id || !in_array($action, ['confirm', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

$db = getDB();

// Fetch current stock price for confirmation
$stmt = $db->prepare("
    SELECT s.price_per_share, s.shares_available
    FROM purchase_requests pr
    JOIN stocks s ON pr.stock_id = s.stock_id
    WHERE pr.request_id = ?
");
$stmt->bind_param('i', $request_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    echo json_encode(['error' => 'Request not found']);
    $db->close();
    exit();
}

if ($action === 'confirm') {
    $confirmed_price = $res['price_per_share'];
    $stmt = $db->prepare("
        UPDATE purchase_requests
        SET status = 'Confirmed',
            confirmed_by = ?,
            confirmed_price = ?,
            sm_note = ?,
            confirmed_at = NOW()
        WHERE request_id = ? AND status = 'Pending'
    ");
    $stmt->bind_param('idsi', $confirmed_by, $confirmed_price, $sm_note, $request_id);
} else {
    $stmt = $db->prepare("
        UPDATE purchase_requests
        SET status = 'Rejected',
            confirmed_by = ?,
            sm_note = ?,
            confirmed_at = NOW()
        WHERE request_id = ? AND status = 'Pending'
    ");
    $stmt->bind_param('isi', $confirmed_by, $sm_note, $request_id);
}

$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$db->close();

if ($affected > 0) {
    echo json_encode(['success' => true, 'action' => $action]);
} else {
    echo json_encode(['error' => 'Update failed or request already processed']);
}
