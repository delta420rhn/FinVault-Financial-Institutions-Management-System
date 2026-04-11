<?php
// FinVault – Database Connection Test
// Visit: http://localhost/finvault/api/test_connection.php
// DELETE or rename this file after confirming connection works.

$host = 'localhost';
$user = 'root';
$pass = '';
$name = 'finvault';  // ← must match your phpMyAdmin DB name

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
  <title>FinVault – DB Test</title>
  <style>
    body { font-family: monospace; background: #07101f; color: #eef1f8; padding: 40px; }
    .ok  { color: #2ec47a; }
    .err { color: #e05555; }
    .box { background: #101e33; border: 1px solid #1e3355; padding: 20px; border-radius: 8px; max-width: 700px; }
    h2   { color: #c9a84c; }
    pre  { background: #0d1b2e; padding: 12px; border-radius: 4px; overflow-x: auto; }
  </style>
</head>
<body>
<div class="box">
  <h2>FinVault – Connection Test</h2>

  <?php
  $conn = @new mysqli($host, $user, $pass, $name);

  if ($conn->connect_error) {
      echo "<p class='err'>❌ <strong>Connection FAILED</strong></p>";
      echo "<p>Error: " . htmlspecialchars($conn->connect_error) . "</p>";
      echo "<hr><h3>How to fix:</h3><ul>
        <li>Make sure <strong>MySQL</strong> is running in the XAMPP Control Panel</li>
        <li>Open phpMyAdmin at <a href='http://localhost/phpmyadmin' style='color:#c9a84c'>http://localhost/phpmyadmin</a></li>
        <li>Check the exact database name listed there</li>
        <li>Edit <code>htdocs/finvault/api/config.php</code> — set <code>DB_NAME</code> to match</li>
        <li>If your MySQL root has a password, set <code>DB_PASS</code> accordingly</li>
      </ul>";
  } else {
      echo "<p class='ok'>✅ <strong>Connected successfully!</strong></p>";
      echo "<p>Host: <b>$host</b> &nbsp; User: <b>$user</b> &nbsp; Database: <b>$name</b></p>";

      // Check tables exist
      $tables_needed = ['roles','users','stocks','trade_records','purchase_requests',
                        'stock_update_requests','compliance_reviews','compliance_actions',
                        'audit_findings','correction_recommendations','policy_updates',
                        'clarification_requests','fraud_investigation_reports'];

      $result = $conn->query("SHOW TABLES");
      $existing = [];
      while ($row = $result->fetch_array()) { $existing[] = $row[0]; }

      echo "<h3>Table Check:</h3><pre>";
      $all_ok = true;
      foreach ($tables_needed as $t) {
          if (in_array($t, $existing)) {
              echo "  ✅  $t\n";
          } else {
              echo "  ❌  $t  ← MISSING\n";
              $all_ok = false;
          }
      }
      echo "</pre>";

      if (!$all_ok) {
          echo "<p class='err'>Some tables are missing — make sure you ran the full SQL script in phpMyAdmin.</p>";
      } else {
          echo "<p class='ok'>All 13 tables found. FinVault backend is ready.</p>";
          // Quick row counts
          echo "<h3>Seed Data Check:</h3><pre>";
          foreach (['stocks','users','trade_records','purchase_requests'] as $t) {
              $cnt = $conn->query("SELECT COUNT(*) FROM `$t`")->fetch_row()[0];
              $icon = $cnt > 0 ? '✅' : '⚠️';
              echo "  $icon  $t: $cnt rows\n";
          }
          echo "</pre>";
      }

      $conn->close();
  }
  ?>

  <p style="color:#4d6180;margin-top:20px;font-size:12px;">
    ⚠ Delete <code>api/test_connection.php</code> after confirming everything works.
  </p>
</div>
</body>
</html>
