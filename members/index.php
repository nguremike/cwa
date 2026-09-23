<?php
require __DIR__ . '/../includes/auth.php';
require_permission('member.view');

$centers = Db::all("SELECT id, name FROM centers ORDER BY name");

// if (!isset($_GET['status'])) {
//     $_GET['status'] = 'ACTIVE';
//     $f['status'] = 'ACTIVE';
// }

$f = [
    'center_id'  => (int)($_GET['center_id']  ?? 0) ?: null,
    'jumuiya_id' => (int)($_GET['jumuiya_id'] ?? 0) ?: null,
    'status'     => trim($_GET['status'] ?? ''),
    'q'          => trim($_GET['q'] ?? ''),
];

$m = Member::find($memberId);
if (!$m) {
    http_response_code(404);
    exit('Member not found');
}
if (scope_center_id() && (int)$m['center_id'] !== scope_center_id()) {
    http_response_code(403);
    exit('Out of scope.');
}
if (scope_jumuiya_id() && (int)$m['jumuiya_id'] !== scope_jumuiya_id()) {
    http_response_code(403);
    exit('Out of scope.');
}

$members = [];

if ($f['center_id'] || $f['jumuiya_id'] || $f['status'] || $f['q']) {
    $members = Member::search($f, 500);
} else {
    $members = Member::all();
}

$pageTitle = 'Members';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-users me-2"></i>Members</h4>
    <a href="create.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-user-plus me-1"></i> Register Member
    </a>
</div>

<form class="card shadow-sm border-0 mb-3" method="get">
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
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
                <option value="">Any</option>
                <?php foreach (['ACTIVE', 'INACTIVE', 'TRANSFERRED', 'DECEASED', 'LEFT', 'SUSPENDED'] as $s): ?>
                    <option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Search</label>
            <input name="q" class="form-control" placeholder="Name, phone, code, ID"
                value="<?= e($f['q']) ?>">
        </div>
        <div class="col-md-1 text-end">
            <button class="btn btn-primary w-100">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </div>
</form>

<?php if ($members): ?>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table id="tblMembers" class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Jumuiya</th>
                        <th>Center</th>
                        <th>Join date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?= e($m['member_code']) ?></span></td>
                            <td><strong><?= e($m['full_name']) ?></strong></td>
                            <td class="small"><?= e($m['phone']) ?></td>
                            <td><?= e($m['jumuiya_name']) ?></td>
                            <td class="small text-muted"><?= e($m['center_name']) ?></td>
                            <td class="small"><?= e($m['join_date']) ?></td>
                            <td>
                                <span class="badge bg-<?= match ($m['status']) {
                                                            'ACTIVE'      => 'success',
                                                            'INACTIVE'    => 'secondary',
                                                            'TRANSFERRED' => 'info',
                                                            'DECEASED'    => 'dark',
                                                            'LEFT'        => 'warning',
                                                            'SUSPENDED'   => 'danger',
                                                            default       => 'secondary',
                                                        } ?>"><?= e($m['status']) ?></span>
                            </td>
                            <td class="text-end">
                                <a href="view.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-primary" title="Profile">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="../reports/member-statement.php?id=<?= (int)$m['id'] ?>"
                                    class="btn btn-sm btn-outline-info" title="Statement">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>
                                <a href="edit.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <!-- create a link to ledger file ledger.php?id=$m['id'] -->
                                <a href="ledger.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-info" title="Ledger">
                                    <i class="fa-solid fa-file-lines"></i>
                                </a>
                                <a href="transfer.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-warning" title="Transfer">
                                    <i class="fa-solid fa-right-left"></i>
                                </a>
                                <a href="status.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-danger" title="Status">
                                    <i class="fa-solid fa-user-shield"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($f['center_id'] || $f['jumuiya_id'] || $f['status'] || $f['q']): ?>
    <div class="alert alert-info">No members match those filters.</div>
<?php else: ?>
    <div class="alert alert-light border">
        Choose a center, a jumuiya, or enter a search term to list members.
    </div>
<?php endif; ?>


<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        const preJumuiya = <?= json_encode($f['jumuiya_id'] ?? '') ?>;

        function loadJumuiyas() {
            const cid = $('#center_id').val();
            const $j = $('#jumuiya_id').empty().append('<option value="">All jumuiyas</option>');
            if (!cid) return;
            api('/api/jumuiyas.php', {
                center_id: cid
            }).done(res => {
                (res.data || []).forEach(j => {
                    const sel = (preJumuiya == j.id) ? ' selected' : '';
                    $j.append(`<option value="${j.id}"${sel}>${j.name}</option>`);
                });
            });
        }
        $('#center_id').on('change', loadJumuiyas);
        loadJumuiyas();

        if ($('#tblMembers').length) {
            $('#tblMembers').DataTable({
                order: [
                    [1, 'asc']
                ],
                pageLength: 50,
                columnDefs: [{
                    orderable: false,
                    targets: -1
                }]
            });
        }
    });
</script>