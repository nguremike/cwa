<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('audit.view');

$users   = Db::all("SELECT id, username, full_name FROM users ORDER BY full_name");
$actions = Db::all("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$tables  = Db::all("SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL ORDER BY table_name");

$f = [
    'user_id'    => (int)($_GET['user_id'] ?? 0) ?: null,
    'action'     => trim($_GET['action']     ?? ''),
    'table_name' => trim($_GET['table_name'] ?? ''),
    'from'       => trim($_GET['from']       ?? ''),
    'to'         => trim($_GET['to']         ?? ''),
    'q'          => trim($_GET['q']          ?? ''),
];

$sql = "SELECT a.*, u.full_name AS user_name, u.username
          FROM audit_logs a
          LEFT JOIN users u ON u.id = a.user_id
         WHERE 1 = 1";
$p = [];
if ($f['user_id']) {
    $sql .= " AND a.user_id = :uid";
    $p['uid'] = $f['user_id'];
}
if ($f['action'] !== '') {
    $sql .= " AND a.action = :act";
    $p['act'] = $f['action'];
}
if ($f['table_name'] !== '') {
    $sql .= " AND a.table_name = :tbl";
    $p['tbl'] = $f['table_name'];
}
if ($f['from'] !== '') {
    $sql .= " AND DATE(a.created_at) >= :fr";
    $p['fr'] = $f['from'];
}
if ($f['to'] !== '') {
    $sql .= " AND DATE(a.created_at) <= :to";
    $p['to'] = $f['to'];
}
if ($f['q'] !== '') {
    $sql .= " AND (a.record_id LIKE :q1 OR a.new_values LIKE :q2 OR a.old_values LIKE :q3)";
    $like = '%' . $f['q'] . '%';
    $p['q1'] = $like;
    $p['q2'] = $like;
    $p['q3'] = $like;
}
$sql .= " ORDER BY a.id DESC LIMIT 2000";

$rows = Db::all($sql, $p);

$pageTitle = 'Audit Log';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0"><i class="fa-solid fa-clipboard-list me-2"></i>Audit Log</h4>
    <a href="export.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-success btn-sm">
        <i class="fa-solid fa-file-excel me-1"></i> Export CSV
    </a>
</div>

<form class="card shadow-sm border-0 mb-3 no-print" method="get">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">User</label>
            <select name="user_id" class="form-select">
                <option value="">Any</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= $f['user_id'] == $u['id'] ? 'selected' : '' ?>>
                        <?= e($u['full_name']) ?> (<?= e($u['username']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Action</label>
            <select name="action" class="form-select">
                <option value="">Any</option>
                <?php foreach ($actions as $a): ?>
                    <option value="<?= e($a['action']) ?>" <?= $f['action'] === $a['action'] ? 'selected' : '' ?>>
                        <?= e($a['action']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Table</label>
            <select name="table_name" class="form-select">
                <option value="">Any</option>
                <?php foreach ($tables as $t): ?>
                    <option value="<?= e($t['table_name']) ?>" <?= $f['table_name'] === $t['table_name'] ? 'selected' : '' ?>>
                        <?= e($t['table_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">From</label>
            <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">To</label>
            <input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>">
        </div>
        <div class="col-md-1 text-end">
            <button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i></button>
        </div>
        <div class="col-md-6">
            <label class="form-label small">Search values</label>
            <input name="q" class="form-control" value="<?= e($f['q']) ?>" placeholder="record id, receipt number, name...">
        </div>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table id="tblAudit" class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record</th>
                    <th>IP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="small"><?= e($r['created_at']) ?></td>
                        <td class="small">
                            <?= e($r['user_name'] ?? '—') ?>
                            <?php if (!empty($r['username'])): ?>
                                <div class="text-muted"><?= e($r['username']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badge = match ($r['action']) {
                                'CREATE'    => 'success',
                                'UPDATE'    => 'primary',
                                'DELETE'    => 'danger',
                                'LOGIN'     => 'info',
                                'LOGOUT'    => 'secondary',
                                'PAYMENT'   => 'success',
                                'REVERSAL'  => 'danger',
                                'TRANSFER'  => 'warning',
                                'ADJUSTMENT' => 'warning',
                                'EXPORT'    => 'dark',
                                default     => 'secondary',
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= e($r['action']) ?></span>
                        </td>
                        <td class="small text-muted"><?= e($r['table_name'] ?? '') ?></td>
                        <td class="small"><?= $r['record_id'] ? '#' . (int)$r['record_id'] : '' ?></td>
                        <td class="small text-muted"><?= e($r['ip_address'] ?? '') ?></td>
                        <td class="text-end">
                            <a href="view.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>
<script>
    $(function() {
        $('#tblAudit').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 50,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });
    });
</script>