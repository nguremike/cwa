<?php
require __DIR__ . '/../includes/auth.php';
require_permission('report.view');

$centers = Db::all("SELECT id, name FROM centers ORDER BY name");
$years   = FinancialYear::all();

$f = [
    'center_id'  => (int)($_GET['center_id'] ?? 0) ?: null,
    'year'       => (int)($_GET['year']      ?? 0) ?: current_year(),
    'status'     => trim($_GET['status']     ?? ''),
];

if (!isset($_GET['year']) || (int)$_GET['year'] <= 0) {
    // No year supplied: use current_year() but force it into the query string
    // so click-throughs and reloads keep the same year.
    $f['year'] = current_year();
} else {
    $f['year'] = (int)$_GET['year'];
}


$report = Report::aggregate($f + ['group' => 'jumuiya', 'type' => 'BOTH']);

$selectedCenterName = '';
foreach ($centers as $c) if ($f['center_id'] == $c['id']) $selectedCenterName = $c['name'];

$pageTitle = 'Center Report';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0">
        <i class="fa-solid fa-diagram-project me-2"></i>Center Report
        <?php if ($selectedCenterName): ?>
            <span class="text-muted small ms-2"><?= e($selectedCenterName) ?></span>
        <?php endif; ?>
    </h4>
    <div>
        <a href="export-center-report.php?<?= http_build_query($_GET) ?>"
            class="btn btn-outline-success btn-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Excel
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-print me-1"></i> Print
        </button>
    </div>
</div>

<form class="card shadow-sm border-0 mb-3 no-print" method="get">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small">Center</label>
            <select name="center_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $f['center_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Year</label>
            <select name="year" class="form-select" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?= (int)$y['year'] ?>" <?= $f['year'] == $y['year'] ? 'selected' : '' ?>>
                        <?= (int)$y['year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <?php foreach (['ACTIVE', 'INACTIVE', 'TRANSFERRED', 'DECEASED', 'LEFT', 'SUSPENDED'] as $s): ?>
                    <option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 text-end">
            <button class="btn btn-primary w-100">
                <i class="fa-solid fa-filter me-1"></i> Apply
            </button>
        </div>
    </div>
</form>

<?php if (!$f['center_id']): ?>
    <div class="alert alert-info">Select a center to see its jumuiya breakdown.</div>
<?php else: ?>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Members</div>
                    <div class="h5 mb-0"><?= (int)$report['totals']['members'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Expected</div>
                    <div class="h5 mb-0"><?= number_format($report['totals']['due'], 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Collected</div>
                    <div class="h5 mb-0 text-success"><?= number_format($report['totals']['paid'], 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Balance</div>
                    <div class="h5 mb-0 text-danger"><?= number_format($report['totals']['balance'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="print-header mb-2">
                <h5 class="mb-0">Catholic Women Association</h5>
                <div class="small text-muted">
                    Center Report · <?= e($selectedCenterName) ?> · <?= (int)$report['year'] ?> ·
                    Generated <?= date('Y-m-d H:i') ?>
                </div>
            </div>

            <?php if (!$report['rows']): ?>
                <div class="alert alert-info mb-0">No jumuiya data for this center in the selected year.</div>
            <?php else: ?>
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Jumuiya</th>
                            <th class="text-end">Members</th>
                            <th class="text-end">Reg. Due</th>
                            <th class="text-end">Reg. Paid</th>
                            <th class="text-end">Reg. Bal.</th>
                            <th class="text-end">Wel. Due</th>
                            <th class="text-end">Wel. Paid</th>
                            <th class="text-end">Wel. Bal.</th>
                            <th class="text-end">Total Due</th>
                            <th class="text-end">Total Paid</th>
                            <th class="text-end">Total Bal.</th>
                            <th class="no-print"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['rows'] as $r): ?>
                            <tr>
                                <td><?= e($r['label']) ?></td>
                                <td class="text-end"><?= (int)$r['members'] ?></td>
                                <td class="text-end"><?= number_format($r['reg_due'], 2) ?></td>
                                <td class="text-end"><?= number_format($r['reg_paid'], 2) ?></td>
                                <td class="text-end <?= $r['reg_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($r['reg_balance'], 2) ?>
                                </td>
                                <td class="text-end"><?= number_format($r['wel_due'], 2) ?></td>
                                <td class="text-end"><?= number_format($r['wel_paid'], 2) ?></td>
                                <td class="text-end <?= $r['wel_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($r['wel_balance'], 2) ?>
                                </td>
                                <td class="text-end fw-bold"><?= number_format($r['due'], 2) ?></td>
                                <td class="text-end fw-bold text-success"><?= number_format($r['paid'], 2) ?></td>
                                <td class="text-end fw-bold <?= $r['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($r['balance'], 2) ?>
                                </td>
                                <td class="text-end no-print">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="registration-matrix.php?center_id=<?= (int)$f['center_id'] ?>&year=<?= (int)$report['year'] ?>">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th>Totals</th>
                            <th class="text-end"><?= (int)$report['totals']['members'] ?></th>
                            <th class="text-end"><?= number_format($report['totals']['reg_due'], 2) ?></th>
                            <th class="text-end"><?= number_format($report['totals']['reg_paid'], 2) ?></th>
                            <th class="text-end text-danger"><?= number_format($report['totals']['reg_balance'], 2) ?></th>
                            <th class="text-end"><?= number_format($report['totals']['wel_due'], 2) ?></th>
                            <th class="text-end"><?= number_format($report['totals']['wel_paid'], 2) ?></th>
                            <th class="text-end text-danger"><?= number_format($report['totals']['wel_balance'], 2) ?></th>
                            <th class="text-end"><?= number_format($report['totals']['due'], 2) ?></th>
                            <th class="text-end text-success"><?= number_format($report['totals']['paid'], 2) ?></th>
                            <th class="text-end text-danger"><?= number_format($report['totals']['balance'], 2) ?></th>
                            <th class="no-print"></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>