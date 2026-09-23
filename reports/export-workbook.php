<?php
require __DIR__ . '/../includes/auth.php';
require_permission('report.export');

$years   = FinancialYear::all();
$centers = Db::all("SELECT id, name FROM centers ORDER BY name");

$sheetOptions = [
    'dashboard'     => 'Dashboard (KPI summary)',
    'reg_matrix'    => 'Registration Matrix',
    'wel_matrix'    => 'Welfare Matrix',
    'reg_arrears'   => 'Registration Arrears',
    'wel_arrears'   => 'Welfare Arrears',
    'center_report' => 'By Jumuiya (center report)',
    'parish_report' => 'By Center (parish report)',
    'payments'      => 'Payments list',
    'members'       => 'Members list',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $year     = (int)($_POST['year'] ?? 0);
    $centerId = (int)($_POST['center_id'] ?? 0) ?: null;
    $sheets   = $_POST['sheets'] ?? [];

    if ($year <= 0) {
        http_response_code(400);
        exit('Pick a year.');
    }
    if (!is_array($sheets) || !$sheets) {
        http_response_code(400);
        exit('Pick at least one sheet.');
    }

    // Whitelist
    $sheets = array_values(array_intersect(array_keys($sheetOptions), $sheets));
    if (!$sheets) {
        http_response_code(400);
        exit('No valid sheets selected.');
    }

    // Ensure PhpSpreadsheet is available
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        http_response_code(500);
        exit('Composer autoloader not found. Run: composer require phpoffice/phpspreadsheet');
    }
    require_once $autoload;
    require_once __DIR__ . '/../classes/Report.php';
    require_once __DIR__ . '/../classes/Member.php';
    require_once __DIR__ . '/../classes/Workbook.php';

    Audit::log('EXPORT', 'workbook', null, null, [
        'year'    => $year,
        'center'  => $centerId,
        'sheets'  => $sheets,
    ]);

    Workbook::stream([
        'year'      => $year,
        'center_id' => $centerId,
        'sheets'    => $sheets,
    ]);
    exit;
}

$pageTitle = 'Export Workbook';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-file-excel me-2"></i>Export Workbook</h4>
    <a href="registration-matrix.php" class="btn btn-outline-secondary btn-sm">Back to reports</a>
</div>

<div class="alert alert-light border small">
    <i class="fa-solid fa-circle-info me-1"></i>
    Generates a single <strong>.xlsx</strong> file with one sheet per selected report.
    Numbers are computed live — the same engine that powers the on-screen reports.
</div>

<form method="post" class="card shadow-sm border-0">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="card-body">

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label small">Year *</label>
                <select name="year" class="form-select" required>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['year'] ?>" <?= (int)$y['year'] === current_year() ? 'selected' : '' ?>>
                            <?= (int)$y['year'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Center (optional)</label>
                <select name="center_id" class="form-select">
                    <option value="">All centers</option>
                    <?php foreach ($centers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    Leave blank for the whole parish. Filter narrows every sheet.
                </div>
            </div>
        </div>

        <label class="form-label small mb-2">Sheets to include</label>
        <div class="row g-2">
            <?php foreach ($sheetOptions as $key => $label): ?>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sheets[]"
                            id="sh_<?= e($key) ?>" value="<?= e($key) ?>" checked>
                        <label class="form-check-label small" for="sh_<?= e($key) ?>"><?= e($label) ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="selectAll">Select all</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="selectNone">Select none</button>
        </div>

    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Generate workbook
        </button>
    </div>
</form>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        $('#selectAll').on('click', () => $('input[name="sheets[]"]').prop('checked', true));
        $('#selectNone').on('click', () => $('input[name="sheets[]"]').prop('checked', false));
    });
</script>