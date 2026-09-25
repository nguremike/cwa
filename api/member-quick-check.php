<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.view');
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../classes/Report.php';

$memberId = (int)($_GET['member_id'] ?? 0);
$type     = strtoupper(trim($_GET['type'] ?? ''));
$year     = (int)($_GET['year'] ?? 0) ?: current_year();

if ($memberId <= 0) json_out(['ok' => false, 'error' => 'member_id required'], 400);
if ($type !== '' && !in_array($type, ['REGISTRATION', 'WELFARE', 'OTHER'], true)) $type = '';

// Scope guard
$m = Member::find($memberId);
if (!$m) json_out(['ok' => false, 'error' => 'Member not found'], 404);
if (scope_center_id() && (int)$m['center_id'] !== scope_center_id()) {
    json_out(['ok' => false, 'error' => 'Out of scope'], 403);
}
if (scope_jumuiya_id() && (int)$m['jumuiya_id'] !== scope_jumuiya_id()) {
    json_out(['ok' => false, 'error' => 'Out of scope'], 403);
}

// Per-type summary from the same engine that powers statements and matrices.
$summaries = [];
$typesToCheck = $type !== '' ? [$type] : ['REGISTRATION', 'WELFARE'];
foreach ($typesToCheck as $t) {
    $row = null;
    try {
        $matrix = Report::memberMatrix([
            'year' => $year,
            'type' => $t,
            'q' => $m['member_code'],
        ]);
        foreach ($matrix['rows'] as $r) {
            if ((int)$r['member']['id'] === $memberId) {
                $row = $r;
                break;
            }
        }
    } catch (Throwable $e) {
        // Types not applicable for this member (e.g. welfare not enabled) return null.
    }
    if ($row) {
        $summaries[$t] = [
            'due'     => (float)$row['totals']['due'],
            'paid'    => (float)$row['totals']['paid'],
            'balance' => (float)$row['totals']['balance'],
        ];
    }
}

// Recent payments in this year (limit 10). Filtered by type when one is chosen.
$sql = "SELECT p.id, p.receipt_no, p.payment_date, p.amount, p.payment_type, p.status,
               u.full_name AS entered_by_name
          FROM payments p
          LEFT JOIN users u ON u.id = p.entered_by
         WHERE p.member_id = :m AND p.year = :y";
$params = ['m' => $memberId, 'y' => $year];
if ($type !== '') {
    $sql .= " AND p.payment_type = :t";
    $params['t'] = $type;
}
$sql .= " ORDER BY p.id DESC LIMIT 10";

$recent = Db::all($sql, $params);

// Outstanding lines (only for the selected type; capped for the popup)
$outstanding = [];
if ($type === '' || $type === 'REGISTRATION' || $type === 'WELFARE') {
    $typesForOutstanding = $type !== '' ? [$type] : ['REGISTRATION', 'WELFARE'];
    foreach ($typesForOutstanding as $t) {
        try {
            $s = AllocationEngine::outstandingStructure($memberId, $year, $t);
            foreach ($s['lines'] as $line) {
                if ($line['outstanding'] <= 0) continue;
                $label = $line['component'];
                if (!empty($line['month'])) {
                    $label .= ' · ' . date('M', mktime(0, 0, 0, (int)$line['month'], 1));
                }
                $outstanding[] = [
                    'type'        => $t,
                    'label'       => $label,
                    'outstanding' => (float)$line['outstanding'],
                ];
            }
        } catch (Throwable $e) {
            // Skip types that don't apply
        }
    }
}

// Advance balances
$advances = [];
foreach (['REGISTRATION', 'WELFARE'] as $t) {
    $row = Db::one(
        "SELECT amount FROM member_advances
          WHERE member_id = :m AND year = :y AND contribution_type = :t",
        ['m' => $memberId, 'y' => $year, 't' => $t]
    );
    $amt = (float)($row['amount'] ?? 0);
    if ($amt != 0.0) $advances[$t] = $amt;
}

json_out([
    'ok' => true,
    'member' => [
        'id'          => (int)$m['id'],
        'member_code' => $m['member_code'],
        'full_name'   => $m['full_name'],
        'phone'       => $m['phone'],
        'status'      => $m['status'],
        'jumuiya_name' => $m['jumuiya_name'],
        'center_name' => $m['center_name'],
    ],
    'year'         => $year,
    'type'         => $type,
    'summaries'    => $summaries,
    'recent'       => $recent,
    'outstanding'  => $outstanding,
    'advances'     => $advances,
]);
