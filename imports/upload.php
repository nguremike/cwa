<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.create');

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) require_once $autoload;

use PhpOffice\PhpSpreadsheet\IOFactory;

$errors  = [];
$preview = null;
$type    = strtoupper($_POST['type'] ?? $_GET['type'] ?? '');
$year    = current_year();

$stageDir = __DIR__ . '/../storage/imports';
if (!is_dir($stageDir)) @mkdir($stageDir, 0775, true);

// ---------------- POST: parse and preview, or confirm -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    if (!empty($_POST['confirm']) && !empty($_SESSION['import_stage'])) {
        // CONFIRM: run the importer
        require_once __DIR__ . '/../classes/ImportRunner.php';
        try {
            $stage = $_SESSION['import_stage'];
            $result = ImportRunner::run($stage);
            unset($_SESSION['import_stage']);
            Audit::log('EXPORT', 'import_batches', $result['batch_id'], null, [
                'type' => $stage['type'],
                'year' => $stage['year'],
                'imported' => $result['imported'],
                'skipped'  => $result['skipped'],
                'total'    => $result['total_amount'],
            ]);
            header('Location: batch.php?id=' . $result['batch_id']);
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Import failed: ' . $e->getMessage();
        }
    }

    if (!empty($_FILES['file']['tmp_name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        try {
            if (!in_array($type, ['REGISTRATION', 'WELFARE'], true)) {
                throw new RuntimeException('Pick a contribution type before uploading.');
            }
            if (!is_file($autoload)) {
                throw new RuntimeException('Composer autoloader not found.');
            }

            $original = $_FILES['file']['name'];
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls'], true)) {
                throw new RuntimeException('Only .xlsx or .xls files are accepted.');
            }

            // Save a copy for audit
            $stored = $stageDir . '/upload-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $stored)) {
                throw new RuntimeException('Could not store uploaded file.');
            }

            // Parse
            $reader = IOFactory::createReaderForFile($stored);
            $reader->setReadDataOnly(true);
            $book = $reader->load($stored);
            $sheet = $book->getSheetByName('Data');
            if (!$sheet) throw new RuntimeException('Sheet "Data" not found in the file.');

            $rows = [];
            $highestRow = $sheet->getHighestDataRow();
            for ($r = 2; $r <= $highestRow; $r++) {   // row 1 = headers, row 2 = sample
                $code    = trim((string)$sheet->getCell("A{$r}")->getValue());
                $dateRaw = $sheet->getCell("B{$r}")->getValue();
                $amount  = (float)$sheet->getCell("C{$r}")->getValue();
                $method  = strtoupper(trim((string)$sheet->getCell("D{$r}")->getValue())) ?: 'CASH';
                $ref     = trim((string)$sheet->getCell("E{$r}")->getValue());
                $notes   = trim((string)$sheet->getCell("F{$r}")->getValue());

                // Skip fully empty rows
                if ($code === '' && $amount == 0.0 && $ref === '') continue;

                // $date = self::normalizeDate($dateRaw);
                $date = normalizeDate($dateRaw);

                $rows[] = [
                    'row'    => $r,
                    'code'   => $code,
                    'date'   => $date,
                    'amount' => $amount,
                    'method' => $method,
                    'ref'    => $ref,
                    'notes'  => $notes,
                ];
            }
            if (!$rows) throw new RuntimeException('No data rows found.');

            // Validate each row
            // $staged = self::validateRows($rows, $type, $year);
            $staged = validateRows($rows, $type, $year);


            // Stash in session for confirm
            $_SESSION['import_stage'] = [
                'type'      => $type,
                'year'      => $year,
                'file'      => $original,
                'stored'    => $stored,
                'rows'      => $staged['valid'],
                'skipped'   => $staged['skipped'],
            ];

            $preview = $staged;
            $preview['file'] = $original;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    } else {
        if (isset($_FILES['file']['error']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Upload error code ' . (int)$_FILES['file']['error'];
        }
    }
}

// ---------------- helper: date normalize -----------------------------
function normalizeDate($raw): ?string
{
    if ($raw === null || $raw === '') return null;
    if (is_numeric($raw)) {
        // Excel serial
        $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$raw);
        return $dt->format('Y-m-d');
    }
    $ts = strtotime((string)$raw);
    return $ts ? date('Y-m-d', $ts) : null;
}

// ---------------- helper: row validation -----------------------------
function validateRows(array $rows, string $type, int $year): array
{
    $valid   = [];
    $skipped = [];
    $seen    = [];

    $members = [];
    foreach (Db::all("SELECT id, member_code, full_name, status FROM members") as $m) {
        $members[strtoupper($m['member_code'])] = $m;
    }

    foreach ($rows as $r) {
        $issues = [];

        // Member
        $codeUpper = strtoupper($r['code']);
        if ($codeUpper === '') {
            $issues[] = 'Member Code is empty.';
        } elseif (!isset($members[$codeUpper])) {
            $issues[] = "Member code '{$r['code']}' not found.";
        } elseif ($members[$codeUpper]['status'] !== 'ACTIVE') {
            $issues[] = "Member {$r['code']} is {$members[$codeUpper]['status']} (not ACTIVE).";
        }

        // if ((int)$members[$codeUpper]['center_id'] !== scope_center_id()) {
        //     $issues[] = 'Member is outside your scope.';
        // }

        // Date
        if (!$r['date']) {
            $issues[] = 'Payment Date is missing or invalid.';
        } elseif ((int)substr($r['date'], 0, 4) !== $year) {
            $issues[] = "Payment Date {$r['date']} is not in the current financial year ({$year}).";
        }

        // Amount
        if ($r['amount'] <= 0) {
            $issues[] = 'Amount must be positive.';
        }

        // Method
        if (!in_array($r['method'], ['CASH', 'MPESA', 'BANK', 'CHEQUE', 'OTHER'], true)) {
            $issues[] = "Method '{$r['method']}' is not one of CASH/MPESA/BANK/CHEQUE/OTHER.";
        }

        // Duplicate within file
        $dupKey = $codeUpper . '|' . $r['date'] . '|' . number_format($r['amount'], 2, '.', '') . '|' . strtoupper($r['ref']);
        if (isset($seen[$dupKey])) {
            $issues[] = "Duplicate of row {$seen[$dupKey]} in this same file.";
        } else {
            $seen[$dupKey] = $r['row'];
        }

        // Duplicate against existing payments
        if (!$issues && isset($members[$codeUpper])) {
            $existing = Db::one(
                "SELECT id, receipt_no FROM payments
                  WHERE member_id = :m AND payment_date = :d AND amount = :a
                    AND COALESCE(reference_no,'') = :ref
                    AND status = 'ACTIVE'
                  LIMIT 1",
                [
                    'm'   => (int)$members[$codeUpper]['id'],
                    'd'   => $r['date'],
                    'a'   => $r['amount'],
                    'ref' => $r['ref'],
                ]
            );
            if ($existing) {
                $issues[] = "Already imported (receipt {$existing['receipt_no']}).";
            }
        }

        // Build the allocation preview for this row (read-only)
        $planPreview = null;
        if (!$issues) {
            try {
                $plan = AllocationEngine::plan((int)$members[$codeUpper]['id'], $year, $type, $r['amount']);
                $planPreview = $plan;
            } catch (Throwable $e) {
                $issues[] = 'Allocation failed: ' . $e->getMessage();
            }
        }

        $r['member_id']   = isset($members[$codeUpper]) ? (int)$members[$codeUpper]['id'] : null;
        $r['member_name'] = $members[$codeUpper]['full_name'] ?? '';
        $r['issues']      = $issues;
        $r['plan']        = $planPreview;

        if ($issues) $skipped[] = $r;
        else         $valid[]   = $r;
    }

    $totalAmount = 0.0;
    foreach ($valid as $v) $totalAmount += (float)$v['amount'];

    return [
        'type'          => $type,
        'year'          => $year,
        'valid'         => $valid,
        'skipped'       => $skipped,
        'valid_count'   => count($valid),
        'skipped_count' => count($skipped),
        'total_amount'  => round($totalAmount, 2),
    ];
}

// ---------------- render --------------------------------------------
$pageTitle = 'Import Payments';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-file-import me-2"></i>Import Payments</h4>
    <a href="batches.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-history me-1"></i> Import history
    </a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($preview): ?>
    <!-- Preview stage -->
    <div class="alert alert-light border small">
        <i class="fa-solid fa-circle-info me-1"></i>
        File: <strong><?= e($preview['file']) ?></strong> ·
        <?= (int)$preview['valid_count'] ?> valid ·
        <?= (int)$preview['skipped_count'] ?> skipped ·
        Total to import: <strong><?= number_format($preview['total_amount'], 2) ?></strong>
        for <strong><?= e($type) ?></strong> · Year <strong><?= (int)$year ?></strong>
    </div>

    <?php if ($preview['skipped_count'] > 0): ?>
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white">
                <strong class="text-danger">Skipped rows (<?= (int)$preview['skipped_count'] ?>)</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Row</th>
                            <th>Member Code</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview['skipped'] as $s): ?>
                            <tr>
                                <td class="small"><?= (int)$s['row'] ?></td>
                                <td class="small"><?= e($s['code']) ?></td>
                                <td class="small"><?= e($s['date'] ?? '') ?></td>
                                <td class="text-end small"><?= number_format((float)$s['amount'], 2) ?></td>
                                <td class="small text-danger"><?= e(implode(' ', $s['issues'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($preview['valid_count'] > 0): ?>
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white">
                <strong class="text-success">Valid rows (<?= (int)$preview['valid_count'] ?>)</strong>
            </div>
            <div class="card-body p-0" style="max-height:420px;overflow:auto">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Row</th>
                            <th>Member</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Preview allocation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview['valid'] as $v): ?>
                            <tr>
                                <td class="small"><?= (int)$v['row'] ?></td>
                                <td class="small">
                                    <?= e($v['member_name']) ?>
                                    <div class="text-muted"><?= e($v['code']) ?></div>
                                </td>
                                <td class="small"><?= e($v['date']) ?></td>
                                <td class="text-end small"><?= number_format((float)$v['amount'], 2) ?></td>
                                <td class="small">
                                    <?php if (!empty($v['plan']['allocations'])): ?>
                                        <?php foreach ($v['plan']['allocations'] as $a):
                                            $label = $a['component'];
                                            if (!empty($a['month'])) {
                                                $label .= ' ' . date('M', mktime(0, 0, 0, (int)$a['month'], 1));
                                            }
                                        ?>
                                            <span class="badge bg-light text-dark border me-1 mb-1">
                                                <?= e($label) ?> · <?= number_format((float)$a['amount'], 2) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (!empty($v['plan']['advance_created']) && $v['plan']['advance_created'] > 0): ?>
                                            <span class="badge bg-warning text-dark me-1 mb-1">
                                                Advance <?= number_format((float)$v['plan']['advance_created'], 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No allocation</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="post" class="card shadow-sm border-0">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="confirm" value="1">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Import writes one payment per valid row, using the same allocation engine as manual entry.
                    Skipped rows are ignored.
                </div>
                <div>
                    <a href="upload.php" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-primary">
                        <i class="fa-solid fa-check me-1"></i>
                        Confirm import (<?= (int)$preview['valid_count'] ?>)
                    </button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            No valid rows to import. Fix the issues above and re-upload.
        </div>
        <a href="upload.php" class="btn btn-outline-secondary">Back</a>
    <?php endif; ?>

<?php else: ?>
    <!-- Upload stage -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small">Contribution type *</label>
                    <select id="type" class="form-select" required>
                        <option value="">— select —</option>
                        <option value="REGISTRATION" <?= $type === 'REGISTRATION' ? 'selected' : '' ?>>Registration</option>
                        <option value="WELFARE" <?= $type === 'WELFARE' ? 'selected' : '' ?>>Welfare</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Template</label>
                    <div class="d-flex gap-2">
                        <a href="template.php?type=REGISTRATION" class="btn btn-outline-success">
                            <i class="fa-solid fa-file-excel me-1"></i> Registration template
                        </a>
                        <a href="template.php?type=WELFARE" class="btn btn-outline-info">
                            <i class="fa-solid fa-file-excel me-1"></i> Welfare template
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="card shadow-sm border-0">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="card-body">
            <label class="form-label small">Upload filled template (.xlsx) *</label>
            <input type="file" name="file" accept=".xlsx,.xls" class="form-control" required>
            <div class="form-text">
                Rows are validated against the current year (<?= (int)$year ?>) and the ledger.
                Nothing is written until you confirm the preview.
            </div>
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-primary">
                <i class="fa-solid fa-upload me-1"></i> Parse & preview
            </button>
        </div>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        $('#type').on('change', function() {
            // Reflect type in the URL so refreshes keep the selection
            const url = new URL(window.location.href);
            url.searchParams.set('type', this.value);
            window.history.replaceState({}, '', url);
        });
        // When a form is submitted, ensure the currently selected type travels with it
        $('form').on('submit', function() {
            const t = $('#type').val();
            if (t) {
                if (!this.querySelector('input[name=type]')) {
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'type';
                    i.value = t;
                    this.appendChild(i);
                }
            }
        });
    });
</script>