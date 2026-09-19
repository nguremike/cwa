<?php
require __DIR__ . '/../includes/auth.php';
require_permission('member.transfer');

$id = (int)($_GET['id'] ?? 0);
$m  = Member::find($id);
if (!$m) {
    http_response_code(404);
    exit('Member not found');
}

$centers = Db::all("SELECT id, name FROM centers WHERE status='ACTIVE' ORDER BY name");
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $newJumuiya = (int)($_POST['jumuiya_id'] ?? 0);
    $effDate    = trim($_POST['effective_date'] ?? '');
    $reason     = trim($_POST['reason'] ?? '');

    if ($newJumuiya <= 0) $errors[] = 'Select the destination jumuiya.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $effDate)) $errors[] = 'Effective date is required.';
    if ($reason === '') $errors[] = 'Reason is required.';

    if (!$errors) {
        try {
            Member::transfer($id, $newJumuiya, $effDate, $reason, Auth::id());
            header('Location: view.php?id=' . $id . '&ok=transferred');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Transfer Member';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-right-left me-2"></i>Transfer Member</h4>
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<div class="alert alert-light border small">
    <strong><?= e($m['full_name']) ?></strong> (<?= e($m['member_code']) ?>) is currently in
    <strong><?= e($m['center_name']) ?> / <?= e($m['jumuiya_name']) ?></strong>.
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
            <label class="form-label small">New center</label>
            <select id="center_id" class="form-select">
                <option value="">— select —</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">New jumuiya *</label>
            <select name="jumuiya_id" id="jumuiya_id" class="form-select" required>
                <option value="">— select center first —</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Effective date *</label>
            <input name="effective_date" type="date" class="form-control" required
                value="<?= e($_POST['effective_date'] ?? date('Y-m-d')) ?>">
        </div>

        <div class="col-12">
            <label class="form-label small">Reason *</label>
            <input name="reason" class="form-control" required
                value="<?= e($_POST['reason'] ?? '') ?>"
                placeholder="e.g. Relocated to a different neighborhood">
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-warning">
            <i class="fa-solid fa-right-left me-1"></i> Transfer Member
        </button>
    </div>
</form>


<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        $('#center_id').on('change', function() {
            const cid = this.value;
            const $j = $('#jumuiya_id').empty().append('<option value="">— select —</option>');
            if (!cid) return;
            api('/api/jumuiyas.php', {
                center_id: cid
            }).done(res => {
                (res.data || []).forEach(j => $j.append(`<option value="${j.id}">${j.name}</option>`));
            });
        });
    });
</script>