<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('center.edit');
$parish = parish_required();

$id = (int)($_GET['id'] ?? 0);
$center = Db::one("SELECT * FROM centers WHERE id = :id", ['id' => $id]);
if (!$center) {
    http_response_code(404);
    exit('Center not found');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $name            = trim($_POST['name'] ?? '');
    $welfare_enabled = isset($_POST['welfare_enabled']) ? 1 : 0;

    if ($name === '') $errors[] = 'Name is required.';
    if (!$errors) {
        $dup = Db::one(
            "SELECT id FROM centers WHERE parish_id = :p AND name = :n AND id <> :id",
            ['p' => $parish['id'], 'n' => $name, 'id' => $id]
        );
        if ($dup) $errors[] = 'Another center already uses that name.';
    }

    if (!$errors) {
        Db::begin();
        try {
            $old = ['name' => $center['name'], 'welfare_enabled' => (int)$center['welfare_enabled']];
            Db::update('centers', [
                'name'            => $name,
                'welfare_enabled' => $welfare_enabled,
            ], 'id = :id', ['id' => $id]);

            Audit::log('UPDATE', 'centers', $id, $old, [
                'name' => $name,
                'welfare_enabled' => $welfare_enabled,
            ]);
            Db::commit();
            header('Location: index.php?ok=updated');
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = 'Save failed: ' . $e->getMessage();
        }
    }
    $center = array_merge($center, $_POST);
}

$pageTitle = 'Edit Center';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-pen me-2"></i>Edit Center</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

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
        <div class="col-md-8">
            <label class="form-label small">Center name *</label>
            <input name="name" class="form-control" required value="<?= e($center['name']) ?>">
            <div class="form-text">Parish: <strong><?= e($parish['name']) ?></strong></div>
        </div>

        <div class="col-md-4">
            <label class="form-label small d-block">Welfare</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="welfare_enabled"
                    name="welfare_enabled" value="1"
                    <?= !empty($center['welfare_enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="welfare_enabled">
                    Welfare enabled for this center
                </label>
            </div>
        </div>
    </div>

    <div class="card-footer bg-white d-flex justify-content-between">
        <span class="text-muted small align-self-center">
            Status: <span class="badge bg-<?= $center['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                <?= e($center['status']) ?>
            </span>
            &nbsp;·&nbsp; Change status from the list page.
        </span>
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
        </button>
    </div>
</form>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>