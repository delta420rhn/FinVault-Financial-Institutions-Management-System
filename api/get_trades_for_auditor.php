<?php
require 'db.php';

$stmt = $pdo->query("
    SELECT t.trade_id, t.trade_ref, s.symbol,
           t.trade_type, t.quantity,
           t.total_value, t.risk_score,
           u.full_name AS executed_by_name
    FROM trade_records t
    JOIN stocks s ON t.stock_id = s.stock_id
    JOIN users u ON t.executed_by = u.user_id
    ORDER BY t.executed_at DESC
");

echo json_encode($stmt->fetchAll());