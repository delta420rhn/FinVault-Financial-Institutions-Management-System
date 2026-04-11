<?php
require 'config.php';

$data      = json_decode(file_get_contents("php://input"), true);
$trade_id  = intval($data['trade_id']);
$req_by    = intval($data['requested_by']);
$sent_to   = intval($data['sent_to']);
$pattern   = $conn->real_escape_string($data['pattern_type']);
$text      = $conn->real_escape_string($data['clarification_text']);
$deadline  = $conn->real_escape_string($data['deadline']);

// Auto-generate ref
$count = $conn->query("SELECT COUNT(*) as cnt FROM clarification_requests")->fetch_assoc()['cnt'];
$ref   = 'CLR-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

$stmt = $conn->prepare(
    "INSERT INTO clarification_requests
        (clarif_ref, trade_id, requested_by, sent_to, pattern_type, clarification_text, deadline)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("siissss", $ref, $trade_id, $req_by, $sent_to, $pattern, $text, $deadline);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "clarif_ref" => $ref]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>