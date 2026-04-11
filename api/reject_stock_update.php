<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$request_id = $data['request_id'];
$note = $data['note'];
$auditor_id = 3;

$stmt = $pdo->prepare("
    UPDATE stock_update_requests
    SET status = 'Rejected',
        reviewed_by = ?,
        auditor_note = ?,
        reviewed_at = NOW()
    WHERE request_id = ?
");

$stmt->execute([$auditor_id, $note, $request_id]);

echo json_encode(["success" => true]);