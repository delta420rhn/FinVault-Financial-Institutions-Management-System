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
            s.quantity AS shares_available,
            s.price_per_share

        FROM purchase_requests pr
        JOIN stocks s ON pr.stock_id = s.stock_id
        ORDER BY pr.requested_at DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);

} catch (Exception $e) {
    // Surface the real error during development
    echo json_encode(["error" => $e->getMessage()]);
}
