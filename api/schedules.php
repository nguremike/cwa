<?php
require __DIR__ . '/../includes/auth.php';
if (!Auth::check()) json_out(['ok' => false, 'error' => 'Unauthenticated'], 401);

$year = (int)($_GET['year'] ?? 0);
$type = strtoupper(trim($_GET['type'] ?? ''));

if ($year <= 0 || $type === '') {
    json_out(['ok' => false, 'error' => 'year and type are required'], 400);
}

$rows = Db::all(
    "SELECT component, month, required_amount
       FROM contribution_schedules
      WHERE year = :y AND contribution_type = :t
      ORDER BY component, month",
    ['y' => $year, 't' => $type]
);

json_out(['ok' => true, 'data' => $rows]);
