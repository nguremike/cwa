<?php
require __DIR__ . '/../includes/auth.php';
require_permission('dashboard.view');

$years = FinancialYear::all();
$year  = (int)($_GET['year'] ?? 0) ?: current_year();

$data = Report::dashboard($year);
$canSeeReports = user_can('report.view') || user_can('*');

$pageTitle = 'Dashboard';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0">
        <i class="fa-solid fa-gauge-high me-2"></i>Dashboard
        <span class="text-muted small ms-2"><?= (int)$year ?></span>
    </h4>
    <form method="get" class="d-flex gap-2">
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
                    <?= (int)$y['year'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Print header -->
<div class="print-header mb-3">
    <h5 class="mb-0">Catholic Women Association</h5>
    <div class="small text-muted">
        Dashboard · <?= (int)$year ?> · Generated <?= date('Y-m-d H:i') ?>
    </div>
</div>

<!-- ---- KPI strip ---- -->
<div class="row g-3 mb-3">
    <?php
    $kpis = [
        ['label' => 'Members',           'value' => $data['counts']['members'],        'icon' => 'fa-users',              'color' => 'primary'],
        ['label' => 'Active members',    'value' => $data['counts']['active_members'], 'icon' => 'fa-user-check',         'color' => 'success'],
        ['label' => 'New this year',     'value' => $data['counts']['new_members'],    'icon' => 'fa-user-plus',          'color' => 'info'],
        ['label' => 'Centers',           'value' => $data['counts']['centers'],        'icon' => 'fa-diagram-project',    'color' => 'secondary'],
        ['label' => 'Jumuiyas',          'value' => $data['counts']['jumuiyas'],       'icon' => 'fa-people-group',       'color' => 'secondary'],
        ['label' => 'Users',             'value' => $data['counts']['users'],          'icon' => 'fa-user-shield',        'color' => 'dark'],
    ];
    foreach ($kpis as $k):
    ?>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small"><?= e($k['label']) ?></div>
                            <div class="h4 mb-0"><?= number_format($k['value']) ?></div>
                        </div>
                        <i class="fa-solid <?= e($k['icon']) ?> fa-2x text-<?= e($k['color']) ?> opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ---- Registration + Welfare KPIs ---- -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <strong><i class="fa-solid fa-id-card me-2"></i>Registration <?= (int)$year ?></strong>
                    <span class="badge bg-<?= $data['registration']['pct'] >= 80 ? 'success' : ($data['registration']['pct'] >= 50 ? 'warning' : 'danger') ?>">
                        <?= number_format($data['registration']['pct'], 1) ?>%
                    </span>
                </div>
                <div class="row text-center">
                    <div class="col">
                        <div class="text-muted small">Expected</div>
                        <div class="h5 mb-0"><?= number_format($data['registration']['due'], 0) ?></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Collected</div>
                        <div class="h5 mb-0 text-success"><?= number_format($data['registration']['paid'], 0) ?></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Balance</div>
                        <div class="h5 mb-0 text-danger"><?= number_format($data['registration']['balance'], 0) ?></div>
                    </div>
                </div>
                <div class="progress mt-3" style="height:6px">
                    <div class="progress-bar bg-success" style="width:<?= max(0, min(100, $data['registration']['pct'])) ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <strong><i class="fa-solid fa-hand-holding-heart me-2"></i>Welfare <?= (int)$year ?></strong>
                    <span class="badge bg-<?= $data['welfare']['pct'] >= 80 ? 'success' : ($data['welfare']['pct'] >= 50 ? 'warning' : 'danger') ?>">
                        <?= number_format($data['welfare']['pct'], 1) ?>%
                    </span>
                </div>
                <div class="row text-center">
                    <div class="col">
                        <div class="text-muted small">Expected</div>
                        <div class="h5 mb-0"><?= number_format($data['welfare']['due'], 0) ?></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Collected</div>
                        <div class="h5 mb-0 text-success"><?= number_format($data['welfare']['paid'], 0) ?></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Balance</div>
                        <div class="h5 mb-0 text-danger"><?= number_format($data['welfare']['balance'], 0) ?></div>
                    </div>
                </div>
                <div class="progress mt-3" style="height:6px">
                    <div class="progress-bar bg-info" style="width:<?= max(0, min(100, $data['welfare']['pct'])) ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ---- Arrears strip ---- -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Members in registration arrears</div>
                <div class="h5 mb-0 text-danger"><?= (int)$data['arrears']['reg_members'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Members in welfare arrears</div>
                <div class="h5 mb-0 text-danger"><?= (int)$data['arrears']['wel_members'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total outstanding</div>
                <div class="h5 mb-0 text-danger"><?= number_format($data['arrears']['total_balance'], 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Fully paid members</div>
                <div class="h5 mb-0 text-success"><?= (int)$data['collection_status']['fully'] ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ---- Charts row 1 ---- -->
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <strong><i class="fa-solid fa-chart-line me-2"></i>Monthly Collections — <?= (int)$year ?></strong>
                <div style="height:280px" class="mt-3">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <strong><i class="fa-solid fa-chart-pie me-2"></i>Registration Collection Status</strong>
                <div style="height:280px" class="mt-3">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ---- Charts row 2 ---- -->
<div class="row g-3 mb-3">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <strong><i class="fa-solid fa-ranking-star me-2"></i>Top Jumuiyas by Collection</strong>
                <div style="height:340px" class="mt-3">
                    <canvas id="chartJumuiya"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <strong><i class="fa-solid fa-chart-bar me-2"></i>Active Members by Center</strong>
                <div style="height:340px" class="mt-3">
                    <canvas id="chartCenters"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canSeeReports): ?>
    <!-- ---- Recent activity ---- -->
    <div class="row g-3">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Recent payments</strong>
                    <a href="../payments/" class="small">View all</a>
                </div>
                <div class="card-body p-0">
                    <?php
                    $recentPay = Db::all(
                        "SELECT p.id, p.receipt_no, p.payment_date, p.amount, p.payment_type, p.status,
                    m.full_name, m.member_code
               FROM payments p
               JOIN members m ON m.id = p.member_id
              ORDER BY p.id DESC
              LIMIT 8"
                    );
                    ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php if (!$recentPay): ?>
                                <tr>
                                    <td class="text-muted small text-center">No payments yet.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($recentPay as $p): ?>
                                <tr>
                                    <td class="small">
                                        <a href="../payments/receipt.php?id=<?= (int)$p['id'] ?>">
                                            <?= e($p['receipt_no']) ?>
                                        </a>
                                    </td>
                                    <td class="small"><?= e($p['full_name']) ?></td>
                                    <td class="small text-muted"><?= e($p['payment_type']) ?></td>
                                    <td class="text-end small"><?= number_format((float)$p['amount'], 2) ?></td>
                                    <td class="text-end small">
                                        <span class="badge bg-<?= $p['status'] === 'ACTIVE' ? 'success' : 'danger' ?>">
                                            <?= e($p['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Recent members</strong>
                    <a href="../members/" class="small">View all</a>
                </div>
                <div class="card-body p-0">
                    <?php
                    $recentM = Db::all(
                        "SELECT m.id, m.member_code, m.full_name, m.join_date, m.status,
                    j.name AS jumuiya_name
               FROM members m
               JOIN jumuiyas j ON j.id = m.jumuiya_id
              ORDER BY m.id DESC
              LIMIT 8"
                    );
                    ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php if (!$recentM): ?>
                                <tr>
                                    <td class="text-muted small text-center">No members yet.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($recentM as $m): ?>
                                <tr>
                                    <td class="small">
                                        <a href="../members/view.php?id=<?= (int)$m['id'] ?>">
                                            <?= e($m['full_name']) ?>
                                        </a>
                                    </td>
                                    <td class="small text-muted"><?= e($m['jumuiya_name']) ?></td>
                                    <td class="small text-muted"><?= e($m['join_date']) ?></td>
                                    <td class="text-end small">
                                        <span class="badge bg-<?= $m['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                                            <?= e($m['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function() {
        const monthly = <?= json_encode(array_values($data['monthly'])) ?>;
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // ---- Monthly collections line chart ----
        new Chart(document.getElementById('chartMonthly'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                        label: 'Registration',
                        data: monthly.map(m => m.reg),
                        borderColor: 'rgb(25,135,84)',
                        backgroundColor: 'rgba(25,135,84,0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Welfare',
                        data: monthly.map(m => m.wel),
                        borderColor: 'rgb(13,202,240)',
                        backgroundColor: 'rgba(13,202,240,0.1)',
                        tension: 0.3,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${Number(ctx.parsed.y).toLocaleString()}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => Number(v).toLocaleString()
                        }
                    }
                }
            }
        });

        // ---- Collection status doughnut ----
        const cs = <?= json_encode($data['collection_status']) ?>;
        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: ['Fully paid', 'Partial', 'Arrears', 'Not started'],
                datasets: [{
                    data: [cs.fully, cs.partial, cs.arrears, cs.not_started],
                    backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545'],
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // ---- Jumuiya comparison horizontal bar ----
        const jc = <?= json_encode($data['jumuiya_comparison']) ?>;
        new Chart(document.getElementById('chartJumuiya'), {
            type: 'bar',
            data: {
                labels: jc.map(r => r.label),
                datasets: [{
                        label: 'Expected',
                        data: jc.map(r => r.due),
                        backgroundColor: 'rgba(108,117,125,0.4)',
                    },
                    {
                        label: 'Collected',
                        data: jc.map(r => r.paid),
                        backgroundColor: 'rgba(25,135,84,0.75)',
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${Number(ctx.parsed.x).toLocaleString()}`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => Number(v).toLocaleString()
                        }
                    }
                }
            }
        });

        // ---- Members by center vertical bar ----
        const mb = <?= json_encode($data['members_by_center']) ?>;
        new Chart(document.getElementById('chartCenters'), {
            type: 'bar',
            data: {
                labels: mb.map(r => r.label),
                datasets: [{
                    label: 'Active members',
                    data: mb.map(r => r.count),
                    backgroundColor: 'rgba(13,110,253,0.7)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    })();
</script>

<style>
    @media print {
        canvas {
            max-height: 240px !important;
        }

        .card {
            break-inside: avoid;
        }
    }
</style>