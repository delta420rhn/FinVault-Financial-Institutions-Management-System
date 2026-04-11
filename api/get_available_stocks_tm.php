<?php
require 'db.php';

$stmt = $pdo->query("
    SELECT symbol, quantity, price 
    FROM stocks
    ORDER BY symbol ASC
");

echo json_encode($stmt->fetchAll());