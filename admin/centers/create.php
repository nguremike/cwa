<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('center.create');
$parish = parish_required();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $name             = trim($_POST['name'] ?? '');
    $welfare_enabled  = isset($_POST['welfare_enabled']) ? 1 : 0;

    if ($name === '') $errors[] = 'Name is required.';
    if (!$errors) {
        $dup = Db::one(
            "SELECT id FROM centers WHERE parish_id = :p AND name = :n",
            ['p' => $parish['id'], 'n' => $name]
        );
        if ($dup) $errors[] = 'A center with that name already exists.';
    }

    if (!$errors) {
        Db::begin();
        try {
            $id = Db::insert('centers', [
                'parish_id'       => $parish['id'],
                'name'            => $name,
                'welfare_enabled' => $welfare_enabled,
                'status'          => 'ACTIVE',
            ]);
            Audit::log('CREATE', 'centers', $id, null, [
                'name' => $name,
                'welfare_enabled' => $welfare_enabled,
            ]);
            Db::commit();
            header('Location: index.php?ok=created');
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = 'Save failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'New Center';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-plus me-2"></i>New Center</h4>
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
            <input name="name" class="form-control" required autofocus
                value="<?= e($_POST['name'] ?? '') ?>">
            <div class="form-text">
                Parish: <strong><?= e($parish['name']) ?></strong>
            </div>
        </div>

        <div class="col-md-4">
            <label class="form-label small d-block">Welfare</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="welfare_enabled"
                    name="welfare_enabled" value="1"
                    <?= !empty($_POST['welfare_enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="welfare_enabled">
                    Welfare enabled for this center
                </label>
            </div>
            <div class="form-text">
                Welfare obligation applies to members in this center.
            </div>
        </div>
    </div>

    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Center
        </button>
    </div>
</form>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>