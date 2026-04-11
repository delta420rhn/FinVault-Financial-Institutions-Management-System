<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$trade_id = $data['trade_id'];
$action = $data['action'];
$auditor_id = 3;

$status = ($action === 'clear') ? 'Cleared' : 'Flagged';

$stmt = $pdo->prepare("
    UPDATE trade_records
    SET auditor_status = ?,
        reviewed_by = ?,
        reviewed_at = NOW()
    WHERE trade_id = ?
");

$stmt->execute([$status, $auditor_id, $trade_id]);

echo json_encode(["success" => true]);