<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.view');

$years = FinancialYear::all();

$selectedYear = (int)($_GET['year'] ?? 0);
if ($selectedYear === 0) {
    $cur = FinancialYear::current();
    $selectedYear = $cur ? (int)$cur['year'] : (int)($years[0]['year'] ?? date('Y'));
}

$types = Db::all("SELECT DISTINCT contribution_type FROM contribution_schedules ORDER BY contribution_type");
if (!$types) {
    $types = [
        ['contribution_type' => 'REGISTRATION'],
        ['contribution_type' => 'WELFARE'],
    ];
}

$rows = Db::all(
    "SELECT contribution_type, component, month, required_amount
       FROM contribution_schedules
      WHERE year = :y
      ORDER BY contribution_type, component, month",
    ['y' => $selectedYear]
);

// Group by type for display
$grouped = [];
foreach ($rows as $r) {
    $grouped[$r['contribution_type']][$r['component']][(int)$r['month']] = (float)$r['required_amount'];
}

$pageTitle = 'Contribution Schedules';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-table-list me-2"></i>Contribution Schedules</h4>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-3">
        <label class="form-label small mb-1">Financial Year</label>
        <select name="year" class="form-select" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['year'] ?>" <?= $selectedYear == $y['year'] ? 'selected' : '' ?>>
                    <?= (int)$y['year'] ?> (<?= e($y['status']) ?><?= $y['is_current'] ? ' · current' : '' ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (!$rows): ?>
    <div class="alert alert-info">
        No schedules exist for <strong><?= (int)$selectedYear ?></strong>.
        <?php if (($fy = FinancialYear::byYear($selectedYear)) && $fy['status'] === 'OPEN'): ?>
            <a href="edit.php?year=<?= (int)$selectedYear ?>&type=REGISTRATION" class="alert-link">Create registration schedule</a>
            or
            <a href="edit.php?year=<?= (int)$selectedYear ?>&type=WELFARE" class="alert-link">create welfare schedule</a>.
        <?php endif; ?>
    </div>
<?php else: ?>

    <?php foreach ($grouped as $type => $components): ?>
        <?php
        $state = schedule_edit_state($selectedYear, $type);
        ?>
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= e($type) ?></strong>
                    <span class="text-muted small ms-2"><?= e($state['reason']) ?></span>
                </div>
                <div>
                    <?php if ($state['editable']): ?>
                        <a href="edit.php?year=<?= (int)$selectedYear ?>&type=<?= urlencode($type) ?>"
                            class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-pen me-1"></i> Edit
                        </a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="fa-solid fa-lock me-1"></i> Locked
                        </button>
                        <a href="unlock.php?year=<?= (int)$selectedYear ?>&type=<?= urlencode($type) ?>"
                            class="btn btn-sm btn-outline-warning ms-1">
                            <i class="fa-solid fa-unlock me-1"></i> Unlock
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php foreach ($components as $component => $months): ?>
                    <div class="mb-3">
                        <div class="text-muted small mb-2"><strong><?= e($component) ?></strong></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" style="min-width:900px">
                                <thead class="table-light text-center">
                                    <tr>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <th class="small"><?= date('M', mktime(0, 0, 0, $m, 1)) ?></th>
                                        <?php endfor; ?>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="text-center">
                                        <?php $total = 0;
                                        for ($m = 1; $m <= 12; $m++):
                                            $v = $months[$m] ?? 0;
                                            $total += $v; ?>
                                            <td><?= $v > 0 ? number_format($v, 0) : '<span class="text-muted">—</span>' ?></td>
                                        <?php endfor; ?>
                                        <td class="fw-bold"><?= number_format($total, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>