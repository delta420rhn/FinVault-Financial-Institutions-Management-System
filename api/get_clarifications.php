<?php
require 'db.php';

$stmt = $pdo->query("
SELECT c.clarif_ref, t.trade_ref, s.symbol,
       c.pattern_type as pattern,
       c.deadline,
       c.status
FROM clarification_requests c
JOIN trade_records t ON c.trade_id = t.trade_id
JOIN stocks s ON t.stock_id = s.stock_id
");

echo json_encode($stmt->fetchAll());