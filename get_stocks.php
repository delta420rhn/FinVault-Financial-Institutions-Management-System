<?php
header('Content-Type: application/json');
require 'db.php';

try {
    $stmt = $pdo->query("
        SELECT 
            stock_id,
            symbol,
            company_name,
            sector,
            shares_available,
            price_per_share,
            (shares_available * price_per_share) AS market_value,
            status
        FROM stocks
        ORDER BY symbol
    ");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>