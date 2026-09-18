<?php
require __DIR__ . '/../includes/auth.php';
require_permission('dashboard.view');

$pageTitle = 'Dashboard';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="row g-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Signed in as</div>
                <div class="h6 mb-0"><?= e($_SESSION['user']['full_name']) ?></div>
                <div class="text-muted small"><?= e($_SESSION['user']['role_name']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Current Financial Year</div>
                <div class="h5 mb-0"><?= e((string)current_year()) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Users</div>
                <div class="h5 mb-0"><?= (int)Db::one("SELECT COUNT(*) c FROM users")['c'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Phase</div>
                <div class="h6 mb-0 text-success">1 · Step 2 complete</div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>