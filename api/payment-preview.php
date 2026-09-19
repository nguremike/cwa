<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.view');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'POST required'], 405);
csrf_check($_POST['csrf'] ?? null);

$memberId = (int)($_POST['member_id'] ?? 0);
$year     = (int)($_POST['year'] ?? current_year());
$type     = strtoupper(trim($_POST['type'] ?? ''));
$amount   = (float)($_POST['amount'] ?? 0);

try {
    if ($memberId <= 0) throw new RuntimeException('Select a member.');
    if (!in_array($type, ['REGISTRATION', 'WELFARE', 'OTHER'], true)) throw new RuntimeException('Select a contribution type.');
    if ($amount <= 0) throw new RuntimeException('Amount must be positive.');

    $plan = AllocationEngine::plan($memberId, $year, $type, $amount);
    $structure = $type === 'OTHER' ? null : AllocationEngine::outstandingStructure($memberId, $year, $type);

    // Human-friendly label for each line
    foreach ($plan['allocations'] as &$a) {
        $a['label'] = match ($a['component']) {
            'RENEWAL'      => 'Renewal',
            'CARD'         => 'Card fee',
            'REGISTRATION' => 'Registration ' . date('M', mktime(0, 0, 0, (int)$a['month'], 1)),
            'WELFARE'      => 'Welfare '      . date('M', mktime(0, 0, 0, (int)$a['month'], 1)),
            'ADVANCE'      => 'Advance (credit balance)',
            'OTHER'        => 'Other',
            default        => $a['component'],
        };
    }
    unset($a);

    json_out(['ok' => true, 'plan' => $plan, 'structure' => $structure]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 400);
}
