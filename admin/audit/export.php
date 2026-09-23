<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('audit.view');

$f = [
    'user_id'    => (int)($_GET['user_id'] ?? 0) ?: null,
    'action'     => trim($_GET['action']     ?? ''),
    'table_name' => trim($_GET['table_name'] ?? ''),
    'from'       => trim($_GET['from']       ?? ''),
    'to'         => trim($_GET['to']         ?? ''),
    'q'          => trim($_GET['q']          ?? ''),
];

$sql = "SELECT a.*, u.full_name AS user_name, u.username
          FROM audit_logs a
          LEFT JOIN users u ON u.id = a.user_id
         WHERE 1 = 1";
$p = [];
if ($f['user_id']) {
    $sql .= " AND a.user_id = :uid";
    $p['uid'] = $f['user_id'];
}
if ($f['action'] !== '') {
    $sql .= " AND a.action = :act";
    $p['act'] = $f['action'];
}
if ($f['table_name'] !== '') {
    $sql .= " AND a.table_name = :tbl";
    $p['tbl'] = $f['table_name'];
}
if ($f['from'] !== '') {
    $sql .= " AND DATE(a.created_at) >= :fr";
    $p['fr'] = $f['from'];
}
if ($f['to'] !== '') {
    $sql .= " AND DATE(a.created_at) <= :to";
    $p['to'] = $f['to'];
}
if ($f['q'] !== '') {
    $sql .= " AND (a.record_id LIKE :q1 OR a.new_values LIKE :q2 OR a.old_values LIKE :q3)";
    $like = '%' . $f['q'] . '%';
    $p['q1'] = $like;
    $p['q2'] = $like;
    $p['q3'] = $like;
}
$sql .= " ORDER BY a.id DESC LIMIT 50000";

$rows = Db::all($sql, $p);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="audit-' . date('Ymd-His') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['ID', 'When', 'User', 'Username', 'Action', 'Table', 'Record', 'IP', 'Old', 'New']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'],
        $r['created_at'],
        $r['user_name'],
        $r['username'],
        $r['action'],
        $r['table_name'],
        $r['record_id'],
        $r['ip_address'],
        $r['old_values'],
        $r['new_values'],
    ]);
}
fclose($out);
exit;
