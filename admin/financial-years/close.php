<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Accept both GET (anchor fallback) and POST (AJAX)
$params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$csrf   = $params['csrf']   ?? null;
$action = $params['action'] ?? '';
$id     = (int)($params['id'] ?? 0);

$respond = function (bool $ok, ?string $error = null) use ($isAjax) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
        exit;
    }
    if ($ok) {
        // Determine the ok message
        $okKey = $_GET['action'] ?? '';
        $msg   = match ($okKey) {
            'current' => 'current',
            'close'   => 'closed',
            'reopen'  => 'reopened',
            default   => 'updated',
        };
        header('Location: index.php?ok=' . $msg);
        exit;
    }
    header('Location: index.php?err=' . urlencode((string)$error));
    exit;
};

try {
    csrf_check($csrf);

    if ($id <= 0)       throw new RuntimeException('Missing financial year id.');
    if ($action === '') throw new RuntimeException('Missing action.');

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
            throw new RuntimeException('Unknown action: ' . $action);
    }
    $respond(true);
} catch (Throwable $e) {
    $respond(false, $e->getMessage());
}
