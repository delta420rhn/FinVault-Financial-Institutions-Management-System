<?php
header('Content-Type: application/json');
require 'db.php';

try {
    $totalStocks = $pdo->query("
        SELECT COUNT(*) FROM stocks
    ")->fetchColumn();

    $approvedUpdates = $pdo->query("
        SELECT COUNT(*) FROM stock_update_requests
        WHERE status = 'Approved'
    ")->fetchColumn();

    $pendingConfirmations = $pdo->query("
        SELECT COUNT(*) FROM purchase_requests
        WHERE status = 'Pending'
    ")->fetchColumn();

    $portfolioValue = $pdo->query("
        SELECT IFNULL(SUM(market_value), 0) FROM stocks
    ")->fetchColumn();

    echo json_encode([
        "total_stocks" => (int)$totalStocks,
        "approved_updates" => (int)$approvedUpdates,
        "pending_confirmations" => (int)$pendingConfirmations,
        "portfolio_value" => (float)$portfolioValue
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>