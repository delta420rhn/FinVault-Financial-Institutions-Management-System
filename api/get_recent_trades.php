<?php
require 'db.php';

try {
    $stmt = $pdo->query("
        SELECT t.trade_ref, s.symbol, t.trade_type,
               t.quantity, t.price_per_share,
               t.total_value, t.executed_at, t.trade_status
        FROM trade_records t
        JOIN stocks s ON t.stock_id = s.stock_id
        ORDER BY t.executed_at DESC
        LIMIT 10
    ");

    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    echo json_encode([]);
}
?>