<?php
require_once 'config.php';

$db = getDB();
$result = $db->query("SELECT stock_id, symbol, company_name, sector, shares_available, price_per_share, market_value, status FROM stocks ORDER BY symbol");

$stocks = [];
while ($row = $result->fetch_assoc()) {
    $stocks[] = $row;
}

echo json_encode($stocks);
$db->close();
