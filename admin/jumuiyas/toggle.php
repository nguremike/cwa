<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('jumuiya.edit');

$id = (int)($_GET['id'] ?? 0);
csrf_check($_GET['csrf'] ?? null);

$j = Db::one("SELECT * FROM jumuiyas WHERE id = :id", ['id' => $id]);
if (!$j) {
    http_response_code(404);
    exit('Jumuiya not found');
}

$newStatus = $j['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

Db::begin();
try {
    Db::update('jumuiyas', ['status' => $newStatus], 'id = :id', ['id' => $id]);
    Audit::log(
        'UPDATE',
        'jumuiyas',
        $id,
        ['status' => $j['status']],
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
