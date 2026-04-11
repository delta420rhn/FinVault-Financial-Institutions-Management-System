<?php
require 'config.php';
$result = $conn->query("SELECT * FROM vw_open_clarifications");
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
?>