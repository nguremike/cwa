<?php
require __DIR__ . '/../includes/auth.php';
require_permission('report.view');

$centers = Db::all("SELECT id, name FROM centers WHERE welfare_enabled = 1 ORDER BY name");
$years   = FinancialYear::all();

$f = [
    'center_id'  => (int)($_GET['center_id']  ?? 0) ?: null,
    'jumuiya_id' => (int)($_GET['jumuiya_id'] ?? 0) ?: null,
    'year'       => (int)($_GET['year']       ?? 0) ?: current_year(),
    'status'     => trim($_GET['status']      ?? ''),
    'q'          => trim($_GET['q']           ?? ''),
];

$report = Report::memberMatrix($f + ['type' => 'WELFARE']);

$pageTitle = 'Welfare Matrix';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0"><i class="fa-solid fa-hand-holding-heart me-2"></i>Welfare Matrix</h4>
    <div>
        <a href="export-welfare-matrix.php?<?= http_build_query($_GET) ?>"
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
        <div class="col-md-3">
            <label class="form-label small">Center</label>
            <select name="center_id" id="center_id" class="form-select">
                <option value="">All centers</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $f['center_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Jumuiya</label>
            <select name="jumuiya_id" id="jumuiya_id" class="form-select">
                <option value="">All jumuiyas</option>
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small">Year</label>
            <select name="year" class="form-select">
                <?php foreach ($years as $y): ?>
                    <option value="<?= (int)$y['year'] ?>" <?= $f['year'] == $y['year'] ? 'selected' : '' ?>>
                        <?= (int)$y['year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
                <option value="">Any</option>
                <?php foreach (['ACTIVE', 'INACTIVE', 'TRANSFERRED', 'DECEASED', 'LEFT', 'SUSPENDED'] as $s): ?>
                    <option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Search</label>
            <input name="q" class="form-control" value="<?= e($f['q']) ?>" placeholder="Name, phone, code">
        </div>
        <!-- INSERT filter button -->
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter me-1"></i> Filter
            </button>
        </div>


    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Members on welfare</div>
                <div class="h5 mb-0"><?= (int)$report['members_count'] ?></div>
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
        <?php if (!$report['rows']): ?>
            <div class="alert alert-info mb-0">No welfare-liable members match those filters.</div>
        <?php else: ?>
            <div class="table-responsive matrix-wrap">
                <table class="table table-sm table-bordered align-middle matrix-table">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="sticky-col">Member</th>
                            <th rowspan="2" class="text-center">Start</th>
                            <th colspan="12" class="text-center">Welfare — Monthly</th>
                            <th rowspan="2" class="text-end">Paid</th>
                            <th rowspan="2" class="text-end">Balance</th>
                        </tr>
                        <tr>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <th class="text-center small"><?= date('M', mktime(0, 0, 0, $m, 1)) ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['rows'] as $r):
                            $m = $r['member'];
                            // start month comes from member_welfare; recompute cheaply here
                            $startRow = Db::one(
                                "SELECT start_month FROM member_welfare WHERE member_id = :m AND year = :y",
                                ['m' => (int)$m['id'], 'y' => (int)$report['year']]
                            );
                            $start = $startRow ? (int)$startRow['start_month'] : 1;
                        ?>
                            <tr>
                                <td class="sticky-col">
                                    <div><strong><?= e($m['full_name']) ?></strong></div>
                                    <div class="text-muted small">
                                        <?= e($m['member_code']) ?> · <?= e($m['jumuiya_name']) ?>
                                    </div>
                                </td>
                                <td class="text-center small text-muted"><?= date('M', mktime(0, 0, 0, $start, 1)) ?></td>
                                <?php for ($mo = 1; $mo <= 12; $mo++):
                                    $dueM = $r['due']['months'][$mo];
                                    $payM = $r['paid']['months'][$mo];
                                    $balM = $r['balance']['months'][$mo];
                                ?>
                                    <td class="text-center small <?= $dueM == 0 ? 'text-muted' : ($balM > 0 ? 'cell-balance' : 'cell-paid') ?>">
                                        <?php if ($dueM == 0): ?>
                                            <span class="text-muted">·</span>
                                        <?php elseif ($balM == 0): ?>
                                            <?= number_format($payM, 0) ?>
                                        <?php else: ?>
                                            <?= number_format($payM, 0) ?><span class="text-muted">/</span><?= number_format($dueM, 0) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="text-end fw-bold"><?= number_format($r['totals']['paid'], 2) ?></td>
                                <td class="text-end fw-bold <?= $r['totals']['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($r['totals']['balance'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th class="sticky-col">Totals</th>
                            <th></th>
                            <?php for ($mo = 1; $mo <= 12; $mo++): ?><th></th><?php endfor; ?>
                            <th class="text-end"><?= number_format($report['totals']['paid'], 2) ?></th>
                            <th class="text-end text-danger"><?= number_format($report['totals']['balance'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="text-muted small mt-2">
                Cells show <code>paid / due</code> when outstanding, or <code>paid</code> when settled.
                A dot (<code>·</code>) means welfare was not due that month (before the member’s start month).
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .matrix-wrap {
        overflow-x: auto;
    }

    .matrix-table th,
    .matrix-table td {
        white-space: nowrap;
    }

    .sticky-col {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 2;
        min-width: 220px;
        border-right: 1px solid #dee2e6;
    }

    .matrix-table thead th.sticky-col {
        z-index: 3;
    }

    .cell-paid {
        background: #eaf7ee;
    }

    .cell-balance {
        background: #fdeaea;
    }

    @media print {

        .no-print,
        nav,
        .navbar,
        footer {
            display: none !important;
        }

        .matrix-wrap {
            overflow: visible;
        }

        .matrix-table {
            font-size: 11px;
        }

        .sticky-col {
            position: static;
        }
    }
</style>

<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        const preJum = <?= json_encode($f['jumuiya_id'] ?? '') ?>;

        function loadJum() {
            const cid = $('#center_id').val();
            const $j = $('#jumuiya_id').empty().append('<option value="">All jumuiyas</option>');
            if (!cid) return;
            api('/api/jumuiyas.php', {
                center_id: cid
            }).done(res => {
                (res.data || []).forEach(j => {
                    const sel = (preJum == j.id) ? ' selected' : '';
                    $j.append(`<option value="${j.id}"${sel}>${j.name}</option>`);
                });
            });
        }
        $('#center_id').on('change', loadJum);
        loadJum();
    });
</script>