<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('jumuiya.view');

$centers = Db::all("SELECT id, name FROM centers ORDER BY name");
$filter  = (int)($_GET['center_id'] ?? 0);

$sql = "SELECT j.id, j.name, j.status,
               c.name AS center_name, c.id AS center_id
          FROM jumuiyas j
          JOIN centers c ON c.id = j.center_id";
$params = [];
if ($filter) {
    $sql .= " WHERE j.center_id = :cid";
    $params['cid'] = $filter;
}
$sql .= " ORDER BY c.name, j.name";

$jumuiyas = Db::all($sql, $params);

$pageTitle = 'Jumuiyas';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-people-group me-2"></i>Jumuiyas</h4>
    <a href="create.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i> New Jumuiya
    </a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-4">
        <select name="center_id" class="form-select" onchange="this.form.submit()">
            <option value="">All centers</option>
            <?php foreach ($centers as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $filter == $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table id="tblJumuiyas" class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Center</th>
                    <th>Jumuiya</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jumuiyas as $j): ?>
                    <tr>
                        <td><?= (int)$j['id'] ?></td>
                        <td><?= e($j['center_name']) ?></td>
                        <td><?= e($j['name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $j['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                                <?= e($j['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="edit.php?id=<?= (int)$j['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="toggle.php?id=<?= (int)$j['id'] ?>&csrf=<?= csrf_token() ?>"
                                class="btn btn-sm btn-outline-<?= $j['status'] === 'ACTIVE' ? 'warning' : 'success' ?> btn-toggle"
                                data-name="<?= e($j['name']) ?>"
                                data-action="<?= $j['status'] === 'ACTIVE' ? 'Deactivate' : 'Activate' ?>">
                                <i class="fa-solid fa-power-off"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(function() {
        $('#tblJumuiyas').DataTable({
            order: [
                [1, 'asc'],
                [2, 'asc']
            ],
            pageLength: 25,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });

        $('.btn-toggle').on('click', function(e) {
            e.preventDefault();
            const url = this.href,
                name = this.dataset.name,
                action = this.dataset.action;
            Swal.fire({
                title: `${action} jumuiya?`,
                html: `You are about to <b>${action.toLowerCase()}</b> <b>${name}</b>.<br>
             <span class="text-muted small">Status change only — no data is deleted.</span>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: action,
            }).then(r => {
                if (r.isConfirmed) window.location.href = url;
            });
        });
    });
</script>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>