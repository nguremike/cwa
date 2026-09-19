<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('center.view');

$centers = Db::all(
    "SELECT c.id, c.name, c.status, c.welfare_enabled, c.created_at,
          (SELECT COUNT(*) FROM jumuiyas j WHERE j.center_id = c.id AND j.status='ACTIVE') AS jumuiya_count
     FROM centers c
    ORDER BY c.name"
);

$pageTitle = 'Centers';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-diagram-project me-2"></i>Centers</h4>
    <a href="create.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i> New Center
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table id="tblCenters" class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Jumuiyas</th>
                    <th>Welfare</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($centers as $c): ?>
                    <tr>
                        <td><?= (int)$c['id'] ?></td>
                        <td><?= e($c['name']) ?></td>
                        <td><span class="badge bg-secondary"><?= (int)$c['jumuiya_count'] ?></span></td>
                        <td>
                            <?php if ($c['welfare_enabled']): ?>
                                <span class="badge bg-success">Enabled</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $c['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                                <?= e($c['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="edit.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="toggle.php?id=<?= (int)$c['id'] ?>&csrf=<?= csrf_token() ?>"
                                class="btn btn-sm btn-outline-<?= $c['status'] === 'ACTIVE' ? 'warning' : 'success' ?> btn-toggle"
                                data-name="<?= e($c['name']) ?>"
                                data-action="<?= $c['status'] === 'ACTIVE' ? 'Deactivate' : 'Activate' ?>">
                                <i class="fa-solid fa-power-off"></i>
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
        $('#tblCenters').DataTable({
            order: [
                [0, 'asc']
            ],
            pageLength: 25,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });

        $('.btn-toggle').on('click', function(e) {
            e.preventDefault();
            const url = this.href;
            const name = this.dataset.name;
            const action = this.dataset.action;
            Swal.fire({
                title: `${action} center?`,
                html: `You are about to <b>${action.toLowerCase()}</b> <b>${name}</b>.<br>
             <span class="text-muted small">This is a status change only — no data is deleted.</span>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: action,
            }).then(r => {
                if (r.isConfirmed) window.location.href = url;
            });
        });
    });
</script>