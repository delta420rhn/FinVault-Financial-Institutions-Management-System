<?php
require_once 'config.php';

$db = getDB();

// Total stocks listed
$r1 = $db->query("SELECT COUNT(*) AS total FROM stocks");
$total_stocks = $r1->fetch_assoc()['total'];

// Total portfolio value
$r2 = $db->query("SELECT SUM(market_value) AS portfolio FROM stocks");
$portfolio = $r2->fetch_assoc()['portfolio'] ?? 0;

// Approved updates
$r3 = $db->query("SELECT COUNT(*) AS approved FROM stock_update_requests WHERE status = 'Approved'");
$approved_updates = $r3->fetch_assoc()['approved'];

// Pending confirmations (purchase requests)
$r4 = $db->query("SELECT COUNT(*) AS pending FROM purchase_requests WHERE status = 'Pending'");
$pending = $r4->fetch_assoc()['pending'];

echo json_encode([
    'total_stocks'     => (int)$total_stocks,
    'portfolio_value'  => (float)$portfolio,
    'approved_updates' => (int)$approved_updates,
    'pending_confirmations' => (int)$pending,
]);

$db->close();
