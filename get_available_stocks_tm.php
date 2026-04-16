<?php
header('Content-Type: application/json');
require 'db.php';

try {
    $stmt = $pdo->query("
        SELECT 
            stock_id,
            symbol,
            shares_available AS quantity,
            price_per_share AS price
        FROM stocks
        ORDER BY symbol
    ");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>