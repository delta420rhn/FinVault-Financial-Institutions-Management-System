<?php
require_once 'config.php';

$db = getDB();

// Use the view already defined in the SQL schema
$result = $db->query("SELECT * FROM vw_pending_stock_updates ORDER BY requested_at DESC");

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
$db->close();
