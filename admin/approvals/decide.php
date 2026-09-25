<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('payment.reverse');
require_once __DIR__ . '/../../classes/ReversalGuard.php';

if (
    !in_array($_SESSION['user']['role_name'] ?? '', ['SUPER_ADMIN', 'PARISH_ADMIN'], true)
    && !user_can('*')
) {
    http_response_code(403);
    exit('Not allowed.');
}

$id = (int)($_GET['id'] ?? 0);
$a = Db::one(
    "SELECT a.*, p.receipt_no, p.amount AS payment_amount, m.full_name AS member_name
       FROM approval_requests a
       JOIN payments p ON p.id = a.payment_id
       JOIN members  m ON m.id = p.member_id
      WHERE a.id = :id",
    ['id' => $id]
);
if (!$a) {
    http_response_code(404);
    exit('Approval not found');
}
if ($a['status'] !== 'PENDING') {
    exit('Already decided.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $decision = $_POST['decision'] ?? '';
    $note     = trim($_POST['note'] ?? '');

    if (!in_array($decision, ['APPROVED', 'REJECTED'], true)) $errors[] = 'Pick approve or reject.';

    if (!$errors) {
        Db::begin();
        try {
            Db::update('approval_requests', [
                'status'        => $decision,
                'decided_by'    => Auth::id(),
                'decided_at'    => date('Y-m-d H:i:s'),
                'decision_note' => $note ?: null,
            ], 'id = :id', ['id' => $id]);

            if ($decision === 'APPROVED' && $a['request_type'] === 'VOID_PAYMENT') {
                Payment::void((int)$a['payment_id'], 'Approved: ' . $a['reason'], Auth::id());
            }

            Audit::log(
                'UPDATE',
                'approval_requests',
                $id,
                ['status' => 'PENDING'],
                ['status' => $decision, 'note' => $note]
            );

            Db::commit();
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Decide Approval';
require __DIR__ . '/../../templates/layout/header.php';
?>
<h4 class="mb-3">Decide Approval #<?= (int)$id ?></h4>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <dl class="row mb-0 small">
            <dt class="col-3 text-muted">Type</dt>
            <dd class="col-9"><?= e($a['request_type']) ?></dd>
            <dt class="col-3 text-muted">Receipt</dt>
            <dd class="col-9"><?= e($a['receipt_no']) ?></dd>
            <dt class="col-3 text-muted">Member</dt>
            <dd class="col-9"><?= e($a['member_name']) ?></dd>
            <dt class="col-3 text-muted">Amount</dt>
            <dd class="col-9"><?= number_format((float)$a['amount'], 2) ?></dd>
            <dt class="col-3 text-muted">Reason</dt>
            <dd class="col-9"><?= e($a['reason']) ?></dd>
        </dl>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="card shadow-sm border-0">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label small">Decision</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="decision" value="APPROVED" checked>
                <label class="form-check-label">Approve and execute</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="decision" value="REJECTED">
                <label class="form-check-label">Reject</label>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small">Decision note</label>
            <textarea name="note" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary"><i class="fa-solid fa-gavel me-1"></i> Submit</button>
    </div>
</form>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>