<?php
require 'db.php';

try {
    $stmt = $pdo->query("
        SELECT correction_id, details, priority,
               status, issued_at
        FROM correction_recommendations
        ORDER BY issued_at DESC
        LIMIT 10
    ");

    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    echo json_encode([]);
}
?>