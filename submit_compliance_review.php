<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$trade_ref  = trim($data['trade_ref']  ?? '');
$risk_level = trim($data['risk_level'] ?? 'Low');
$findings   = trim($data['findings']   ?? '');

$auditor_id         = 3; // Carol Islam
$compliance_officer = 4; // David Chowdhury

if (!$trade_ref || !$findings) {
    http_response_code(400);
    echo json_encode(['error' => 'trade_ref and findings are required']);
    exit();
}

$db = getDB();

// Look up the trade_id from the trade_ref
$stmt = $db->prepare("SELECT trade_id FROM trade_records WHERE trade_ref = ?");
$stmt->bind_param('s', $trade_ref);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['error' => "Trade ref '$trade_ref' not found"]);
    $db->close();
    exit();
}

$trade_id = $row['trade_id'];

// Generate review_ref
$count_row = $db->query("SELECT COUNT(*) AS cnt FROM compliance_reviews")->fetch_assoc();
$review_ref = 'CRV-' . str_pad($count_row['cnt'] + 1, 4, '0', STR_PAD_LEFT);

$stmt = $db->prepare("
    INSERT INTO compliance_reviews
        (review_ref, trade_id, submitted_by, assigned_to, risk_level, findings, status)
    VALUES (?, ?, ?, ?, ?, ?, 'Open')
");
$stmt->bind_param('ssiiss', $review_ref, $trade_id, $auditor_id, $compliance_officer, $risk_level, $findings);
$stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();
$db->close();

echo json_encode(['success' => true, 'review_ref' => $review_ref, 'review_id' => $new_id]);
