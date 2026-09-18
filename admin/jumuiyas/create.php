<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('jumuiya.create');
parish_required();

$centers = Db::all("SELECT id, name FROM centers WHERE status='ACTIVE' ORDER BY name");
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $center_id = (int)($_POST['center_id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');

    if ($center_id <= 0) $errors[] = 'Center is required.';
    if ($name === '')    $errors[] = 'Jumuiya name is required.';

    if (!$errors) {
        $dup = Db::one(
            "SELECT id FROM jumuiyas WHERE center_id = :c AND name = :n",
            ['c' => $center_id, 'n' => $name]
        );
        if ($dup) $errors[] = 'That jumuiya already exists under the selected center.';
    }

    if (!$errors) {
        Db::begin();
        try {
            $id = Db::insert('jumuiyas', [
                'center_id' => $center_id,
                'name'      => $name,
                'status'    => 'ACTIVE',
            ]);
            Audit::log(
                'CREATE',
                'jumuiyas',
                $id,
                null,
                ['center_id' => $center_id, 'name' => $name]
            );
            Db::commit();
            header('Location: index.php?ok=created');
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = 'Save failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'New Jumuiya';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-plus me-2"></i>New Jumuiya</h4>
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
        <div class="col-md-6">
            <label class="form-label small">Center *</label>
            <select name="center_id" class="form-select" required>
                <option value="">— select center —</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= (($_POST['center_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small">Jumuiya name *</label>
            <input name="name" class="form-control" required
                value="<?= e($_POST['name'] ?? '') ?>">
        </div>
    </div>

    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Jumuiya
        </button>
    </div>
</form>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>