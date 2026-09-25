<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.view');

$years = FinancialYear::all();

$selectedYear = (int)($_GET['year'] ?? 0);
if ($selectedYear === 0) {
    $cur = FinancialYear::current();
    $selectedYear = $cur ? (int)$cur['year'] : (int)($years[0]['year'] ?? date('Y'));
}

// All known contribution types we expect schedules for.
// Registry-driven so adding a third type later is a one-line change here.
$knownTypes = [];
foreach (Db::all("SELECT code FROM contribution_types ORDER BY code") as $t) {
    $code = strtoupper((string)$t['code']);
    if (in_array($code, ['REGISTRATION', 'WELFARE'], true)) $knownTypes[] = $code;
}
if (!$knownTypes) {
    // Fallback: the two types we ship with
    $knownTypes = ['REGISTRATION', 'WELFARE'];
}

// Load all rows for the selected year, grouped by type -> component -> month
$rows = Db::all(
    "SELECT contribution_type, component, month, required_amount
       FROM contribution_schedules
      WHERE year = :y
      ORDER BY contribution_type, component, month",
    ['y' => $selectedYear]
);

$grouped = [];
foreach ($rows as $r) {
    $type = strtoupper($r['contribution_type']);
    $grouped[$type][$r['component']][(int)$r['month']] = (float)$r['required_amount'];
}

$yearRow  = FinancialYear::byYear($selectedYear);
$yearOpen = $yearRow && $yearRow['status'] === 'OPEN';

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

<?php if (!$yearOpen): ?>
    <div class="alert alert-warning">
        <i class="fa-solid fa-lock me-1"></i>
        This financial year is <strong><?= e($yearRow['status'] ?? 'missing') ?></strong>.
        Schedules cannot be edited until it is reopened.
        <a href="../financial-years/index.php" class="alert-link ms-2">Manage financial years</a>
    </div>
<?php endif; ?>

<?php foreach ($knownTypes as $type):
    $hasRows = !empty($grouped[$type]);
    $state   = schedule_edit_state($selectedYear, $type);
?>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <strong><?= e($type) ?></strong>
                <span class="text-muted small ms-2">
                    <?= $hasRows ? e($state['reason']) : 'No schedule yet for this year.' ?>
                </span>
            </div>
            <div>
                <?php if (!$hasRows): ?>
                    <?php if ($yearOpen): ?>
                        <a href="edit.php?year=<?= (int)$selectedYear ?>&type=<?= urlencode($type) ?>"
                            class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-plus me-1"></i> Create <?= e(strtolower($type)) ?> schedule
                        </a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="fa-solid fa-lock me-1"></i> Locked
                        </button>
                    <?php endif; ?>
                <?php elseif ($state['editable']): ?>
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

        <?php if ($hasRows): ?>
            <div class="card-body">
                <?php foreach ($grouped[$type] as $component => $months): ?>
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
        <?php else: ?>
            <div class="card-body">
                <div class="text-muted small">
                    No <strong><?= e(strtolower($type)) ?></strong> schedule exists for
                    <strong><?= (int)$selectedYear ?></strong>.
                    <?php if ($yearOpen): ?>
                        Members registered into this year will have zero obligation for
                        <?= e(strtolower($type)) ?> until a schedule is created.
                        Use the button above to add one.
                    <?php else: ?>
                        Reopen the financial year to add one.
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>