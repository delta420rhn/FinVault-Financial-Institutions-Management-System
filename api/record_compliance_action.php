<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$review_ref = $data['review_ref'];
$decision = $data['decision'];
$notes = $data['notes'];
$decided_by = 4;

$stmt = $pdo->prepare("
   INSERT INTO compliance_actions
   (review_id, decided_by, decision, action_notes)
   VALUES (
       (SELECT review_id FROM compliance_reviews WHERE review_ref=?),
       ?, ?, ?
   )
");

$stmt->execute([$review_ref, $decided_by, $decision, $notes]);

// Close review
$pdo->prepare("
   UPDATE compliance_reviews
   SET status='Closed', closed_at=NOW()
   WHERE review_ref=?
")->execute([$review_ref]);

echo json_encode(["success"=>true]);