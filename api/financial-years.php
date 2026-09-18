<?php
require __DIR__ . '/../includes/auth.php';
if (!Auth::check()) json_out(['ok' => false, 'error' => 'Unauthenticated'], 401);

$status = $_GET['status'] ?? 'ALL';
if (!in_array($status, ['OPEN', 'CLOSED', 'ALL'], true)) $status = 'ALL';

$sql = "SELECT id, year, start_date, end_date, status, is_current FROM financial_years";
$p = [];
if ($status !== 'ALL') {
    $sql .= " WHERE status = :s";
    $p['s'] = $status;
}
$sql .= " ORDER BY year DESC";

json_out(['ok' => true, 'data' => Db::all($sql, $p)]);
