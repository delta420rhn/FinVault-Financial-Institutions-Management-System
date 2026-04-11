<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$policy_ref    = trim($data['policy_ref']    ?? '');
$title         = trim($data['title']         ?? '');
$details       = trim($data['details']       ?? '');
$category      = trim($data['category']      ?? 'Other');
$recipients    = trim($data['recipients']    ?? 'Auditor Only');
$effective_date = trim($data['effective_date'] ?? '');

$compliance_officer = 4; // David Chowdhury

if (!$policy_ref || !$title || !$details || !$effective_date) {
    http_response_code(400);
    echo json_encode(['error' => 'policy_ref, title, details, and effective_date are required']);
    exit();
}

$db = getDB();

$stmt = $db->prepare("
    INSERT INTO policy_updates
        (policy_ref, title, details, category, issued_by, recipients, effective_date, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'Upcoming')
");
$stmt->bind_param('ssssisss',
    $policy_ref, $title, $details, $category,
    $compliance_officer, $recipients, $effective_date
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'policy_id' => $stmt->insert_id]);
} else {
    // Duplicate policy_ref is the most common error
    echo json_encode(['error' => 'Could not insert policy. Policy reference may already exist.']);
}

$stmt->close();
$db->close();
