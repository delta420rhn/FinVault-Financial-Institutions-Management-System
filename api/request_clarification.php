<?php
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$trade_id = $data['trade_id'];
$pattern = $data['pattern'];
$message = $data['message'];
$deadline = $data['deadline'];

$stmt = $conn->prepare("INSERT INTO clarifications (trade_id, pattern, message, deadline) VALUES (?,?,?,?)");
$stmt->bind_param("ssss", $trade_id, $pattern, $message, $deadline);

$stmt->execute();

echo json_encode(["status"=>"sent"]);
?>