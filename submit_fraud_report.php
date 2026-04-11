<?php
require 'config.php';

$data     = json_decode(file_get_contents("php://input"), true);
$trade_id = intval($data['trade_id']);
$sub_by   = intval($data['submitted_by']);
$sent_to  = intval($data['sent_to']);
$verdict  = $conn->real_escape_string($data['verdict']);
$evidence = $conn->real_escape_string($data['evidence_summary']);
$action   = $conn->real_escape_string($data['recommended_action']);

$count = $conn->query("SELECT COUNT(*) as cnt FROM fraud_investigation_reports")->fetch_assoc()['cnt'];
$ref   = 'FIR-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

$stmt = $conn->prepare(
    "INSERT INTO fraud_investigation_reports
        (report_ref, trade_id, submitted_by, sent_to, verdict, evidence_summary, recommended_action)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("siissss", $ref, $trade_id, $sub_by, $sent_to, $verdict, $evidence, $action);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "report_ref" => $ref]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>