<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.view');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'POST required'], 405);
csrf_check($_POST['csrf'] ?? null);

$memberId = (int)($_POST['member_id'] ?? 0);
$year     = (int)($_POST['year'] ?? current_year());
$type     = strtoupper(trim($_POST['type'] ?? ''));
$paymentId = (int)($_POST['payment_id'] ?? 0); // for correction mode

try {
    if ($memberId <= 0) throw new RuntimeException('Select a member.');
    if (!in_array($type, ['REGISTRATION', 'WELFARE', 'OTHER'], true)) throw new RuntimeException('Select a contribution type.');

    if ($type === 'OTHER') {
        json_out(['ok' => true, 'data' => [
            'lines' => [[
                'component' => 'OTHER',
                'month' => null,
                'required' => 0,
                'paid' => 0,
                'outstanding' => 0,
                'label' => 'Other (free amount)',
            ]],
            'advance' => 0.0,
            'existing_alloc' => [],
        ]]);
    }

    $structure = AllocationEngine::outstandingStructure($memberId, $year, $type);

    // If correcting an existing payment, add its current allocations to "outstanding"
    // so the user sees what is being re-split.
    $existingAlloc = [];
    if ($paymentId > 0) {
        $rows = Db::all(
            "SELECT component, month, amount
               FROM payment_allocations
              WHERE payment_id = :p AND amount > 0 AND allocation_method <> 'REVERSAL'
              ORDER BY id",
            ['p' => $paymentId]
        );
        $existingAlloc = $rows;
    }

    // Human-friendly labels
    foreach ($structure['lines'] as &$l) {
        $l['label'] = match ($l['component']) {
            'RENEWAL'      => 'Renewal',
            'CARD'         => 'Card fee',
            'REGISTRATION' => 'Registration ' . date('M', mktime(0, 0, 0, (int)$l['month'], 1)),
            'WELFARE'      => 'Welfare '      . date('M', mktime(0, 0, 0, (int)$l['month'], 1)),
            'OTHER'        => 'Other',
            default        => $l['component'],
        };
    }
    unset($l);

    $advance = (float)(Db::one(
        "SELECT amount FROM member_advances
          WHERE member_id = :m AND year = :y AND contribution_type = :t",
        ['m' => $memberId, 'y' => $year, 't' => $type]
    )['amount'] ?? 0.0);

    json_out(['ok' => true, 'data' => [
        'lines'          => $structure['lines'],
        'advance'        => $advance,
        'existing_alloc' => $existingAlloc,
    ]]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
