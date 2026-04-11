<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$review_id   = (int)($data['review_id']    ?? 0);
$decision    = trim($data['decision']      ?? '');
$action_notes = trim($data['action_notes'] ?? '');

$compliance_officer = 4; // David Chowdhury

$valid_decisions = [
    'Escalate to Fraud Analyst',
    'Issue Policy Reminder',
    'Clear – No Violation',
    'Suspend Trading Privileges',
];

if (!$review_id || !in_array($decision, $valid_decisions)) {
    http_response_code(400);
    echo json_encode(['error' => 'review_id and a valid decision are required']);
    exit();
}

$db = getDB();

// Insert compliance action
$stmt = $db->prepare("
    INSERT INTO compliance_actions (review_id, decided_by, decision, action_notes)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param('iiss', $review_id, $compliance_officer, $decision, $action_notes);
$stmt->execute();
$stmt->close();

// Close the review
$stmt = $db->prepare("
    UPDATE compliance_reviews SET status = 'Closed', closed_at = NOW()
    WHERE review_id = ?
");
$stmt->bind_param('i', $review_id);
$stmt->execute();
$stmt->close();

$db->close();

echo json_encode(['success' => true, 'decision' => $decision]);
