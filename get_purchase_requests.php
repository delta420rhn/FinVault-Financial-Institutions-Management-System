<?php
require_once 'config.php';

$db = getDB();
$sql = "
    SELECT
        pr.request_id,
        s.symbol,
        s.company_name,
        s.shares_available,
        s.price_per_share,
        pr.quantity,
        pr.target_price,
        pr.confirmed_price,
        pr.priority,
        pr.status,
        pr.sm_note,
        pr.justification,
        pr.requested_at,
        pr.confirmed_at,
        u.full_name AS requested_by_name
    FROM purchase_requests pr
    JOIN stocks s ON pr.stock_id = s.stock_id
    JOIN users u ON pr.requested_by = u.user_id
    ORDER BY
        FIELD(pr.status,'Pending','Confirmed','Rejected'),
        pr.requested_at DESC
";
$result = $db->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$db->close();
