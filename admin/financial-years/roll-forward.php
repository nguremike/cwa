<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

header('Content-Type: application/json');

$params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
try {
    csrf_check($params['csrf'] ?? null);
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) throw new RuntimeException('Missing id.');

    $res = FinancialYear::closeAndRollForward($id);
    json_out(['ok' => true, 'next_year' => $res['next_year'], 'copied' => $res['copied']]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
