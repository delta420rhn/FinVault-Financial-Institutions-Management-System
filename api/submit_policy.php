<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$ref = $data['ref'];
$title = $data['title'];
$details = $data['details'];
$date = $data['date'];
$category = $data['category'];
$recipients = $data['recipients'];
$issued_by = 4; // Compliance Officer

$stmt = $pdo->prepare("
   INSERT INTO policy_updates
   (policy_ref, title, details, category, issued_by, recipients, effective_date, status)
   VALUES (?, ?, ?, ?, ?, ?, ?, 'Upcoming')
");

$stmt->execute([$ref, $title, $details, $category, $issued_by, $recipients, $date]);

echo json_encode(["success" => true]);