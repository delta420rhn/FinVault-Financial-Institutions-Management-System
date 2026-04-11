<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$symbol = trim($data['symbol'] ?? '');
$qty    = $data['qty']   ?? null;
$price  = $data['price'] ?? null;
$note   = trim($data['note'] ?? '');

if (!$symbol || !$qty || !$price) {
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit;
}

// STEP 1: Convert symbol → stock_id
$stmt = $pdo->prepare("SELECT stock_id FROM stocks WHERE symbol = ?");
$stmt->execute([$symbol]);
$stock = $stmt->fetch();

if (!$stock) {
    echo json_encode(["success" => false, "error" => "Invalid stock symbol: $symbol"]);
    exit;
}

$stock_id = $stock['stock_id'];

// STEP 2: Insert
$stmt = $pdo->prepare("
    INSERT INTO purchase_requests
        (stock_id, requested_by, quantity, target_price, justification, status)
    VALUES (?, ?, ?, ?, ?, 'Pending')
");

$stmt->execute([
    $stock_id,
    2,      // Trade Manager user_id (Bob = 2)
    $qty,
    $price,
    $note
]);

echo json_encode(["success" => true]);
