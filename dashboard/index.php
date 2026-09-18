<?php
require __DIR__ . '/../includes/auth.php';
require_permission('dashboard.view');

$pageTitle = 'Dashboard';
require __DIR__ . '/../templates/layout/header.php';
?>

<?php
$counts = [
    'centers'   => (int)Db::one("SELECT COUNT(*) c FROM centers WHERE status='ACTIVE'")['c'],
    'jumuiyas'  => (int)Db::one("SELECT COUNT(*) c FROM jumuiyas WHERE status='ACTIVE'")['c'],
    'users'     => (int)Db::one("SELECT COUNT(*) c FROM users WHERE status='ACTIVE'")['c'],
];
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
                <div class="text-muted small">Active Centers</div>
                <div class="h5 mb-0"><?= $counts['centers'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Active Jumuiyas</div>
                <div class="h5 mb-0"><?= $counts['jumuiyas'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Active Users</div>
                <div class="h5 mb-0"><?= $counts['users'] ?></div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3">



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
                <div class="text-muted small">Phase</div>
                <div class="h6 mb-0 text-success">1 · Step 3 complete</div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>