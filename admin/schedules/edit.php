<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

$year = (int)($_GET['year'] ?? 0);
$type = strtoupper(trim($_GET['type'] ?? ''));

if ($year <= 0 || $type === '') {
    http_response_code(400);
    exit('year and type are required');
}

$fy = FinancialYear::byYear($year);
if (!$fy) {
    http_response_code(404);
    exit('Financial year does not exist');
}

$state = schedule_edit_state($year, $type);
$errors = [];

// Load existing schedule
$existing = Db::all(
    "SELECT component, month, required_amount
       FROM contribution_schedules
      WHERE year = :y AND contribution_type = :t",
    ['y' => $year, 't' => $type]
);
$matrix = [];
foreach ($existing as $e) {
    $matrix[$e['component']][(int)$e['month']] = (float)$e['required_amount'];
}

// Which components make sense for each type?
$componentsByType = [
    'REGISTRATION' => ['REGISTRATION'],
    'WELFARE'      => ['WELFARE'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    if (!$state['editable']) {
        $errors[] = 'Schedule is not editable: ' . $state['reason'];
    }

    // Expect payload: amounts[COMPONENT][1..12]
    $amounts = $_POST['amounts'] ?? [];
    if (!is_array($amounts) || !$amounts) $errors[] = 'No amounts submitted.';

    if (!$errors) {
        Db::begin();
        try {
            foreach ($amounts as $component => $months) {
                $component = strtoupper(trim($component));
                if (!preg_match('/^[A-Z_]+$/', $component)) continue;

                // Normalize: remove existing, re-insert only non-zero
                Db::q(
                    "DELETE FROM contribution_schedules
                      WHERE year = :y AND contribution_type = :t AND component = :c",
                    ['y' => $year, 't' => $type, 'c' => $component]
                );

                for ($m = 1; $m <= 12; $m++) {
                    $raw = $months[$m] ?? 0;
                    $v   = is_numeric($raw) ? round((float)$raw, 2) : 0.0;
                    if ($v <= 0) continue;
                    Db::insert('contribution_schedules', [
                        'year'              => $year,
                        'contribution_type' => $type,
                        'component'         => $component,
                        'month'             => $m,
                        'required_amount'   => $v,
                    ]);
                }
            }

            Audit::log(
                'UPDATE',
                'contribution_schedules',
                null,
                null,
                ['year' => $year, 'type' => $type]
            );

            Db::commit();
            header('Location: index.php?year=' . $year);
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = 'Save failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Edit Schedule · {$type} · {$year}";
require __DIR__ . '/../../templates/layout/header.php';

// Default components to render
$renderComponents = $componentsByType[$type] ?? array_keys($matrix);
if (!$renderComponents) $renderComponents = ['REGISTRATION'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fa-solid fa-pen me-2"></i>
        <?= e($type) ?> Schedule — <?= (int)$year ?>
    </h4>
    <a href="index.php?year=<?= (int)$year ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$state['editable']): ?>
    <div class="alert alert-warning">
        <i class="fa-solid fa-lock me-1"></i> <?= e($state['reason']) ?>
        <a href="unlock.php?year=<?= (int)$year ?>&type=<?= urlencode($type) ?>" class="alert-link ms-2">
            <i class="fa-solid fa-unlock me-1"></i>Unlock schedule
        </a>
    </div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <?php foreach ($renderComponents as $component): ?>
        <?php
        $vals = [];
        for ($m = 1; $m <= 12; $m++) $vals[$m] = $matrix[$component][$m] ?? 0;
        $total = array_sum($vals);
        ?>
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white">
                <strong><?= e($component) ?></strong>
                <span class="text-muted small ms-2">Total: <span class="js-total fw-bold"><?= number_format($total, 2) ?></span></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" style="min-width:900px">
                        <thead class="table-light text-center">
                            <tr>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <th class="small"><?= date('M', mktime(0, 0, 0, $m, 1)) ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="p-1" style="min-width:70px">
                                        <input type="number" step="0.01" min="0"
                                            class="form-control form-control-sm text-center js-sched"
                                            name="amounts[<?= e($component) ?>][<?= $m ?>]"
                                            value="<?= $vals[$m] > 0 ? number_format($vals[$m], 2, '.', '') : '' ?>"
                                            placeholder="0"
                                            <?= !$state['editable'] ? 'disabled' : '' ?>>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary" <?= !$state['editable'] ? 'disabled' : '' ?>>
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Schedule
        </button>
    </div>
</form>

<script>
    $(function() {
        function recount() {
            $('.js-total').each(function(i) {
                // Only one component per card for now; keep it simple.
            });
            // Simpler: recompute per card
            $('.card').each(function() {
                const $card = $(this);
                const $rows = $card.find('input.js-sched');
                if (!$rows.length) return;
                let total = 0;
                $rows.each(function() {
                    total += parseFloat(this.value || 0);
                });
                $card.find('.js-total').text(total.toFixed(2));
            });
        }
        $(document).on('input', 'input.js-sched', recount);
        recount();
    });
</script>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>