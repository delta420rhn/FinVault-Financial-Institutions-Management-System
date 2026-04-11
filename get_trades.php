<?php
require_once 'config.php';

$db = getDB();

// Optional filter: ?status=Flagged or ?risk=High
$where_clauses = [];
$params        = [];
$types         = '';

if (!empty($_GET['status'])) {
    $where_clauses[] = "t.auditor_status = ?";
    $params[] = $_GET['status'];
    $types   .= 's';
}
if (!empty($_GET['risk'])) {
    $where_clauses[] = "t.risk_score = ?";
    $params[] = $_GET['risk'];
    $types   .= 's';
}

$where = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$sql = "
    SELECT
        t.trade_id,
        t.trade_ref,
        s.symbol,
        s.company_name,
        t.trade_type,
        t.order_mode,
        t.quantity,
        t.price_per_share,
        t.total_value,
        t.risk_score,
        t.auditor_status,
        t.trade_status,
        t.justification,
        t.executed_at,
        t.reviewed_at,
        u_exec.full_name AS executed_by_name,
        u_rev.full_name  AS reviewed_by_name
    FROM trade_records t
    JOIN stocks     s      ON t.stock_id    = s.stock_id
    JOIN users      u_exec ON t.executed_by = u_exec.user_id
    LEFT JOIN users u_rev  ON t.reviewed_by = u_rev.user_id
    $where
    ORDER BY t.executed_at DESC
";

if ($params) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query($sql);
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
$db->close();
