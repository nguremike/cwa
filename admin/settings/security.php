<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

$keys = [
    'login_window_minutes'      => ['Login failure window (minutes)', 'int'],
    'login_max_attempts'        => ['Max failed login attempts',      'int'],
    'login_lock_minutes'        => ['Login lock duration (minutes)',  'int'],
    'session_rotate_seconds'    => ['Session ID rotation (seconds)',  'int'],
    'session_idle_seconds'      => ['Session idle timeout (seconds)',  'int'],
    'reversal_min_reason_chars' => ['Minimum void reason length',      'int'],
    'reversal_cap_center_admin' => ['Center Admin void cap (KSh)',     'int'],
    'reversal_cap_parish_admin' => ['Parish Admin void cap (KSh)',     'int'],
    'reversal_requires_approval' => ['Require approval above cap (0/1)', 'bool'],
    'housekeeping_audit_days'   => ['Prune audit logs after (days)',   'int'],
    'housekeeping_login_days'   => ['Prune login attempts after (days)', 'int'],
];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    foreach ($keys as $k => [$label, $type]) {
        $v = $_POST[$k] ?? '';
        if ($type === 'int' && !preg_match('/^\d+$/', (string)$v)) {
            $errors[] = "{$label} must be a non-negative integer.";
            continue;
        }
        if ($type === 'bool') $v = ((int)$v === 1) ? '1' : '0';
        setting_set($k, (string)$v);
    }
    if (!$errors) {
        Audit::log('UPDATE', 'settings', null, null, ['group' => 'security']);
        header('Location: security.php?ok=1');
        exit;
    }
}

$pageTitle = 'Security Settings';
require __DIR__ . '/../../templates/layout/header.php';
?>
<h4 class="mb-3"><i class="fa-solid fa-lock me-2"></i>Security Settings</h4>

<?php if (!empty($_GET['ok'])): ?>
    <div class="alert alert-success py-2 small">Saved.</div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="card shadow-sm border-0">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="card-body row g-3">
        <?php foreach ($keys as $k => [$label, $type]): ?>
            <div class="col-md-6">
                <label class="form-label small"><?= e($label) ?></label>
                <?php if ($type === 'bool'): ?>
                    <select name="<?= e($k) ?>" class="form-select">
                        <option value="0" <?= setting_bool($k) ? '' : 'selected' ?>>0 — do not require approval</option>
                        <option value="1" <?= setting_bool($k) ? 'selected' : '' ?>>1 — require approval above cap</option>
                    </select>
                <?php else: ?>
                    <input type="number" min="0" name="<?= e($k) ?>" class="form-control"
                        value="<?= e((string)setting_get($k, '')) ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Save</button>
    </div>
</form>

<div class="alert alert-light border small mt-3">
    <i class="fa-solid fa-circle-info me-1"></i>
    Changes take effect on the next request. Lowering the idle timeout may sign out idle users.
</div>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>