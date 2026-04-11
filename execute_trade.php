<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$stock_id      = intval($data['stock_id']);
$executed_by   = intval($data['executed_by']);
$trade_type    = $conn->real_escape_string($data['trade_type']);
$order_mode    = $conn->real_escape_string($data['order_mode']);
$quantity      = intval($data['quantity']);
$price         = floatval($data['price_per_share']);
$justification = $conn->real_escape_string($data['justification']);

// Auto-generate trade ref
$count_result = $conn->query("SELECT COUNT(*) as cnt FROM trade_records");
$count_row    = $count_result->fetch_assoc();
$trade_ref    = 'TRD-' . str_pad($count_row['cnt'] + 1, 4, '0', STR_PAD_LEFT);

$stmt = $conn->prepare(
    "INSERT INTO trade_records
        (trade_ref, stock_id, executed_by, trade_type, order_mode, quantity, price_per_share, justification, trade_status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Executed')"
);
$stmt->bind_param("siissids",
    $trade_ref, $stock_id, $executed_by,
    $trade_type, $order_mode, $quantity,
    $price, $justification
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "trade_ref" => $trade_ref]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>