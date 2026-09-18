<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('user.view');

$roles = Db::all("SELECT id, name, description FROM roles ORDER BY id");
$map   = permission_map();

$pageTitle = 'Roles';
require __DIR__ . '/../../templates/layout/header.php';
?>
<h4 class="mb-3"><i class="fa-solid fa-key me-2"></i>Roles &amp; Permissions</h4>
<div class="row g-3">
    <?php foreach ($roles as $r):
        $perms = $map[$r['name']] ?? [];
    ?>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <strong><?= e($r['name']) ?></strong>
                    <div class="text-muted small"><?= e($r['description'] ?? '') ?></div>
                </div>
                <div class="card-body">
                    <?php if ($perms === ['*']): ?>
                        <span class="badge bg-danger">Full system access</span>
                    <?php else: ?>
                        <?php foreach ($perms as $p): ?>
                            <span class="badge bg-light text-dark border mb-1"><?= e($p) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<p class="text-muted small mt-3">
    Roles are defined in <code>includes/permissions.php</code>. Editing UI will be added in a later phase.
</p>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>