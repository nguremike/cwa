<?php
require __DIR__ . '/../includes/auth.php';

if (!Auth::check()) json_out(['ok' => false, 'error' => 'Unauthenticated'], 401);

$status = $_GET['status'] ?? 'ACTIVE';
if (!in_array($status, ['ACTIVE', 'INACTIVE', 'ALL'], true)) $status = 'ACTIVE';

$sql = "SELECT id, name, welfare_enabled FROM centers";
$params = [];
if ($status !== 'ALL') {
    $sql .= " WHERE status = :s";
    $params['s'] = $status;
}
$sql .= " ORDER BY name";

json_out(['ok' => true, 'data' => Db::all($sql, $params)]);
