<?php
require __DIR__ . '/../includes/auth.php';
require_permission('member.create');

$centers = Db::all("SELECT id, name FROM centers WHERE status='ACTIVE' ORDER BY name");
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $data = [
        'full_name'     => trim($_POST['full_name']  ?? ''),
        'phone'         => trim($_POST['phone']      ?? ''),
        'id_number'     => trim($_POST['id_number']  ?? '') ?: null,
        'gender'        => $_POST['gender']           ?? null,
        'date_of_birth' => trim($_POST['date_of_birth'] ?? '') ?: null,
        'join_date'     => trim($_POST['join_date']  ?? ''),
        'jumuiya_id'    => (int)($_POST['jumuiya_id'] ?? 0),
        'notes'         => trim($_POST['notes']      ?? '') ?: null,
    ];

    if ($data['full_name'] === '')  $errors[] = 'Full name is required.';
    // if ($data['phone'] === '')      $errors[] = 'Phone is required.';
    if ($data['jumuiya_id'] <= 0)   $errors[] = 'Jumuiya is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['join_date'])) $errors[] = 'Join date is required.';

    if (!$errors) {
        $dup = Db::one("SELECT id FROM members WHERE phone = :p", ['p' => $data['phone']]);
        if ($dup) $errors[] = 'That phone number is already registered to another member.';
    }

    if (!$errors) {
        try {
            $id = Member::create($data, Auth::id());
            header('Location: view.php?id=' . $id . '&ok=created');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Register Member';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-user-plus me-2"></i>Register Member</h4>
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
            <label class="form-label small">Full name *</label>
            <input name="full_name" class="form-control" required
                value="<?= e($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small">Phone </label>
            <input name="phone" class="form-control"
                value="<?= e($_POST['phone'] ?? '') ?>">
        </div>



        <div class="col-md-4">
            <label class="form-label small">Center *</label>
            <select id="center_id" class="form-select" required>
                <option value="">— select —</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= (($_POST['center_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Jumuiya *</label>
            <select name="jumuiya_id" id="jumuiya_id" class="form-select" required>
                <option value="">— select center first —</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Join date *</label>
            <input name="join_date" type="date" class="form-control" required
                value="<?= e($_POST['join_date'] ?? date('Y-m-d')) ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label small">National ID</label>
            <input name="id_number" class="form-control"
                value="<?= e($_POST['id_number'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Date of birth</label>
            <input name="date_of_birth" type="date" class="form-control"
                value="<?= e($_POST['date_of_birth'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Gender</label>
            <select name="gender" class="form-select">
                <option value="">—</option>
                <?php foreach (['FEMALE', 'MALE', 'OTHER'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($_POST['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label small">Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?= e($_POST['notes'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div class="alert alert-light border small mb-0">
                <i class="fa-solid fa-circle-info me-1"></i>
                On save the system will:
                <ul class="mb-0 mt-1">
                    <li>Generate a permanent member code (<code>CWA-000001</code> format).</li>
                    <li>Record the initial jumuiya placement in member history.</li>
                    <li>Create the annual registration obligation for the year of the join date.</li>
                    <li>Create a welfare obligation <em>only if</em> the selected center has welfare enabled.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Member
        </button>
    </div>
</form>


<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        const preJumuiya = <?= json_encode($_POST['jumuiya_id'] ?? '') ?>;

        function loadJumuiyas() {
            const cid = $('#center_id').val();
            const $j = $('#jumuiya_id').empty().append('<option value="">— select —</option>');
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