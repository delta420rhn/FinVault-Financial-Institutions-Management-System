<?php
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$trade_id = $data['trade_id'];
$symbol = $data['symbol'];
$verdict = $data['verdict'];
$summary = $data['summary'];
$action = $data['action'];

$stmt = $conn->prepare("INSERT INTO fraud_reports (trade_id, symbol, verdict, summary, action) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss", $trade_id, $symbol, $verdict, $summary, $action);

$stmt->execute();

echo json_encode(["status"=>"success"]);
?>