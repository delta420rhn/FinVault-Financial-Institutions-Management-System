<?php
header('Content-Type: application/json');
require 'db.php';

try {
    $stmt = $pdo->query("
        SELECT 
            pr.request_id,
            pr.quantity,
            pr.target_price,
            pr.status,
            pr.priority,
            pr.sm_note,
            pr.requested_by,
            pr.requested_at,

            s.symbol,
            s.company_name,
            s.shares_available,
            s.price_per_share

        FROM purchase_requests pr
        JOIN stocks s ON pr.stock_id = s.stock_id
        ORDER BY pr.requested_at DESC
    ");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>