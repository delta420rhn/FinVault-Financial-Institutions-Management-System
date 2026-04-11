<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$sent_to = ($data['sent_to'] === 'Stock Manager') ? 1 : 2;
$ref = $data['ref'];
$priority = $data['priority'];
$details = $data['details'];
$deadline = $data['deadline'];

$stmt = $pdo->prepare("
    INSERT INTO correction_recommendations
    (issued_by, sent_to, related_ref, details, priority, deadline)
    VALUES (3, ?, ?, ?, ?, ?)
");

$stmt->execute([$sent_to, $ref, $details, $priority, $deadline]);

echo json_encode(["success"=>true]);