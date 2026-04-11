<?php
require_once 'config.php';

$db = getDB();

$result = $db->query("
    SELECT policy_id, policy_ref, title, category, recipients, effective_date, status, issued_at
    FROM policy_updates
    ORDER BY issued_at DESC
");

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
$db->close();
