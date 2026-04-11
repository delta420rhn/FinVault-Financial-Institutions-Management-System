<?php
require_once 'config.php';

$db = getDB();
$sql = "
    SELECT
        cr.correction_id,
        cr.related_ref,
        cr.details,
        cr.priority,
        cr.deadline,
        cr.status,
        cr.issued_at,
        cr.resolution_note,
        u.full_name AS issued_by_name
    FROM correction_recommendations cr
    JOIN users u ON cr.issued_by = u.user_id
    WHERE cr.sent_to = 1
    ORDER BY cr.issued_at DESC
";
$result = $db->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
$db->close();
