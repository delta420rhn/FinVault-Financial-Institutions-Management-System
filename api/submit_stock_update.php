<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$stock_id = $data['stock_id'] ?? null;
$new_quantity = $data['new_quantity'] ?? null;
$reason = trim($data['reason'] ?? '');
$requested_by = 1; // Stock Manager (Alice Rahman)

if (!$stock_id || $new_quantity === null || !$reason) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

try {
    // Get current quantity
    $stmt = $pdo->prepare("SELECT shares_available FROM stocks WHERE stock_id = ?");
    $stmt->execute([$stock_id]);
    $old_quantity = $stmt->fetchColumn();

    if ($old_quantity === false) {
        echo json_encode(["success" => false, "error" => "Stock not found"]);
        exit;
    }

    // Insert update request
    $stmt = $pdo->prepare("
        INSERT INTO stock_update_requests
        (stock_id, requested_by, old_quantity, new_quantity, reason, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([
        $stock_id,
        $requested_by,
        $old_quantity,
        $new_quantity,
        $reason
    ]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>