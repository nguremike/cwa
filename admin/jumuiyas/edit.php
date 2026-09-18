<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('jumuiya.edit');
parish_required();

$id = (int)($_GET['id'] ?? 0);
$jum = Db::one("SELECT * FROM jumuiyas WHERE id = :id", ['id' => $id]);
if (!$jum) {
    http_response_code(404);
    exit('Jumuiya not found');
}

$centers = Db::all("SELECT id, name FROM centers ORDER BY name");
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $center_id = (int)($_POST['center_id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');

    if ($center_id <= 0) $errors[] = 'Center is required.';
    if ($name === '')    $errors[] = 'Jumuiya name is required.';

    if (!$errors) {
        $dup = Db::one(
            "SELECT id FROM jumuiyas WHERE center_id = :c AND name = :n AND id <> :id",
            ['c' => $center_id, 'n' => $name, 'id' => $id]
        );
        if ($dup) $errors[] = 'Another jumuiya already uses that name under this center.';
    }

    if (!$errors) {
        Db::begin();
        try {
            $old = ['center_id' => (int)$jum['center_id'], 'name' => $jum['name']];
            Db::update('jumuiyas', [
                'center_id' => $center_id,
                'name'      => $name,
            ], 'id = :id', ['id' => $id]);
            Audit::log(
                'UPDATE',
                'jumuiyas',
                $id,
                $old,
                ['center_id' => $center_id, 'name' => $name]
            );
            Db::commit();
            header('Location: index.php?ok=updated');
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = 'Save failed: ' . $e->getMessage();
        }
    }
    $jum = array_merge($jum, $_POST);
}

$pageTitle = 'Edit Jumuiya';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-pen me-2"></i>Edit Jumuiya</h4>
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
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= ($jum['center_id'] == $c['id']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">
                Moving a jumuiya between centers does not move its members. Use Member Transfers for that.
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label small">Jumuiya name *</label>
            <input name="name" class="form-control" required value="<?= e($jum['name']) ?>">
        </div>
    </div>

    <div class="card-footer bg-white d-flex justify-content-between">
        <span class="text-muted small align-self-center">
            Status: <span class="badge bg-<?= $jum['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                <?= e($jum['status']) ?>
            </span>
        </span>
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
        </button>
    </div>
</form>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>