<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.reverse');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'POST required'], 405);
csrf_check($_POST['csrf'] ?? null);

$paymentId = (int)($_POST['payment_id'] ?? 0);
$rawLines  = $_POST['lines'] ?? [];

try {
    if ($paymentId <= 0) throw new RuntimeException('Missing payment id.');
    if (!is_array($rawLines) || !$rawLines) throw new RuntimeException('Provide the new allocation lines.');

    $check = Payment::canCorrect($paymentId);
    if (!$check['ok']) throw new RuntimeException($check['reason']);

    $p = Payment::find($paymentId);
    if (!$p) throw new RuntimeException('Payment not found.');

    $memberId = (int)$p['member_id'];
    $year     = (int)$p['year'];
    $type     = $p['payment_type'];
    $amount   = (float)$p['amount'];

    // Build new allocation set
    $newLines = [];
    $sum = 0.0;
    foreach ($rawLines as $l) {
        $component = strtoupper(trim($l['component'] ?? ''));
        $month     = isset($l['month']) && $l['month'] !== '' ? (int)$l['month'] : null;
        $amt       = round((float)($l['amount'] ?? 0), 2);
        if ($amt < 0) throw new RuntimeException('Allocation lines cannot be negative.');
        if ($amt == 0.0) continue;
        $sum += $amt;
        $newLines[] = ['component' => $component, 'month' => $month, 'amount' => $amt];
    }
    $sum = round($sum, 2);
    $unallocated = round($amount - $sum, 2);
    if ($unallocated < 0) throw new RuntimeException('New allocations exceed the payment amount.');

    // Fetch ONLY the original AUTO allocations. Never mirror a mirror.
    $originals = Db::all(
        "SELECT component, month, amount
           FROM payment_allocations
          WHERE payment_id = :p
            AND amount <> 0
            AND allocation_method = 'AUTO'
          ORDER BY id",
        ['p' => $paymentId]
    );
    if (!$originals) {
        throw new RuntimeException('No original AUTO allocations found to correct. Void instead.');
    }

    // Compute original advance contribution (for bucket adjustment)
    $oldAdvanceContribution = 0.0;
    foreach ($originals as $a) {
        if ($a['component'] === 'ADVANCE' && (float)$a['amount'] > 0) {
            $oldAdvanceContribution += (float)$a['amount'];
        }
    }

    Db::begin();
    try {
        // 1) Write negative mirrors of the ORIGINAL rows only.
        foreach ($originals as $a) {
            Db::insert('payment_allocations', [
                'payment_id'        => $paymentId,
                'member_id'         => $memberId,
                'year'              => $year,
                'contribution_type' => $type,
                'component'         => $a['component'],
                'month'             => $a['month'],
                'amount'            => -1 * (float)$a['amount'],   // always negative
                'allocation_method' => 'ADJUSTMENT',
                'notes'             => 'Correction — reversal of original',
                'created_by'        => Auth::id(),
            ]);
        }

        // 2) Write the new MANUAL allocations.
        foreach ($newLines as $l) {
            Db::insert('payment_allocations', [
                'payment_id'        => $paymentId,
                'member_id'         => $memberId,
                'year'              => $year,
                'contribution_type' => $type,
                'component'         => $l['component'],
                'month'             => $l['month'],
                'amount'            => $l['amount'],
                'allocation_method' => 'MANUAL',
                'notes'             => 'Correction — new split',
                'created_by'        => Auth::id(),
            ]);
        }

        // 3) Overflow goes to advance as an ADJUSTMENT.
        $newAdvanceContribution = 0.0;
        if ($unallocated > 0) {
            Db::insert('payment_allocations', [
                'payment_id'        => $paymentId,
                'member_id'         => $memberId,
                'year'              => $year,
                'contribution_type' => $type,
                'component'         => 'ADVANCE',
                'month'             => null,
                'amount'            => $unallocated,
                'allocation_method' => 'ADJUSTMENT',
                'notes'             => 'Correction — overflow to advance',
                'created_by'        => Auth::id(),
            ]);
            $newAdvanceContribution = $unallocated;
        }

        // 4) Adjust advance bucket by (new − old).
        $delta = round($newAdvanceContribution - $oldAdvanceContribution, 2);
        if (abs($delta) > 0.0001) {
            AllocationEngine::adjustAdvance($memberId, $year, $type, $delta);
        }

        Audit::log(
            'ADJUSTMENT',
            'payments',
            $paymentId,
            ['reason' => 'reallocation'],
            [
                'old_advance' => $oldAdvanceContribution,
                'new_advance' => $newAdvanceContribution,
                'new_sum'     => $sum,
                'unallocated' => $unallocated,
            ]
        );

        Db::commit();
        json_out(['ok' => true]);
    } catch (Throwable $e) {
        Db::rollback();
        throw $e;
    }
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
