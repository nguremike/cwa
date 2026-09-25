<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.view');

$id = (int)($_GET['id'] ?? 0);
$p  = Payment::find($id);
if (!$p) {
    http_response_code(404);
    exit('Payment not found');
}
// $alloc = Payment::allocations($id);
// Effective allocation for display: sum signed amounts per (component, month),
// then show only the positive results.
$allocRows = Db::all(
    "SELECT component, month,
            COALESCE(SUM(amount),0) AS amount
       FROM payment_allocations
      WHERE payment_id = :id
      GROUP BY component, month
      HAVING amount <> 0
      ORDER BY id",
    ['id' => $id]
);
$alloc = array_map(fn($r) => [
    'component'         => $r['component'],
    'month'             => $r['month'],
    'amount'            => (float)$r['amount'],
    'allocation_method' => 'MANUAL',
], $allocRows);

$pageTitle = 'Receipt ' . $p['receipt_no'];
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-0">Catholic Women Association</h5>
                        <div class="text-muted small">Payment Receipt</div>
                    </div>
                    <div class="text-end">
                        <div><strong><?= e($p['receipt_no']) ?></strong></div>
                        <div class="text-muted small"><?= e($p['payment_date']) ?></div>
                    </div>
                </div>

                <hr>

                <div class="row small mb-3">
                    <div class="col-6">
                        <div class="text-muted">Member</div>
                        <div><strong><?= e($p['full_name']) ?></strong></div>
                        <div><?= e($p['member_code']) ?> · <?= e($p['phone']) ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-muted">Center / Jumuiya</div>
                        <div><?= e($p['center_name']) ?></div>
                        <div><?= e($p['jumuiya_name']) ?></div>
                    </div>
                </div>

                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Allocation</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alloc as $a): ?>
                            <tr>
                                <td>
                                    <?php
                                    $label = $a['component'];
                                    if ($a['month']) $label .= ' · ' . date('M', mktime(0, 0, 0, (int)$a['month'], 1));
                                    if ($a['allocation_method'] === 'REVERSAL') $label .= ' (reversal)';
                                    ?>
                                    <?= e($label) ?>
                                </td>
                                <td class="text-end"><?= number_format((float)$a['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Payment</th>
                            <th class="text-end"><?= number_format((float)$p['amount'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>

                <?php if ($p['status'] === 'VOIDED'): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        This payment has been voided.
                    </div>
                <?php endif; ?>

                <div class="text-muted small">
                    Method: <?= e($p['payment_method']) ?>
                    <?= $p['reference_no'] ? ' · Ref: ' . e($p['reference_no']) : '' ?><br>
                    Entered by: <?= e($p['entered_by_name'] ?? '—') ?>
                </div>

                <div class="text-end mt-3">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> Print
                    </button>
                    <?php if ($p['status'] === 'ACTIVE' && (user_can('payment.reverse') || user_can('*'))): ?>
                        <a href="void.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-danger btn-sm">
                            <i class="fa-solid fa-ban me-1"></i> Void
                        </a>
                    <?php endif; ?>

                    <?php
                    $check = Payment::canCorrect($p['id']);
                    if ($p['status'] === 'ACTIVE' && $check['ok'] && (user_can('payment.reverse') || user_can('*'))):
                    ?>
                        <a href="correct.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-warning btn-sm">
                            <i class="fa-solid fa-sliders me-1"></i> Correct allocation
                        </a>
                    <?php elseif ($p['status'] === 'ACTIVE' && !$check['ok'] && (user_can('payment.reverse') || user_can('*'))): ?>
                        <button class="btn btn-outline-secondary btn-sm" disabled title="<?= e($check['reason']) ?>">
                            <i class="fa-solid fa-sliders me-1"></i> Correction not available
                        </button>
                    <?php endif; ?>
                    <?php if ($p['status'] === 'VOIDED'):
                        $v = Db::one("SELECT v.*, u.full_name AS uname FROM payment_voids v
                  LEFT JOIN users u ON u.id = v.voided_by
                  WHERE v.payment_id = :p ORDER BY v.id DESC LIMIT 1", ['p' => $p['id']]);
                        if ($v): ?>
                            <div class="alert alert-secondary py-2 small mt-3">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Voided by <strong><?= e($v['uname'] ?? '—') ?></strong>
                                on <?= e($v['voided_at']) ?> —
                                “<?= e($v['reason']) ?>”
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($_GET['pending'])): ?>
                        <div class="alert alert-warning py-2 small">
                            <i class="fa-solid fa-hourglass-half me-1"></i>
                            Your void request is pending approval. You will be notified once it is decided.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>