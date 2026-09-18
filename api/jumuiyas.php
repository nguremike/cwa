<?php
require __DIR__ . '/../includes/auth.php';

if (!Auth::check()) json_out(['ok' => false, 'error' => 'Unauthenticated'], 401);

$centerId = (int)($_GET['center_id'] ?? 0);
if ($centerId <= 0) json_out(['ok' => true, 'data' => []]);

$rows = Db::all(
    "SELECT id, name FROM jumuiyas
      WHERE center_id = :c AND status = 'ACTIVE'
      ORDER BY name",
    ['c' => $centerId]
);
json_out(['ok' => true, 'data' => $rows]);
