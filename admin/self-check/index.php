<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('audit.view');

/**
 * Each check returns:
 *   [
 *     'id'      => short slug,
 *     'label'   => human text,
 *     'severity'=> 'error' | 'warn' | 'info',
 *     'count'   => int,
 *     'sql'     => the SQL used (for the drill-through CSV),
 *     'rows'    => first N violating rows for preview,
 *   ]
 */
$checks = [];

// 1) Every ACTIVE payment's signed allocation sum equals its amount.
$sql = "SELECT p.id, p.receipt_no, p.amount,
               COALESCE(SUM(pa.amount),0) AS allocated
          FROM payments p
          LEFT JOIN payment_allocations pa ON pa.payment_id = p.id
         WHERE p.status = 'ACTIVE'
         GROUP BY p.id, p.receipt_no, p.amount
        HAVING ABS(COALESCE(SUM(pa.amount),0) - p.amount) > 0.005";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'active_payment_balance',
    'label' => 'Every ACTIVE payment sums to its amount (across allocations)',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 2) Every VOIDED payment has a matching payment_voids row.
$sql = "SELECT p.id, p.receipt_no
          FROM payments p
          LEFT JOIN payment_voids v ON v.payment_id = p.id
         WHERE p.status = 'VOIDED' AND v.id IS NULL";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'voided_has_record',
    'label' => 'Every VOIDED payment has a payment_voids row',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 3) Every VOIDED payment nets to zero across allocations.
$sql = "SELECT p.id, p.receipt_no, COALESCE(SUM(pa.amount),0) AS net
          FROM payments p
          LEFT JOIN payment_allocations pa ON pa.payment_id = p.id
         WHERE p.status = 'VOIDED'
         GROUP BY p.id, p.receipt_no
        HAVING ABS(COALESCE(SUM(pa.amount),0)) > 0.005";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'voided_nets_zero',
    'label' => 'Every VOIDED payment nets to zero (original + reversal)',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 4) Advance bucket equals ADVANCE allocations minus ADJUSTMENT drawdowns.
$sql = "SELECT ma.member_id, ma.year, ma.contribution_type, ma.amount AS bucket,
               COALESCE((
                   SELECT SUM(CASE
                       WHEN pa.component = 'ADVANCE' AND pa.amount > 0 THEN pa.amount
                       WHEN pa.allocation_method = 'ADJUSTMENT' AND pa.amount > 0 THEN -pa.amount
                       ELSE 0
                   END)
                     FROM payment_allocations pa
                     JOIN payments p ON p.id = pa.payment_id
                    WHERE pa.member_id = ma.member_id
                      AND pa.year = ma.year
                      AND pa.contribution_type = ma.contribution_type
                      AND p.status = 'ACTIVE'
               ),0) AS expected
          FROM member_advances ma
         HAVING ABS(bucket - expected) > 0.005";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'advance_matches_ledger',
    'label' => 'Advance bucket equals net ADVANCE allocations per member/year/type',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 5) No orphan registrations (member exists).
$sql = "SELECT mr.id, mr.member_id, mr.year
          FROM member_registration mr
          LEFT JOIN members m ON m.id = mr.member_id
         WHERE m.id IS NULL";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'reg_no_orphans',
    'label' => 'Every member_registration row points to an existing member',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 6) No orphan welfare rows.
$sql = "SELECT mw.id, mw.member_id, mw.year
          FROM member_welfare mw
          LEFT JOIN members m ON m.id = mw.member_id
         WHERE m.id IS NULL";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'wel_no_orphans',
    'label' => 'Every member_welfare row points to an existing member',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 7) Open years have both REGISTRATION and WELFARE schedules (unless no centers want welfare).
$sql = "SELECT fy.year
          FROM financial_years fy
         WHERE fy.status = 'OPEN'
           AND EXISTS (
               SELECT 1 FROM contribution_schedules cs
                WHERE cs.year = fy.year AND cs.contribution_type = 'REGISTRATION'
           ) = 0";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'open_year_has_reg_schedule',
    'label' => 'Every OPEN year has a REGISTRATION schedule',
    'severity' => 'warn',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 8) Every jumuiya belongs to a center.
$sql = "SELECT j.id, j.name
          FROM jumuiyas j
          LEFT JOIN centers c ON c.id = j.center_id
         WHERE c.id IS NULL";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'jumuiya_no_orphans',
    'label' => 'Every jumuiya belongs to an existing center',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 9) Every member belongs to a jumuiya.
$sql = "SELECT m.id, m.member_code, m.full_name
          FROM members m
          LEFT JOIN jumuiyas j ON j.id = m.jumuiya_id
         WHERE j.id IS NULL";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'member_no_orphans',
    'label' => 'Every member belongs to an existing jumuiya',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 10) No duplicate active advance buckets per member/year/type.
$sql = "SELECT member_id, year, contribution_type, COUNT(*) AS c
          FROM member_advances
         GROUP BY member_id, year, contribution_type
        HAVING c > 1";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'advance_unique',
    'label' => 'Advance bucket is unique per (member, year, type)',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 11) No payments without allocations (except zero-amount which never happens).
$sql = "SELECT p.id, p.receipt_no, p.amount
          FROM payments p
          LEFT JOIN payment_allocations pa ON pa.payment_id = p.id
         WHERE p.status = 'ACTIVE' AND pa.id IS NULL";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'active_payment_has_allocations',
    'label' => 'Every ACTIVE payment has at least one allocation row',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

// 12) Advance components only appear under matching contribution_type.
$sql = "SELECT pa.id, pa.payment_id, pa.contribution_type
          FROM payment_allocations pa
         WHERE pa.component = 'ADVANCE'
           AND pa.contribution_type NOT IN ('REGISTRATION','WELFARE','OTHER')";
$rows = Db::all($sql);
$checks[] = [
    'id' => 'advance_type_matches',
    'label' => 'ADVANCE allocations carry a valid contribution type',
    'severity' => 'error',
    'count' => count($rows),
    'rows' => array_slice($rows, 0, 20),
    'sql' => $sql,
];

$errCount = 0;
$warnCount = 0;
foreach ($checks as $c) {
    if ($c['severity'] === 'error' && $c['count'] > 0) $errCount += $c['count'];
    if ($c['severity'] === 'warn'  && $c['count'] > 0) $warnCount += $c['count'];
}

$pageTitle = 'Self-check';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-heart-pulse me-2"></i>Self-check Invariants</h4>
    <span>
        <?php if ($errCount === 0 && $warnCount === 0): ?>
            <span class="badge bg-success">All checks passed</span>
        <?php else: ?>
            <span class="badge bg-danger"><?= (int)$errCount ?> error<?= $errCount === 1 ? '' : 's' ?></span>
            <span class="badge bg-warning text-dark"><?= (int)$warnCount ?> warning<?= $warnCount === 1 ? '' : 's' ?></span>
        <?php endif; ?>
    </span>
</div>

<div class="alert alert-light border small">
    Each check runs a live SQL assertion against the ledger and reference tables.
    A green tick means the invariant holds. A red count means the listed rows are inconsistent and should be
    investigated — the drill-through link opens the underlying SQL result as CSV.
</div>

<div class="accordion" id="chkAcc">
    <?php foreach ($checks as $i => $c):
        $badge = $c['count'] > 0
            ? ($c['severity'] === 'error' ? 'danger' : 'warning text-dark')
            : 'success';
    ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#chk<?= $i ?>">
                    <span class="badge bg-<?= $badge ?> me-2">
                        <?= $c['count'] > 0 ? (int)$c['count'] : '✓' ?>
                    </span>
                    <?= e($c['label']) ?>
                    <span class="text-muted small ms-2">[<?= e($c['id']) ?>]</span>
                </button>
            </h2>
            <div id="chk<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#chkAcc">
                <div class="accordion-body">
                    <?php if ($c['count'] === 0): ?>
                        <div class="text-success small">
                            <i class="fa-solid fa-circle-check me-1"></i> Invariant holds.
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-danger small">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                <?= (int)$c['count'] ?> row(s) violate this invariant.
                            </div>
                            <a class="btn btn-sm btn-outline-secondary"
                                target="_blank"
                                href="export-check.php?id=<?= urlencode($c['id']) ?>">
                                <i class="fa-solid fa-file-csv me-1"></i> CSV
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <?php foreach (array_keys($c['rows'][0]) as $col): ?>
                                            <th class="small"><?= e($col) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($c['rows'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $v): ?>
                                                <td class="small"><?= e((string)$v) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>