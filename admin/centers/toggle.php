<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('center.edit');

$id = (int)($_GET['id'] ?? 0);
csrf_check($_GET['csrf'] ?? null);

$c = Db::one("SELECT * FROM centers WHERE id = :id", ['id' => $id]);
if (!$c) {
    http_response_code(404);
    exit('Center not found');
}

$newStatus = $c['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

Db::begin();
try {
    Db::update('centers', ['status' => $newStatus], 'id = :id', ['id' => $id]);
    Audit::log(
        'UPDATE',
        'centers',
        $id,
        ['status' => $c['status']],
        ['status' => $newStatus]
    );
    Db::commit();
} catch (Throwable $e) {
    Db::rollback();
    http_response_code(500);
    exit('Toggle failed: ' . $e->getMessage());
}

header('Location: index.php?ok=' . ($newStatus === 'ACTIVE' ? 'activated' : 'deactivated'));
exit;
