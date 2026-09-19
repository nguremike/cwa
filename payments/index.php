<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.view');

$centers = Db::all("SELECT id, name FROM centers ORDER BY name");
$years   = FinancialYear::all();

$f = [
    'center_id'  => (int)($_GET['center_id']  ?? 0) ?: null,
    'jumuiya_id' => (int)($_GET['jumuiya_id'] ?? 0) ?: null,
    'year'       => (int)($_GET['year']       ?? 0) ?: null,
    'type'       => trim($_GET['type']        ?? ''),
    'status'     => trim($_GET['status']      ?? ''),
    'from'       => trim($_GET['from']        ?? ''),
    'to'         => trim($_GET['to']          ?? ''),
];

$rows = Payment::listFiltered($f);

$pageTitle = 'Payments';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-receipt me-2"></i>Payments</h4>
    <a href="create.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i> Record Contribution
    </a>
</div>

<form class="card shadow-sm border-0 mb-3" method="get">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small">Center</label>
            <select name="center_id" id="center_id" class="form-select">
                <option value="">All</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $f['center_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Jumuiya</label>
            <select name="jumuiya_id" id="jumuiya_id" class="form-select">
                <option value="">All</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Year</label>
            <select name="year" class="form-select">
                <option value="">All</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= (int)$y['year'] ?>" <?= $f['year'] == $y['year'] ? 'selected' : '' ?>><?= (int)$y['year'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Type</label>
            <select name="type" class="form-select">
                <option value="">Any</option>
                <?php foreach (['REGISTRATION', 'WELFARE', 'OTHER'] as $t): ?>
                    <option value="<?= $t ?>" <?= $f['type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
                <option value="">Any</option>
                <option value="ACTIVE" <?= $f['status'] === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                <option value="VOIDED" <?= $f['status'] === 'VOIDED' ? 'selected' : '' ?>>VOIDED</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i></button>
        </div>
        <div class="col-md-2">
            <label class="form-label small">From</label>
            <input type="date" name="from" class="form-control" value="<?= e($f['from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">To</label>
            <input type="date" name="to" class="form-control" value="<?= e($f['to'] ?? '') ?>">
        </div>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table id="tblPay" class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Receipt</th>
                    <th>Date</th>
                    <th>Member</th>
                    <th>Jumuiya</th>
                    <th>Type</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?= e($r['receipt_no']) ?></span></td>
                        <td class="small"><?= e($r['payment_date']) ?></td>
                        <td><?= e($r['full_name']) ?><div class="text-muted small"><?= e($r['member_code']) ?></div>
                        </td>
                        <td class="small"><?= e($r['jumuiya_name']) ?><div class="text-muted small"><?= e($r['center_name']) ?></div>
                        </td>
                        <td><span class="badge bg-info text-dark"><?= e($r['payment_type']) ?></span></td>
                        <td class="text-end"><?= number_format((float)$r['amount'], 2) ?></td>
                        <td>
                            <span class="badge bg-<?= $r['status'] === 'ACTIVE' ? 'success' : 'danger' ?>">
                                <?= e($r['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="receipt.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary">
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
        const preJum = <?= json_encode($f['jumuiya_id'] ?? '') ?>;

        function loadJum() {
            const cid = $('#center_id').val();
            const $j = $('#jumuiya_id').empty().append('<option value="">All</option>');
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

        $('#tblPay').DataTable({
            order: [
                [1, 'desc']
            ],
            pageLength: 50,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });
    });
</script>