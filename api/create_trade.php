<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$symbol = $data['symbol'];
$qty = $data['qty'];
$price = $data['price'];
$type = $data['type'];
$note = $data['note'];

$total = $qty * $price;

try {
    $pdo->beginTransaction();

    // 1. Get stock ID + current quantity
    $stmt = $pdo->prepare("SELECT stock_id, quantity FROM stocks WHERE symbol=? FOR UPDATE");
    $stmt->execute([$symbol]);
    $stock = $stmt->fetch();

    if (!$stock) {
        throw new Exception("Stock not found");
    }

    $stock_id = $stock['stock_id'];
    $current_qty = $stock['quantity'];

    // 2. CHECK STOCK AVAILABILITY (IMPORTANT)
    if ($type === 'BUY' && $qty > $current_qty) {
        throw new Exception("Not enough stock available");
    }

    // 3. INSERT TRADE
    $stmt = $pdo->prepare("
        INSERT INTO trade_records
        (trade_ref, stock_id, trade_type, quantity, price_per_share, total_value, executed_by, trade_status)
        VALUES (
          CONCAT('TRD-', FLOOR(RAND()*10000)),
          ?, ?, ?, ?, ?, 2, 'Executed'
        )
    ");
    $stmt->execute([$stock_id, $type, $qty, $price, $total]);

    // 4. UPDATE STOCK (THIS IS THE KEY FIX)
    if ($type === 'BUY') {
        $new_qty = $current_qty - $qty;
    } else {
        $new_qty = $current_qty + $qty;
    }

    $stmt = $pdo->prepare("UPDATE stocks SET quantity=? WHERE stock_id=?");
    $stmt->execute([$new_qty, $stock_id]);

    $pdo->commit();

    echo json_encode(["success"=>true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error"=>$e->getMessage()]);
}