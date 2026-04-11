<?php
require 'db.php';

$stmt = $pdo->query("
   SELECT cr.review_id, cr.review_ref, t.trade_ref,
          s.symbol, cr.risk_level, cr.findings,
          cr.status, cr.submitted_at
   FROM compliance_reviews cr
   JOIN trade_records t ON cr.trade_id = t.trade_id
   JOIN stocks s ON t.stock_id = s.stock_id
   ORDER BY cr.submitted_at DESC
");

echo json_encode($stmt->fetchAll());