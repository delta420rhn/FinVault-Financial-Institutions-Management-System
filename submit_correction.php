<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$sent_to_role = trim($data['sent_to']     ?? '');   // 'Stock Manager' or 'Trade Manager'
$related_ref  = trim($data['related_ref'] ?? '');
$details      = trim($data['details']     ?? '');
$priority     = trim($data['priority']    ?? 'Standard');
$deadline     = trim($data['deadline']    ?? '');

$auditor_id = 3; // Carol Islam

if (!$sent_to_role || !$details) {
    http_response_code(400);
    echo json_encode(['error' => 'sent_to and details are required']);
    exit();
}

// Map role name to user_id
$role_map = ['Stock Manager' => 1, 'Trade Manager' => 2];
if (!isset($role_map[$sent_to_role])) {
    echo json_encode(['error' => "Unknown role '$sent_to_role'. Use 'Stock Manager' or 'Trade Manager'"]);
    exit();
}
$sent_to_id = $role_map[$sent_to_role];

$db = getDB();

$stmt = $db->prepare("
    INSERT INTO correction_recommendations
        (issued_by, sent_to, related_ref, details, priority, deadline, status)
    VALUES (?, ?, ?, ?, ?, ?, 'Open')
");
$deadline_val = $deadline ?: null;
$stmt->bind_param('iissss', $auditor_id, $sent_to_id, $related_ref, $details, $priority, $deadline_val);
$stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();
$db->close();

echo json_encode(['success' => true, 'correction_id' => $new_id]);
