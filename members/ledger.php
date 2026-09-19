<?php
require __DIR__ . '/../includes/auth.php';
require_permission('member.view');

$id   = (int)($_GET['id'] ?? 0);
$year = (int)($_GET['year'] ?? current_year());

$m = Member::find($id);
if (!$m) {
    http_response_code(404);
    exit('Member not found');
}

$regStructure = null;
$welStructure = null;
$regError = null;
$welError = null;
try {
    $regStructure = AllocationEngine::outstandingStructure($id, $year, 'REGISTRATION');
} catch (Throwable $e) {
    $regError = $e->getMessage();
}
try {
    $welStructure = AllocationEngine::outstandingStructure($id, $year, 'WELFARE');
} catch (Throwable $e) {
    $welError = $e->getMessage();
}

$advances = Db::all(
    "SELECT contribution_type, amount
       FROM member_advances
      WHERE member_id = :m AND year = :y AND amount <> 0
      ORDER BY contribution_type",
    ['m' => $id, 'y' => $year]
);

$recentPayments = Db::all(
    "SELECT id, receipt_no, payment_date, amount, payment_type, status
       FROM payments
      WHERE member_id = :m AND year = :y
      ORDER BY id DESC LIMIT 20",
    ['m' => $id, 'y' => $year]
);

$recentAlloc = Db::all(
    "SELECT pa.*, p.receipt_no
       FROM payment_allocations pa
       JOIN payments p ON p.id = pa.payment_id
      WHERE pa.member_id = :m AND pa.year = :y
      ORDER BY pa.id DESC LIMIT 40",
    ['m' => $id, 'y' => $year]
);

$pageTitle = 'Member Ledger';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fa-solid fa-list-check me-2"></i>Ledger Snapshot
        <span class="text-muted small ms-2">
            <?= e($m['member_code']) ?> · <?= e($m['full_name']) ?> · <?= (int)$year ?>
        </span>
    </h4>
    <div>
        <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Profile</a>
        <form method="get" class="d-inline">
            <input type="hidden" name="id" value="<?= $id ?>">
            <select name="year" class="form-select form-select-sm d-inline-block w-auto"
                onchange="this.form.submit()">
                <?php foreach (FinancialYear::all() as $y): ?>
                    <option value="<?= (int)$y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
                        <?= (int)$y['year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Advance buckets (credits)</strong></div>
            <div class="card-body">
                <?php if (!$advances): ?>
                    <div class="text-muted small">No advance for this member/year.</div>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($advances as $a): ?>
                                <tr>
                                    <td><?= e($a['contribution_type']) ?></td>
                                    <td class="text-end"><?= number_format((float)$a['amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Outstanding lines</strong></div>
            <div class="card-body">
                <?php foreach ([['REGISTRATION', $regStructure, $regError], ['WELFARE', $welStructure, $welError]] as [$label, $s, $err]): ?>
                    <div class="mb-3">
                        <div class="text-muted small mb-1"><strong><?= $label ?></strong></div>
                        <?php if ($err): ?>
                            <div class="text-danger small"><?= e($err) ?></div>
                        <?php else: ?>
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Component</th>
                                        <th>Month</th>
                                        <th class="text-end">Required</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($s['lines'] as $l): ?>
                                        <tr>
                                            <td class="small"><?= e($l['component']) ?></td>
                                            <td class="small"><?= $l['month'] ? date('M', mktime(0, 0, 0, (int)$l['month'], 1)) : '—' ?></td>
                                            <td class="text-end small"><?= number_format($l['required'], 2) ?></td>
                                            <td class="text-end small"><?= number_format($l['paid'], 2) ?></td>
                                            <td class="text-end small <?= $l['outstanding'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                                <?= number_format($l['outstanding'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="small text-muted">
                                        <th colspan="2">Totals</th>
                                        <th class="text-end"><?= number_format($s['due_total'], 2) ?></th>
                                        <th class="text-end"><?= number_format($s['paid_total'], 2) ?></th>
                                        <th class="text-end"><?= number_format(max(0, $s['due_total'] - $s['paid_total']), 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Recent payments</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPayments as $p): ?>
                            <tr>
                                <td class="small"><a href="../payments/receipt.php?id=<?= (int)$p['id'] ?>"><?= e($p['receipt_no']) ?></a></td>
                                <td class="small"><?= e($p['payment_date']) ?></td>
                                <td class="small"><?= e($p['payment_type']) ?></td>
                                <td class="text-end small"><?= number_format((float)$p['amount'], 2) ?></td>
                                <td class="small"><span class="badge bg-<?= $p['status'] === 'ACTIVE' ? 'success' : 'danger' ?>"><?= e($p['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$recentPayments): ?>
                            <tr>
                                <td colspan="5" class="text-muted small text-center">No payments yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Recent allocation lines</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt</th>
                            <th>Component</th>
                            <th>Month</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAlloc as $a): ?>
                            <tr>
                                <td class="small"><?= e($a['receipt_no']) ?></td>
                                <td class="small"><?= e($a['component']) ?></td>
                                <td class="small"><?= $a['month'] ? date('M', mktime(0, 0, 0, (int)$a['month'], 1)) : '—' ?></td>
                                <td class="text-end small <?= $a['amount'] < 0 ? 'text-danger' : '' ?>"><?= number_format((float)$a['amount'], 2) ?></td>
                                <td class="small"><?= e($a['allocation_method']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$recentAlloc): ?>
                            <tr>
                                <td colspan="5" class="text-muted small text-center">No allocations yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="text-muted small mt-3">
    <i class="fa-solid fa-circle-info me-1"></i>
    This page is read-only. It shows exactly what the allocation engine sees when it computes a preview.
</div>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>