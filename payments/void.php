<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.reverse');

$id = (int)($_GET['id'] ?? 0);
$p  = Payment::find($id);
if (!$p) {
    http_response_code(404);
    exit('Payment not found');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $reason = trim($_POST['reason'] ?? '');

    require_once __DIR__ . '/../classes/ReversalGuard.php';
    $state = ReversalGuard::canVoid($id, $reason);

    if (!$state['allowed'] && !$state['needs_approval']) {
        $errors[] = $state['reason'];
    } elseif ($state['needs_approval']) {
        try {
            ReversalGuard::createApproval($id, $reason);
            Audit::log('UPDATE', 'approval_requests', null, null, [
                'payment_id' => $id,
                'amount' => (float)$p['amount'],
                'reason' => $reason,
            ]);
            header('Location: receipt.php?id=' . $id . '&pending=1');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Could not create approval request: ' . $e->getMessage();
        }
    } else {
        try {
            Payment::void($id, $reason, Auth::id());
            header('Location: receipt.php?id=' . $id);
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Void Payment';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-ban me-2"></i>Void Payment</h4>
    <a href="receipt.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="alert alert-warning small">
    <i class="fa-solid fa-triangle-exclamation me-1"></i>
    Voiding writes a reversal for every allocation on <strong><?= e($p['receipt_no']) ?></strong>
    and marks the payment as <strong>VOIDED</strong>. The original and the reversal both remain in the ledger.
</div>

<form method="post" class="card shadow-sm border-0">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="card-body">
        <label class="form-label small">Reason *</label>
        <textarea name="reason" class="form-control" rows="3" required></textarea>
    </div>
    <div class="card-footer bg-white text-end">
        <a href="receipt.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-danger">
            <i class="fa-solid fa-ban me-1"></i> Void Payment
        </button>
    </div>
</form>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>