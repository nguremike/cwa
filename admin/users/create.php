<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('user.create');

$roles   = Db::all("SELECT id, name FROM roles ORDER BY name");
$centers = Db::all("SELECT id, name FROM centers WHERE status='ACTIVE' ORDER BY name");
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $username  = trim($_POST['username']  ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $role_id   = (int)($_POST['role_id']  ?? 0);
    $center_id = $_POST['center_id'] !== '' ? (int)$_POST['center_id'] : null;
    $jumuiya_id = $_POST['jumuiya_id'] !== '' ? (int)$_POST['jumuiya_id'] : null;
    $password  = (string)($_POST['password'] ?? '');
    $confirm   = (string)($_POST['password_confirm'] ?? '');

    if ($username === '')   $errors[] = 'Username is required.';
    if ($full_name === '')  $errors[] = 'Full name is required.';
    if ($role_id <= 0)      $errors[] = 'Role is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $dup = Db::one("SELECT id FROM users WHERE username = :u", ['u' => $username]);
        if ($dup) $errors[] = 'Username already exists.';
    }

    if (!$errors) {
        Db::begin();
        try {
            $id = Db::insert('users', [
                'username'      => $username,
                'full_name'     => $full_name,
                'email'         => $email ?: null,
                'phone'         => $phone ?: null,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'role_id'       => $role_id,
                'center_id'     => $center_id,
                'jumuiya_id'    => $jumuiya_id,
                'status'        => 'ACTIVE',
            ]);
            Audit::log('CREATE', 'users', $id, null, [
                'username' => $username,
                'role_id' => $role_id
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

$pageTitle = 'New User';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-user-plus me-2"></i>New User</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" class="card shadow-sm border-0">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="card-body row g-3">

        <div class="col-md-4">
            <label class="form-label small">Username *</label>
            <input name="username" class="form-control" required
                value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label small">Full name *</label>
            <input name="full_name" class="form-control" required
                value="<?= e($_POST['full_name'] ?? '') ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label small">Email</label>
            <input name="email" type="email" class="form-control"
                value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small">Phone</label>
            <input name="phone" class="form-control"
                value="<?= e($_POST['phone'] ?? '') ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label small">Role *</label>
            <select name="role_id" class="form-select" required>
                <option value="">— select —</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>"
                        <?= (($_POST['role_id'] ?? '') == $r['id']) ? 'selected' : '' ?>>
                        <?= e($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small">Center scope (optional)</label>
            <select name="center_id" id="center_id" class="form-select">
                <option value="">— global —</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= (($_POST['center_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small">Jumuiya scope (optional)</label>
            <select name="jumuiya_id" id="jumuiya_id" class="form-select">
                <option value="">— none —</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label small">Password *</label>
            <input name="password" type="password" class="form-control" required minlength="8">
        </div>
        <div class="col-md-6">
            <label class="form-label small">Confirm password *</label>
            <input name="password_confirm" type="password" class="form-control" required minlength="8">
        </div>
    </div>

    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save User
        </button>
    </div>
</form>

<script>
    $(function() {
        const preJumuiya = <?= json_encode($_POST['jumuiya_id'] ?? '') ?>;

        function loadJumuiyas() {
            const cid = $('#center_id').val();
            const $j = $('#jumuiya_id').empty().append('<option value="">— none —</option>');
            if (!cid) return;
            api('/api/jumuiyas.php', {
                center_id: cid
            }).done(res => {
                (res.data || []).forEach(j => {
                    const sel = (preJumuiya == j.id) ? ' selected' : '';
                    $j.append(`<option value="${j.id}"${sel}>${j.name}</option>`);
                });
            });
        }
        $('#center_id').on('change', loadJumuiyas);
        loadJumuiyas();
    });
</script>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>