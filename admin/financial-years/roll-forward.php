<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'POST required'], 405);
csrf_check($_POST['csrf'] ?? null);

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) json_out(['ok' => false, 'error' => 'Missing id'], 400);

try {
    $res = FinancialYear::closeAndRollForward($id);
    json_out(['ok' => true, 'next_year' => $res['next_year'], 'copied' => $res['copied']]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
