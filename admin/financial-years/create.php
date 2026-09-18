<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $year       = (int)($_POST['year'] ?? 0);
    $start      = trim($_POST['start_date'] ?? '');
    $end        = trim($_POST['end_date']   ?? '');
    $makeCurrent = isset($_POST['make_current']);

    if ($year < 2000 || $year > 2100) $errors[] = 'Year must be between 2000 and 2100.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $errors[] = 'Start date is invalid.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $errors[] = 'End date is invalid.';
    if (!$errors && $start >= $end)                   $errors[] = 'Start date must be before end date.';
    if (!$errors && FinancialYear::exists($year))     $errors[] = "Year {$year} already exists.";

    if (!$errors) {
        try {
            FinancialYear::create($year, $start, $end, $makeCurrent);
            header('Location: index.php?ok=created');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Save failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'New Financial Year';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-plus me-2"></i>New Financial Year</h4>
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
                value="<?= e($_POST['year'] ?? (date('Y') + 1)) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Start date *</label>
            <input name="start_date" type="date" class="form-control" required
                value="<?= e($_POST['start_date'] ?? (date('Y') + 1) . '-01-01') ?>">
        </div>
        <div class="col-md-5">
            <label class="form-label small">End date *</label>
            <input name="end_date" type="date" class="form-control" required
                value="<?= e($_POST['end_date'] ?? (date('Y') + 1) . '-12-31') ?>">
        </div>

        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="make_current" id="mc"
                    <?= !empty($_POST['make_current']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="mc">Make this the current financial year</label>
            </div>
        </div>

        <div class="col-12">
            <div class="alert alert-light border small mb-0">
                <i class="fa-solid fa-circle-info me-1"></i>
                A newly created year starts empty. Add contribution schedules from
                <a href="<?= e($config['app']['url']) ?>/admin/schedules/">Contribution Schedules</a>
                after saving — or use <em>Close &amp; Roll Forward</em> on the previous year to copy them automatically.
            </div>
        </div>
    </div>

    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Create Year
        </button>
    </div>
</form>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>