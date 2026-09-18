<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

$id = (int)($_GET['id'] ?? 0);
$fy = FinancialYear::find($id);
if (!$fy) {
    http_response_code(404);
    exit('Year not found');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $year  = (int)($_POST['year'] ?? 0);
    $start = trim($_POST['start_date'] ?? '');
    $end   = trim($_POST['end_date']   ?? '');

    if ($year < 2000 || $year > 2100) $errors[] = 'Year must be between 2000 and 2100.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $errors[] = 'Start date is invalid.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $errors[] = 'End date is invalid.';
    if (!$errors && $start >= $end)                   $errors[] = 'Start date must be before end date.';
    if (!$errors && FinancialYear::exists($year, $id)) $errors[] = "Another year is already set to {$year}.";

    if (!$errors) {
        try {
            FinancialYear::update($id, $year, $start, $end);
            header('Location: index.php?ok=updated');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    $fy = array_merge($fy, $_POST);
}

$pageTitle = 'Edit Financial Year';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-pen me-2"></i>Edit Financial Year</h4>
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
        <div class="col-md-3">
            <label class="form-label small">Year *</label>
            <input name="year" type="number" min="2000" max="2100" class="form-control" required
                value="<?= e($fy['year']) ?>" <?= $fy['status'] === 'CLOSED' ? 'disabled' : '' ?>>
            <?php if ($fy['status'] === 'CLOSED'): ?>
                <input type="hidden" name="year" value="<?= e($fy['year']) ?>">
                <div class="form-text text-warning">Reopen the year to edit.</div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Start date *</label>
            <input name="start_date" type="date" class="form-control" required
                value="<?= e($fy['start_date']) ?>" <?= $fy['status'] === 'CLOSED' ? 'disabled' : '' ?>>
            <?php if ($fy['status'] === 'CLOSED'): ?>
                <input type="hidden" name="start_date" value="<?= e($fy['start_date']) ?>">
            <?php endif; ?>
        </div>
        <div class="col-md-5">
            <label class="form-label small">End date *</label>
            <input name="end_date" type="date" class="form-control" required
                value="<?= e($fy['end_date']) ?>" <?= $fy['status'] === 'CLOSED' ? 'disabled' : '' ?>>
            <?php if ($fy['status'] === 'CLOSED'): ?>
                <input type="hidden" name="end_date" value="<?= e($fy['end_date']) ?>">
            <?php endif; ?>
        </div>
    </div>

    <div class="card-footer bg-white d-flex justify-content-between">
        <span class="text-muted small align-self-center">
            Status: <span class="badge bg-<?= $fy['status'] === 'OPEN' ? 'success' : 'secondary' ?>"><?= e($fy['status']) ?></span>
            <?= $fy['is_current'] ? '· <span class="badge bg-primary">Current</span>' : '' ?>
        </span>
        <button class="btn btn-primary" <?= $fy['status'] === 'CLOSED' ? 'disabled' : '' ?>>
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
        </button>
    </div>
</form>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>