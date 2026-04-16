<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$request_id = $data['request_id'] ?? null;
$action     = $data['action'] ?? '';
$note       = trim($data['sm_note'] ?? '');
$sm_id      = 1;

if (!$request_id || !in_array($action, ['confirm', 'reject'])) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

try {
    if ($action === 'confirm') {

        $stmt = $pdo->prepare("
            UPDATE purchase_requests pr
            JOIN stocks s ON pr.stock_id = s.stock_id
            SET 
                pr.status = 'Confirmed',
                pr.confirmed_price = s.price_per_share,
                pr.confirmed_by = ?,
                pr.sm_note = ?,
                pr.confirmed_at = NOW()
            WHERE pr.request_id = ?
        ");

        $stmt->execute([$sm_id, $note, $request_id]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE purchase_requests
            SET 
                status = 'Rejected',
                confirmed_by = ?,
                sm_note = ?,
                confirmed_at = NOW()
            WHERE request_id = ?
        ");

        $stmt->execute([$sm_id, $note, $request_id]);
    }

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>