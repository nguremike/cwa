<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('user.view');

$users = Db::all(
    "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.status,
          u.last_login_at, r.name AS role_name,
          c.name AS center_name, j.name AS jumuiya_name
     FROM users u
     JOIN roles r ON r.id = u.role_id
     LEFT JOIN centers c ON c.id = u.center_id
     LEFT JOIN jumuiyas j ON j.id = u.jumuiya_id
    ORDER BY u.id DESC"
);

$pageTitle = 'Users';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-users me-2"></i>Users</h4>
    <a href="create.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i> New User
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table id="tblUsers" class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Full name</th>
                    <th>Role</th>
                    <th>Scope</th>
                    <th>Status</th>
                    <th>Last login</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['full_name']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= e($u['role_name']) ?></span></td>
                        <td>
                            <?php if ($u['center_name']): ?>
                                <span class="text-muted small">
                                    Center: <?= e($u['center_name']) ?>
                                    <?= $u['jumuiya_name'] ? ' / ' . e($u['jumuiya_name']) : '' ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">Global</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $s = $u['status']; ?>
                            <span class="badge bg-<?= $s === 'ACTIVE' ? 'success' : ($s === 'LOCKED' ? 'danger' : 'secondary') ?>">
                                <?= e($s) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= e($u['last_login_at'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-pen"></i>
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
        $('#tblUsers').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });
    });
</script>