<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$trade_id = $data['trade_id'];
$pattern = $data['pattern'];
$desc = $data['desc'];

$stmt = $pdo->prepare("
    INSERT INTO clarification_requests
    (clarif_ref, trade_id, requested_by, sent_to, pattern_type, clarification_text)
    VALUES (
        CONCAT('CLR-', FLOOR(RAND()*10000)),
        (SELECT trade_id FROM trade_records WHERE trade_ref=?),
        5, 2, ?, ?
    )
");

$stmt->execute([$trade_id, $pattern, $desc]);

echo json_encode(["success"=>true]);