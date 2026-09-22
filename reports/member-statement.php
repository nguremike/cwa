<?php
require __DIR__ . '/../includes/auth.php';
require_permission('report.view');

$memberId = (int)($_GET['id'] ?? 0);
$year     = (int)($_GET['year'] ?? 0) ?: current_year();

$years = FinancialYear::all();
require __DIR__ . '/../templates/layout/footer.php';


// If no member chosen, show a chooser.
if ($memberId <= 0) {
    $centers = Db::all("SELECT id, name FROM centers ORDER BY name");
    $pageTitle = 'Member Statement';
    require __DIR__ . '/../templates/layout/header.php';

?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fa-solid fa-file-invoice me-2"></i>Member Statement</h4>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="alert alert-light border small mb-3">
                Choose a center and jumuiya, then a member, to view their annual statement.
            </div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small">Center</label>
                    <select id="center_id" class="form-select">
                        <option value="">— select —</option>
                        <?php foreach ($centers as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Jumuiya</label>
                    <select id="jumuiya_id" class="form-select">
                        <option value="">— select center first —</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Member</label>
                    <select id="member_pick" class="form-select">
                        <option value="">— select jumuiya first —</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="openBtn" class="btn btn-primary w-100" disabled>
                        <i class="fa-solid fa-arrow-right me-1"></i> Open
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function() {
            function loadJum() {
                const cid = $('#center_id').val();
                const $j = $('#jumuiya_id').empty().append('<option value="">— select —</option>');
                const $m = $('#member_pick').empty().append('<option value="">— select jumuiya first —</option>');
                $('#openBtn').prop('disabled', true);
                if (!cid) return;
                api('/api/jumuiyas.php', {
                    center_id: cid
                }).done(res => {
                    (res.data || []).forEach(x => $j.append(`<option value="${x.id}">${x.name}</option>`));
                });
            }

            function loadMembers() {
                const jid = $('#jumuiya_id').val();
                const $m = $('#member_pick').empty().append('<option value="">— loading… —</option>');
                $('#openBtn').prop('disabled', true);
                if (!jid) {
                    $m.empty().append('<option value="">— select jumuiya first —</option>');
                    return;
                }
                $.getJSON(CWA.base + '/api/members.php', {
                    jumuiya_id: jid
                }).done(res => {
                    $m.empty().append('<option value="">— select —</option>');
                    (res.data || []).forEach(x =>
                        $m.append(`<option value="${x.id}">${x.member_code} · ${x.full_name}</option>`));
                });
            }
            $('#center_id').on('change', loadJum);
            $('#jumuiya_id').on('change', loadMembers);
            $('#member_pick').on('change', function() {
                $('#openBtn').prop('disabled', !this.value);
            });
            $('#openBtn').on('click', function() {
                const id = $('#member_pick').val();
                if (id) location.href = 'member-statement.php?id=' + id;
            });
        });
    </script>
<?php
    require __DIR__ . '/../templates/layout/footer.php';
    exit;
}

// From here on, we have a member.
try {
    $st = Report::memberStatement($memberId, $year);
} catch (Throwable $e) {
    http_response_code(404);
    exit('Statement error: ' . $e->getMessage());
}

$m = $st['member'];
$reg = $st['registration'];
$wel = $st['welfare'];
$welMeta = $st['welfare_meta'];

$pageTitle = 'Statement · ' . $m['full_name'];
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0">
        <i class="fa-solid fa-file-invoice me-2"></i>Member Statement
    </h4>
    <div class="d-flex gap-2">
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="id" value="<?= $memberId ?>">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?= (int)$y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
                        <?= (int)$y['year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="export-member-statement.php?id=<?= $memberId ?>&year=<?= $year ?>"
            class="btn btn-outline-success btn-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Excel
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-print me-1"></i> Print
        </button>
        <a href="../members/view.php?id=<?= $memberId ?>" class="btn btn-outline-secondary btn-sm">Profile</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">

        <!-- Print header -->
        <div class="print-header mb-3">
            <h5 class="mb-0">Catholic Women Association</h5>
            <div class="small text-muted">
                Member Statement · <?= (int)$year ?> · Generated <?= date('Y-m-d H:i') ?>
            </div>
        </div>

        <!-- Identity -->
        <div class="row small mb-3">
            <div class="col-md-6">
                <div class="text-muted">Member</div>
                <div class="h5 mb-1"><?= e($m['full_name']) ?></div>
                <div>
                    <span class="badge bg-light text-dark border"><?= e($m['member_code']) ?></span>
                    <span class="badge bg-<?= $m['status'] === 'ACTIVE' ? 'success' : 'secondary' ?> ms-1"><?= e($m['status']) ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Phone</div>
                <div><?= e($m['phone']) ?></div>
                <div class="text-muted mt-2">Join date</div>
                <div><?= e($m['join_date']) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Jumuiya</div>
                <div><?= e($m['jumuiya_name']) ?></div>
                <div class="text-muted mt-2">Center</div>
                <div><?= e($m['center_name']) ?></div>
            </div>
        </div>

        <hr>

        <!-- Registration block -->
        <h6 class="mb-2">
            <i class="fa-solid fa-id-card me-1"></i> Registration — <?= (int)$year ?>
        </h6>
        <?php if ($reg): ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle statement-table">
                    <thead class="table-light">
                        <tr>
                            <th>Line</th>
                            <th class="text-end">Due</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Renewal</td>
                            <td class="text-end"><?= number_format($reg['due']['renewal'], 2) ?></td>
                            <td class="text-end"><?= number_format($reg['paid']['renewal'], 2) ?></td>
                            <td class="text-end <?= $reg['balance']['renewal'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                <?= number_format($reg['balance']['renewal'], 2) ?>
                            </td>
                        </tr>
                        <?php if ($reg['due']['card'] > 0): ?>
                            <tr>
                                <td>Card fee</td>
                                <td class="text-end"><?= number_format($reg['due']['card'], 2) ?></td>
                                <td class="text-end"><?= number_format($reg['paid']['card'], 2) ?></td>
                                <td class="text-end <?= $reg['balance']['card'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                    <?= number_format($reg['balance']['card'], 2) ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php for ($mo = 1; $mo <= 12; $mo++):
                            $dueM = $reg['due']['months'][$mo];
                            if ($dueM == 0) continue;
                        ?>
                            <tr>
                                <td><?= date('F', mktime(0, 0, 0, $mo, 1)) ?></td>
                                <td class="text-end"><?= number_format($dueM, 2) ?></td>
                                <td class="text-end"><?= number_format($reg['paid']['months'][$mo], 2) ?></td>
                                <td class="text-end <?= $reg['balance']['months'][$mo] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                    <?= number_format($reg['balance']['months'][$mo], 2) ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th>Registration totals</th>
                            <th class="text-end"><?= number_format($reg['totals']['due'], 2) ?></th>
                            <th class="text-end"><?= number_format($reg['totals']['paid'], 2) ?></th>
                            <th class="text-end <?= $reg['totals']['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($reg['totals']['balance'], 2) ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-light border small">No registration obligation recorded for <?= (int)$year ?>.</div>
        <?php endif; ?>

        <hr>

        <!-- Welfare block -->
        <h6 class="mb-2">
            <i class="fa-solid fa-hand-holding-heart me-1"></i> Welfare — <?= (int)$year ?>
        </h6>
        <?php if ($welMeta && $welMeta['status'] === 'NOT_APPLICABLE'): ?>
            <div class="alert alert-light border small mb-0">
                Welfare is not enabled for this member's center.
            </div>
        <?php elseif ($wel): ?>
            <div class="small text-muted mb-2">
                Welfare starts from
                <strong><?= date('F', mktime(0, 0, 0, (int)$welMeta['start_month'], 1)) ?></strong>.
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle statement-table">
                    <thead class="table-light">
                        <tr>
                            <th>Line</th>
                            <th class="text-end">Due</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($mo = 1; $mo <= 12; $mo++):
                            $dueM = $wel['due']['months'][$mo];
                            if ($dueM == 0) continue;
                        ?>
                            <tr>
                                <td><?= date('F', mktime(0, 0, 0, $mo, 1)) ?></td>
                                <td class="text-end"><?= number_format($dueM, 2) ?></td>
                                <td class="text-end"><?= number_format($wel['paid']['months'][$mo], 2) ?></td>
                                <td class="text-end <?= $wel['balance']['months'][$mo] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                    <?= number_format($wel['balance']['months'][$mo], 2) ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th>Welfare totals</th>
                            <th class="text-end"><?= number_format($wel['totals']['due'], 2) ?></th>
                            <th class="text-end"><?= number_format($wel['totals']['paid'], 2) ?></th>
                            <th class="text-end <?= $wel['totals']['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($wel['totals']['balance'], 2) ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-light border small mb-0">No welfare obligation recorded for <?= (int)$year ?>.</div>
        <?php endif; ?>

        <hr>

        <!-- Combined summary -->
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <div class="text-muted small">Total due</div>
                        <div class="h4 mb-0"><?= number_format($st['totals']['due'], 2) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <div class="text-muted small">Total paid</div>
                        <div class="h4 mb-0 text-success"><?= number_format($st['totals']['paid'], 2) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <div class="text-muted small">Balance</div>
                        <div class="h4 mb-0 <?= $st['totals']['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($st['totals']['balance'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .statement-table th,
    .statement-table td {
        white-space: nowrap;
    }

    @media print {
        .statement-table {
            font-size: 11px;
        }
    }
</style>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>