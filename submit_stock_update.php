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

$stock_id    = (int)($data['stock_id']    ?? 0);
$new_qty     = (int)($data['new_quantity'] ?? -1);
$reason      = trim($data['reason']       ?? '');
$requested_by = 1; // Stock Manager user_id (Alice Rahman)

if (!$stock_id || $new_qty < 0 || !$reason) {
    http_response_code(400);
    echo json_encode(['error' => 'stock_id, new_quantity (≥0), and reason are required']);
    exit();
}

$db = getDB();

// Get current quantity
$stmt = $db->prepare("SELECT shares_available FROM stocks WHERE stock_id = ?");
$stmt->bind_param('i', $stock_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['error' => 'Stock not found']);
    $db->close();
    exit();
}

$old_qty = (int)$row['shares_available'];

// Insert update request
$stmt = $db->prepare("
    INSERT INTO stock_update_requests
        (stock_id, requested_by, old_quantity, new_quantity, reason, status)
    VALUES (?, ?, ?, ?, ?, 'Pending')
");
$stmt->bind_param('iiiss', $stock_id, $requested_by, $old_qty, $new_qty, $reason);
$stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();
$db->close();

echo json_encode(['success' => true, 'request_id' => $new_id]);
