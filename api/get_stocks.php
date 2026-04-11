<?php
require 'db.php';

try {
    $stmt = $pdo->query("
        SELECT stock_id, symbol, company_name, sector,
               shares_available, price_per_share,
               market_value, status
        FROM stocks
        ORDER BY symbol
    ");

    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    echo json_encode([]);
}
?>