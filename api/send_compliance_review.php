<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$ref = $data['ref'];
$risk = $data['risk'];
$findings = $data['findings'];

$stmt = $pdo->prepare("
    INSERT INTO compliance_reviews
    (review_ref, trade_id, submitted_by, assigned_to, risk_level, findings)
    VALUES (CONCAT('CRV-', FLOOR(RAND()*10000)), 
            (SELECT trade_id FROM trade_records WHERE trade_ref=?),
            3, 4, ?, ?)
");

$stmt->execute([$ref, $risk, $findings]);

echo json_encode(["success"=>true]);