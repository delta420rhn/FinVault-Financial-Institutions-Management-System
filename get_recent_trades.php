<?php
require_once 'config.php';

$db = getDB();
$sql = "
    SELECT
        t.trade_ref,
        s.symbol,
        t.trade_type,
        t.quantity,
        t.price_per_share,
        t.total_value,
        t.trade_status,
        t.executed_at
    FROM trade_records t
    JOIN stocks s ON t.stock_id = s.stock_id
    ORDER BY t.executed_at DESC
    LIMIT 10
";
$result = $db->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$db->close();
