<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$request_id = $data['request_id'] ?? null;
$action = $data['action'] ?? '';
$sm_note = trim($data['sm_note'] ?? '');
$confirmed_by = 1; // Stock Manager

if (!$request_id || !in_array($action, ['confirm', 'reject'])) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

try {
    if ($action === 'confirm') {
        $stmt = $pdo->prepare("
            UPDATE purchase_requests
            SET status = 'Confirmed',
                confirmed_by = ?,
                confirmed_price = (
                    SELECT price_per_share FROM stocks
                    WHERE stock_id = purchase_requests.stock_id
                ),
                sm_note = ?,
                confirmed_at = NOW()
            WHERE request_id = ?
        ");
        $stmt->execute([$confirmed_by, $sm_note, $request_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE purchase_requests
            SET status = 'Rejected',
                confirmed_by = ?,
                sm_note = ?,
                confirmed_at = NOW()
            WHERE request_id = ?
        ");
        $stmt->execute([$confirmed_by, $sm_note, $request_id]);
    }

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>