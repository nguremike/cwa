<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

$year = (int)($_GET['year'] ?? 0);
$type = strtoupper(trim($_GET['type'] ?? ''));
$errors = [];

if ($year <= 0 || $type === '') {
    http_response_code(400);
    exit('year and type are required');
}
$fy = FinancialYear::byYear($year);
if (!$fy) {
    http_response_code(404);
    exit('Financial year does not exist');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') $errors[] = 'A reason is required for the audit trail.';

    if (!$errors) {
        Db::begin();
        try {
            // Upsert: one unlock per (year, type). Re-unlocking updates it.
            $existing = Db::one(
                "SELECT id FROM contribution_schedule_locks WHERE year = :y AND contribution_type = :t",
                ['y' => $year, 't' => $type]
            );
            if ($existing) {
                Db::update('contribution_schedule_locks', [
                    'unlocked_by' => Auth::id(),
                    'unlocked_at' => date('Y-m-d H:i:s'),
                    'reason'      => $reason,
                ], 'id = :id', ['id' => $existing['id']]);
            } else {
                Db::insert('contribution_schedule_locks', [
                    'year'              => $year,
                    'contribution_type' => $type,
                    'unlocked_by'       => Auth::id(),
                    'reason'            => $reason,
                ]);
            }
            Audit::log('UPDATE', 'contribution_schedule_locks', null, null, [
                'year' => $year,
                'type' => $type,
                'reason' => $reason,
            ]);
            Db::commit();
            header('Location: edit.php?year=' . $year . '&type=' . urlencode($type));
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Unlock Schedule';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fa-solid fa-unlock me-2"></i>
        Unlock <?= e($type) ?> Schedule — <?= (int)$year ?>
    </h4>
    <a href="index.php?year=<?= (int)$year ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<div class="alert alert-warning">
    <i class="fa-solid fa-triangle-exclamation me-1"></i>
    Payments already exist for <strong><?= e($type) ?> <?= (int)$year ?></strong>.
    Editing the schedule after payments have been recorded can affect arrears and reports.
    <br><span class="small">Unlocking is per (year, type) and is written to the audit log with your reason.</span>
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
    <div class="card-body">
        <label class="form-label small">Reason for unlocking *</label>
        <textarea name="reason" class="form-control" rows="3" required
            placeholder="e.g. December figure corrected from 70 to 80 after committee approval 2026-03-14"></textarea>
    </div>
    <div class="card-footer bg-white text-end">
        <a href="index.php?year=<?= (int)$year ?>" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-warning">
            <i class="fa-solid fa-unlock me-1"></i> Unlock Schedule
        </button>
    </div>
</form>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>