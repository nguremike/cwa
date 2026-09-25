<?php
require __DIR__ . '/../includes/auth.php';
if (
    !in_array($_SESSION['user']['role_name'] ?? '', ['SUPER_ADMIN', 'PARISH_ADMIN'], true)
    && !user_can('*')
) {
    json_out(['ok' => true, 'count' => 0]);
}
$c = (int)(Db::one("SELECT COUNT(*) c FROM approval_requests WHERE status='PENDING'")['c'] ?? 0);
json_out(['ok' => true, 'count' => $c]);
