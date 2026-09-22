<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'POST required'], 405);
csrf_check($_POST['csrf'] ?? null);

$memberId   = (int)($_POST['member_id'] ?? 0);
$year       = (int)($_POST['year'] ?? current_year());
$paymentDate = trim($_POST['payment_date'] ?? '');
$amount     = (float)($_POST['amount'] ?? 0);
$type       = strtoupper(trim($_POST['type'] ?? ''));
$method     = strtoupper(trim($_POST['method'] ?? 'CASH'));
$reference  = trim($_POST['reference'] ?? '') ?: null;
$notes      = trim($_POST['notes'] ?? '') ?: null;
$mode       = strtolower(trim($_POST['mode'] ?? 'auto')); // auto | manual

try {
    if ($memberId <= 0) throw new RuntimeException('Member is required.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)) throw new RuntimeException('Payment date is invalid.');
    if ($amount <= 0) throw new RuntimeException('Amount must be positive.');
    if (!in_array($type, ['REGISTRATION', 'WELFARE', 'OTHER'], true)) throw new RuntimeException('Invalid contribution type.');
    if (!in_array($method, ['CASH', 'MPESA', 'BANK', 'CHEQUE', 'OTHER'], true)) throw new RuntimeException('Invalid payment method.');

    $plan = null;

    if ($mode === 'manual') {
        $rawLines = $_POST['lines'] ?? [];
        if (!is_array($rawLines) || !$rawLines) throw new RuntimeException('Manual allocation requires at least one line.');

        // Build the acceptable set of (component, month) for this member/year/type
        $allowed = [];
        if ($type === 'OTHER') {
            $allowed['OTHER|'] = true;
        } else {
            $structure = AllocationEngine::outstandingStructure($memberId, $year, $type);
            foreach ($structure['lines'] as $l) {
                $k = $l['component'] . '|' . ($l['month'] ?? '');
                $allowed[$k] = true;
            }
        }

        $manual = [];
        $sum    = 0.0;
        foreach ($rawLines as $l) {
            $component = strtoupper(trim($l['component'] ?? ''));
            $month     = isset($l['month']) && $l['month'] !== '' ? (int)$l['month'] : null;
            $amt       = round((float)($l['amount'] ?? 0), 2);
            if ($amt < 0) throw new RuntimeException('Manual allocation cannot be negative.');
            if ($amt == 0.0) continue;

            $k = $component . '|' . ($month ?? '');
            if (!isset($allowed[$k])) {
                throw new RuntimeException("Manual line does not match an outstanding item: {$k}.");
            }
            $sum += $amt;
            $manual[] = [
                'component' => $component,
                'month'     => $month,
                'amount'    => $amt,
                'method'    => 'MANUAL',
            ];
        }
        $sum = round($sum, 2);
        $unallocated = round($amount - $sum, 2);
        if ($unallocated < 0) throw new RuntimeException('Manual allocations exceed the payment amount.');

        if ($unallocated > 0) {
            $manual[] = [
                'component' => 'ADVANCE',
                'month'     => null,
                'amount'    => $unallocated,
                'method'    => 'MANUAL',
                'notes'     => 'Unallocated remainder to advance',
            ];
        }

        $plan = [
            'allocations'     => $manual,
            'advance_used'    => 0.0,
            'advance_created' => $unallocated > 0 ? $unallocated : 0.0,
            'unallocated'     => $unallocated > 0 ? $unallocated : 0.0,
            'summary'         => ['paid_before' => 0, 'due' => 0, 'paid_after' => 0],
        ];
    } else {
        $plan = AllocationEngine::plan($memberId, $year, $type, $amount);
    }

    $paymentId = Payment::createWithPlan(
        $memberId,
        $year,
        $paymentDate,
        $amount,
        $type,
        $method,
        $reference,
        $notes,
        $plan,
        Auth::id()
    );

    $payment = Payment::find($paymentId);
    json_out(['ok' => true, 'payment_id' => $paymentId, 'receipt_no' => $payment['receipt_no']]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
