<?php
require __DIR__ . '/../includes/auth.php';
require_permission('member.edit');

$id = (int)($_GET['id'] ?? 0);
$m  = Member::find($id);
if (!$m) {
    http_response_code(404);
    exit('Member not found');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $data = [
        'full_name'     => trim($_POST['full_name']  ?? ''),
        'phone'         => trim($_POST['phone']      ?? ''),
        'id_number'     => trim($_POST['id_number']  ?? '') ?: null,
        'gender'        => $_POST['gender']           ?? null,
        'date_of_birth' => trim($_POST['date_of_birth'] ?? '') ?: null,
        'notes'         => trim($_POST['notes']      ?? '') ?: null,
    ];

    if ($data['full_name'] === '') $errors[] = 'Full name is required.';
    // if ($data['phone'] === '')     $errors[] = 'Phone is required.';

    if (!$errors) {
        $dup = Db::one(
            "SELECT id FROM members WHERE phone = :p AND id <> :id",
            ['p' => $data['phone'], 'id' => $id]
        );
        if ($dup) $errors[] = 'That phone number belongs to another member.';
    }

    if (!$errors) {
        try {
            Member::update($id, $data, Auth::id());
            header('Location: view.php?id=' . $id . '&ok=updated');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    $m = array_merge($m, $data);
}

$pageTitle = 'Edit Member';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-pen me-2"></i>Edit Member</h4>
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Back</a>
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
            <label class="form-label small">Full name *</label>
            <input name="full_name" class="form-control" required value="<?= e($m['full_name']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small">Phone</label>
            <input name="phone" class="form-control" required value="<?= e($m['phone']) ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label small">National ID</label>
            <input name="id_number" class="form-control" value="<?= e($m['id_number'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Date of birth</label>
            <input name="date_of_birth" type="date" class="form-control" value="<?= e($m['date_of_birth'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Gender</label>
            <select name="gender" class="form-select">
                <option value="">—</option>
                <?php foreach (['FEMALE', 'MALE', 'OTHER'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($m['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label small">Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?= e($m['notes'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div class="alert alert-light border small mb-0">
                Member code, join date, and jumuiya are not editable here.
                Use <a href="transfer.php?id=<?= $id ?>">Transfer</a> to change jumuiya.
            </div>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
        </button>
    </div>
</form>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>