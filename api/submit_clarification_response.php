<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$text = $data['text'];

$stmt = $pdo->prepare("
UPDATE clarification_requests
SET response = ?, status='Responded'
WHERE status='Pending'
LIMIT 1
");

$stmt->execute([$text]);

echo json_encode(["success"=>true]);