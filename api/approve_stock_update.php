<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$request_id = $data['request_id'] ?? null;
$auditor_id = 3; // Carol

try {
    $stmt = $pdo->prepare("CALL sp_approve_stock_update(?, ?, ?)");
    $stmt->execute([$request_id, $auditor_id, 'Approved by Auditor']);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}