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
<?php $fy = FinancialYear::current(); ?>
<?php if ($fy): ?>
    <div class="card mt-4 shadow-sm border-0">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted small">Current Financial Year</div>
                <div class="h5 mb-0">
                    <?= (int)$fy['year'] ?>
                    <span class="badge bg-success ms-2"><?= e($fy['status']) ?></span>
                </div>
            </div>
            <?php if (user_can('settings.view') || user_can('*')): ?>
                <div>
                    <a href="<?= e($config['app']['url']) ?>/admin/schedules/?year=<?= (int)$fy['year'] ?>"
                        class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-table-list me-1"></i> View schedules
                    </a>
                    <a href="<?= e($config['app']['url']) ?>/admin/financial-years/"
                        class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-calendar-days me-1"></i> Financial Years
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<div class="row g-3">

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