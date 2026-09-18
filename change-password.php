<?php
require __DIR__ . '/includes/auth.php';

$msg = null;
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $cur  = (string)($_POST['current'] ?? '');
    $new  = (string)($_POST['new'] ?? '');
    $conf = (string)($_POST['confirm'] ?? '');

    $u = Db::one("SELECT password_hash FROM users WHERE id = :id", ['id' => Auth::id()]);
    if (!$u || !password_verify($cur, $u['password_hash'])) {
        $err = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $err = 'New password must be at least 8 characters.';
    } elseif ($new !== $conf) {
        $err = 'New passwords do not match.';
    } else {
        Db::update(
            'users',
            ['password_hash' => password_hash($new, PASSWORD_BCRYPT)],
            'id = :id',
            ['id' => Auth::id()]
        );
        Audit::log('UPDATE', 'users', Auth::id(), null, ['password_changed' => true]);
        $msg = 'Password updated successfully.';
    }
}

$pageTitle = 'Change Password';
require __DIR__ . '/templates/layout/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <h4 class="mb-3"><i class="fa-solid fa-key me-2"></i>Change Password</h4>

        <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

        <form method="post" class="card shadow-sm border-0">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small">Current password</label>
                    <input name="current" type="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">New password</label>
                    <input name="new" type="password" class="form-control" required minlength="8">
                </div>
                <div class="mb-0">
                    <label class="form-label small">Confirm new password</label>
                    <input name="confirm" type="password" class="form-control" required minlength="8">
                </div>
            </div>
            <div class="card-footer bg-white text-end">
                <button class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/templates/layout/footer.php'; ?>