<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'POST required'], 405);
csrf_check($_POST['csrf'] ?? null);

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

try {
    switch ($action) {
        case 'current':
            FinancialYear::makeCurrent($id);
            break;
        case 'close':
            FinancialYear::close($id);
            break;
        case 'reopen':
            FinancialYear::reopen($id);
            break;
        default:
            json_out(['ok' => false, 'error' => 'Unknown action'], 400);
    }
    json_out(['ok' => true]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
