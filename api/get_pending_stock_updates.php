<?php
require 'db.php';

$stmt = $pdo->query("
    SELECT r.request_id, s.symbol, s.company_name,
           r.old_quantity, r.new_quantity,
           r.reason, r.requested_at
    FROM stock_update_requests r
    JOIN stocks s ON r.stock_id = s.stock_id
    WHERE r.status = 'Pending'
    ORDER BY r.requested_at DESC
");

echo json_encode($stmt->fetchAll());