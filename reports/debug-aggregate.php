<?php
require __DIR__ . '/../includes/auth.php';
require_permission('report.view');

$year     = (int)($_GET['year']     ?? 0) ?: current_year();
$centerId = (int)($_GET['center_id'] ?? 0) ?: null;

$filter = [
    'year'      => $year,
    'center_id' => $centerId,
    'status'    => '',
    'q'         => '',
];

$reg = Report::memberMatrix($filter + ['type' => 'REGISTRATION']);
$wel = Report::memberMatrix($filter + ['type' => 'WELFARE']);

$agg = Report::aggregate($filter + ['group' => 'jumuiya', 'type' => 'BOTH']);

$pageTitle = 'Aggregate Debug';
require __DIR__ . '/../templates/layout/header.php';
?>
<h4>Diagnostic — Aggregate vs Matrix</h4>
<p class="text-muted small">
    Year <strong><?= (int)$year ?></strong>, Center
    <strong><?= $centerId ? (int)$centerId : 'all' ?></strong>.
    Compare the two tables: the sum of <code>totals.paid</code> in the matrix rows
    must equal the <code>reg_paid</code>/<code>wel_paid</code> in the aggregate.
</p>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Registration matrix rows</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Jumuiya</th>
                            <th class="text-end">Due</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $regPaidSum = 0;
                        foreach ($reg['rows'] as $r):
                            $regPaidSum += (float)$r['totals']['paid']; ?>
                            <tr>
                                <td class="small"><?= e($r['member']['full_name']) ?></td>
                                <td class="small"><?= e($r['member']['jumuiya_name']) ?></td>
                                <td class="text-end small"><?= number_format($r['totals']['due'], 2) ?></td>
                                <td class="text-end small fw-bold"><?= number_format($r['totals']['paid'], 2) ?></td>
                                <td class="text-end small"><?= number_format($r['totals']['balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="3" class="text-end">Matrix sum paid</th>
                            <th class="text-end"><?= number_format($regPaidSum, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Welfare matrix rows</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Jumuiya</th>
                            <th class="text-end">Due</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $welPaidSum = 0;
                        foreach ($wel['rows'] as $r):
                            $welPaidSum += (float)$r['totals']['paid']; ?>
                            <tr>
                                <td class="small"><?= e($r['member']['full_name']) ?></td>
                                <td class="small"><?= e($r['member']['jumuiya_name']) ?></td>
                                <td class="text-end small"><?= number_format($r['totals']['due'], 2) ?></td>
                                <td class="text-end small fw-bold"><?= number_format($r['totals']['paid'], 2) ?></td>
                                <td class="text-end small"><?= number_format($r['totals']['balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="3" class="text-end">Matrix sum paid</th>
                            <th class="text-end"><?= number_format($welPaidSum, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Aggregate (what the Center Report page reads)</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Group</th>
                            <th class="text-end">Members</th>
                            <th class="text-end">Reg Due</th>
                            <th class="text-end">Reg Paid</th>
                            <th class="text-end">Wel Due</th>
                            <th class="text-end">Wel Paid</th>
                            <th class="text-end">Total Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agg['rows'] as $r): ?>
                            <tr>
                                <td class="small"><?= e($r['label']) ?></td>
                                <td class="text-end small"><?= (int)$r['members'] ?></td>
                                <td class="text-end small"><?= number_format($r['reg_due'], 2) ?></td>
                                <td class="text-end small"><?= number_format($r['reg_paid'], 2) ?></td>
                                <td class="text-end small"><?= number_format($r['wel_due'], 2) ?></td>
                                <td class="text-end small"><?= number_format($r['wel_paid'], 2) ?></td>
                                <td class="text-end small fw-bold"><?= number_format($r['paid'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-3 text-muted small">
    Matrix totals printed above are <em>before</em> the aggregate grouping.
    If the aggregate shows 0 where the matrix shows non-zero, the bug is inside
    <code>Report::aggregate()</code>. If the matrix itself shows 0, the bug is in
    <code>Report::memberMatrix()</code> or in the filter being passed.
</div>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>