<?php
// api/db.php
header("Content-Type: application/json");

$host = "localhost";
$dbname = "finvault";
$username = "root";
$password = ""; // Default for XAMPP

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
?>