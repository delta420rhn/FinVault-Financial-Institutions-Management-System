<?php
// FinVault – Database Configuration
// ─────────────────────────────────────────────────────────────
// XAMPP defaults: host=localhost, user=root, pass=''
// Change DB_NAME to match whatever you named your database
// in phpMyAdmin when you imported the SQL script.
// ─────────────────────────────────────────────────────────────

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Leave empty if no password set in XAMPP
define('DB_NAME', 'finvault');  // ← Change this to your actual DB name if different

function getDB(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode([
            'error'  => 'Database connection failed',
            'detail' => $conn->connect_error,
            'fix'    => 'Check: (1) XAMPP MySQL is running, (2) DB_NAME in api/config.php matches your phpMyAdmin database name, (3) DB_USER/DB_PASS are correct.'
        ]);
        exit();
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

// CORS + JSON headers for all API responses
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
