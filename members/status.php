<?php
require __DIR__ . '/../includes/auth.php';
require_permission('member.edit');

$id = (int)($_GET['id'] ?? 0);
$m  = Member::find($id);
if (!$m) {
    http_response_code(404);
    exit('Member not found');
}

$statuses = ['ACTIVE', 'INACTIVE', 'TRANSFERRED', 'DECEASED', 'LEFT', 'SUSPENDED'];
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $new = $_POST['status'] ?? '';
    if (!in_array($new, $statuses, true)) $errors[] = 'Invalid status.';

    if (!$errors) {
        try {
            Member::changeStatus($id, $new, Auth::id());
            header('Location: view.php?id=' . $id . '&ok=status');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Member Status';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-user-shield me-2"></i>Change Status</h4>
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="alert alert-light border small">
    <strong><?= e($m['full_name']) ?></strong> (<?= e($m['member_code']) ?>) is currently
    <span class="badge bg-<?= $m['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>"><?= e($m['status']) ?></span>.
    Status change does not delete financial history.
</div>

<form method="post" class="card shadow-sm border-0">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="card-body">
        <label class="form-label small">New status *</label>
        <select name="status" class="form-select" required>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $m['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-danger">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Status
        </button>
    </div>
</form>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>