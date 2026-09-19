<?php
require __DIR__ . '/../includes/auth.php';
if (!Auth::check()) json_out(['ok' => false, 'error' => 'Unauthenticated'], 401);

$jumuiyaId = (int)($_GET['jumuiya_id'] ?? 0);
$status    = trim($_GET['status'] ?? 'ACTIVE');
$q         = trim($_GET['q'] ?? '');

$sql = "SELECT id, member_code, full_name, phone, status
          FROM members
         WHERE 1 = 1";
$p = [];
if ($jumuiyaId > 0) {
    $sql .= " AND jumuiya_id = :j";
    $p['j'] = $jumuiyaId;
}
if ($status !== '') {
    $sql .= " AND status = :s";
    $p['s'] = $status;
}
if ($q !== '') {
    $sql .= " AND (full_name LIKE :q1 OR phone LIKE :q2 OR member_code LIKE :q3)";
    $like = '%' . $q . '%';
    $p['q1'] = $like;
    $p['q2'] = $like;
    $p['q3'] = $like;
}
$sql .= " ORDER BY full_name LIMIT 200";

json_out(['ok' => true, 'data' => Db::all($sql, $p)]);
