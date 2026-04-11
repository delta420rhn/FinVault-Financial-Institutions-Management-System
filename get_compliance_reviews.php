<?php
require_once 'config.php';

$db = getDB();

$sql = "
    SELECT
        cr.review_id,
        cr.review_ref,
        t.trade_ref,
        s.symbol,
        cr.risk_level,
        cr.findings,
        cr.status,
        cr.submitted_at,
        cr.closed_at,
        u_au.full_name AS submitted_by_name,
        ca.decision,
        ca.action_notes,
        ca.decided_at
    FROM compliance_reviews cr
    JOIN trade_records t   ON cr.trade_id     = t.trade_id
    JOIN stocks        s   ON t.stock_id      = s.stock_id
    JOIN users      u_au   ON cr.submitted_by = u_au.user_id
    LEFT JOIN compliance_actions ca ON ca.review_id = cr.review_id
    ORDER BY cr.submitted_at DESC
";

$result = $db->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
$db->close();
