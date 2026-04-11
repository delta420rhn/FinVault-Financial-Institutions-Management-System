<?php
require 'db.php';

$stmt = $pdo->query("
   SELECT policy_ref, title, category,
          issued_at, effective_date,
          recipients, status
   FROM policy_updates
   ORDER BY issued_at DESC
");

echo json_encode($stmt->fetchAll());