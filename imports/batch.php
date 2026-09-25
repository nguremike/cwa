<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.view');

$id = (int)($_GET['id'] ?? 0);
$batch = Db::one(
    "SELECT b.*, u.full_name AS uploader, v.full_name AS voider
       FROM import_batches b
       LEFT JOIN users u ON u.id = b.uploaded_by
       LEFT JOIN users v ON v.id = b.voided_by
      WHERE b.id = :id",
    ['id' => $id]
);
if (!$batch) {
    http_response_code(404);
    exit('Batch not found');
}

$errors = [];

// Void batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'void') {
    csrf_check($_POST['csrf'] ?? null);
    require_permission('payment.reverse');
    $reason = trim($_POST['reason'] ?? '');
    if (mb_strlen($reason) < 10) {
        $errors[] = 'Reason must be at least 10 characters.';
    }
    if ($batch['status'] !== 'POSTED') {
        $errors[] = 'Batch is already voided.';
    }
    if (!$errors) {
        Db::begin();
        try {
            $payments = Db::all("SELECT id FROM payments WHERE import_batch_id = :b AND status = 'ACTIVE'", ['b' => $id]);
            foreach ($payments as $p) {
                Payment::void((int)$p['id'], 'Batch void: ' . $reason, Auth::id());
            }
            Db::update('import_batches', [
                'status'      => 'VOIDED',
                'voided_by'   => Auth::id(),
                'voided_at'   => date('Y-m-d H:i:s'),
                'void_reason' => $reason,
            ], 'id = :id', ['id' => $id]);

            Audit::log(
                'REVERSAL',
                'import_batches',
                $id,
                ['status' => 'POSTED'],
                ['status' => 'VOIDED', 'reason' => $reason, 'count' => count($payments)]
            );

            Db::commit();
            header('Location: batch.php?id=' . $id);
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = 'Void failed: ' . $e->getMessage();
        }
    }
}

$payments = Db::all(
    "SELECT p.id, p.receipt_no, p.payment_date, p.amount, p.payment_method, p.reference_no,
            p.status, m.full_name, m.member_code
       FROM payments p
       JOIN members m ON m.id = p.member_id
      WHERE p.import_batch_id = :b
      ORDER BY p.id ASC",
    ['b' => $id]
);

$pageTitle = 'Import Batch ' . $batch['batch_ref'];
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fa-solid fa-file-import me-2"></i>
        Batch <?= e($batch['batch_ref']) ?>
        <span class="badge bg-<?= $batch['status'] === 'POSTED' ? 'success' : 'danger' ?> ms-2">
            <?= e($batch['status']) ?>
        </span>
    </h4>
    <a href="batches.php" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <dl class="row mb-0 small">
            <dt class="col-3 text-muted">Type</dt>
            <dd class="col-9"><?= e($batch['contribution_type']) ?></dd>
            <dt class="col-3 text-muted">Year</dt>
            <dd class="col-9"><?= (int)$batch['year'] ?></dd>
            <dt class="col-3 text-muted">Source file</dt>
            <dd class="col-9"><?= e($batch['source_file']) ?></dd>
            <dt class="col-3 text-muted">Uploaded</dt>
            <dd class="col-9"><?= e($batch['uploaded_at']) ?> by <?= e($batch['uploader'] ?? '—') ?></dd>
            <dt class="col-3 text-muted">Rows</dt>
            <dd class="col-9"><?= (int)$batch['rows_imported'] ?> imported · <?= (int)$batch['rows_skipped'] ?> skipped</dd>
            <dt class="col-3 text-muted">Amount</dt>
            <dd class="col-9"><?= number_format((float)$batch['total_amount'], 2) ?></dd>
            <?php if ($batch['status'] === 'VOIDED'): ?>
                <dt class="col-3 text-muted">Voided</dt>
                <dd class="col-9">
                    <?= e($batch['voided_at']) ?> by <?= e($batch['voider'] ?? '—') ?> —
                    “<?= e($batch['void_reason']) ?>”
                </dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<?php if ($batch['status'] === 'POSTED' && (user_can('payment.reverse') || user_can('*'))): ?>
    <form method="post" class="card shadow-sm border-0 mb-3"
        onsubmit="return confirm('Void every ACTIVE payment in this batch?');">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="void">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label small">Void batch reason (min 10 chars)</label>
                <input name="reason" class="form-control" required minlength="10">
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-danger w-100">
                    <i class="fa-solid fa-ban me-1"></i> Void entire batch
                </button>
            </div>
        </div>
    </form>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white"><strong>Payments in this batch</strong></div>
    <div class="card-body p-0">
        <table id="tblBatchPay" class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Receipt</th>
                    <th>Date</th>
                    <th>Member</th>
                    <th class="text-end">Amount</th>
                    <th>Method</th>
                    <th>Ref</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="small"><?= e($p['receipt_no']) ?></td>
                        <td class="small"><?= e($p['payment_date']) ?></td>
                        <td class="small"><?= e($p['full_name']) ?><div class="text-muted"><?= e($p['member_code']) ?></div>
                        </td>
                        <td class="text-end small"><?= number_format((float)$p['amount'], 2) ?></td>
                        <td class="small"><?= e($p['payment_method']) ?></td>
                        <td class="small"><?= e($p['reference_no'] ?? '') ?></td>
                        <td class="small">
                            <span class="badge bg-<?= $p['status'] === 'ACTIVE' ? 'success' : 'danger' ?>"><?= e($p['status']) ?></span>
                        </td>
                        <td class="text-end">
                            <a href="../payments/receipt.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-receipt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        $('#tblBatchPay').DataTable({
            order: [
                [0, 'asc']
            ],
            pageLength: 100,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });
    });
</script>