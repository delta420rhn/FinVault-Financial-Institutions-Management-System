<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$symbol   = $data['symbol'] ?? '';
$qty      = $data['quantity'] ?? 0;
$price    = $data['target_price'] ?? 0;
$priority = $data['priority'] ?? 'Standard';
$note     = trim($data['note'] ?? '');
$user_id  = 1; // Trade Manager

if (!$symbol || $qty <= 0 || $price <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

try {
    // Get stock_id
    $stmt = $pdo->prepare("SELECT stock_id FROM stocks WHERE symbol = ?");
    $stmt->execute([$symbol]);
    $stock = $stmt->fetch();

    if (!$stock) {
        echo json_encode(["success" => false, "error" => "Stock not found"]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO purchase_requests 
        (stock_id, quantity, target_price, priority, status, requested_by, sm_note, requested_at)
        VALUES (?, ?, ?, ?, 'Pending', ?, ?, NOW())
    ");

    $stmt->execute([
        $stock['stock_id'],
        $qty,
        $price,
        $priority,
        $user_id,
        $note
    ]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>