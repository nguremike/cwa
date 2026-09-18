<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('user.edit');

$id = (int)($_GET['id'] ?? 0);
$user = Db::one("SELECT * FROM users WHERE id = :id", ['id' => $id]);
if (!$user) {
    http_response_code(404);
    exit('User not found');
}

$roles   = Db::all("SELECT id, name FROM roles ORDER BY name");
$centers = Db::all("SELECT id, name FROM centers WHERE status='ACTIVE' ORDER BY name");
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $role_id   = (int)($_POST['role_id']  ?? 0);
    $center_id = $_POST['center_id'] !== '' ? (int)$_POST['center_id'] : null;
    $jumuiya_id = $_POST['jumuiya_id'] !== '' ? (int)$_POST['jumuiya_id'] : null;
    $status    = $_POST['status'] ?? 'ACTIVE';
    $newPass   = (string)($_POST['password'] ?? '');

    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($role_id <= 0)     $errors[] = 'Role is required.';
    if ($newPass !== '' && strlen($newPass) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (!$errors) {
        Db::begin();
        try {
            $data = [
                'full_name'  => $full_name,
                'email'      => $email ?: null,
                'phone'      => $phone ?: null,
                'role_id'    => $role_id,
                'center_id'  => $center_id,
                'jumuiya_id' => $jumuiya_id,
                'status'     => $status,
            ];
            if ($newPass !== '') {
                $data['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT);
            }
            Db::update('users', $data, 'id = :id', ['id' => $id]);
            Audit::log('UPDATE', 'users', $id, null, ['status' => $status, 'role_id' => $role_id]);
            Db::commit();
            header('Location: index.php?ok=updated');
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = 'Save failed: ' . $e->getMessage();
        }
    }
    // Guard against missing keys from partial re-submits
    $posted = array_merge([
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'phone'     => $user['phone'],
        'role_id'   => $user['role_id'],
        'center_id' => $user['center_id'],
        'jumuiya_id' => $user['jumuiya_id'],
        'status'    => $user['status'],
    ], $_POST);
    $user = array_merge($user, $posted);
}

$pageTitle = 'Edit User';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-user-pen me-2"></i>Edit User</h4>
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

        <div class="col-md-4">
            <label class="form-label small">Username</label>
            <input class="form-control" value="<?= e($user['username']) ?>" disabled>
        </div>
        <div class="col-md-8">
            <label class="form-label small">Full name *</label>
            <input name="full_name" class="form-control" required value="<?= e($user['full_name']) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label small">Email</label>
            <input name="email" type="email" class="form-control" value="<?= e($user['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small">Phone</label>
            <input name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label small">Role *</label>
            <select name="role_id" class="form-select" required>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>"
                        <?= ($user['role_id'] == $r['id']) ? 'selected' : '' ?>>
                        <?= e($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small">Center scope</label>
            <select name="center_id" id="center_id" class="form-select">
                <option value="">— global —</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= ($user['center_id'] == $c['id']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small">Jumuiya scope</label>
            <select name="jumuiya_id" id="jumuiya_id" class="form-select">
                <option value="">— none —</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
                <?php $curStatus = $user['status'] ?? 'ACTIVE'; ?>
                <?php foreach (['ACTIVE', 'INACTIVE', 'LOCKED'] as $s): ?>
                    <option value="<?= $s ?>" <?= $curStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small">New password (leave blank to keep current)</label>
            <input name="password" type="password" class="form-control" minlength="8">
        </div>
    </div>

    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
        </button>
    </div>
</form>

<script>
    $(function() {
        const preJumuiya = <?= json_encode($user['jumuiya_id'] ?? '') ?>;

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