<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('payment.reverse');

if (
    !in_array($_SESSION['user']['role_name'] ?? '', ['SUPER_ADMIN', 'PARISH_ADMIN'], true)
    && !user_can('*')
) {
    http_response_code(403);
    exit('Not allowed.');
}

$rows = Db::all(
    "SELECT a.*, p.receipt_no, p.payment_date, p.amount AS payment_amount,
            m.full_name AS member_name, m.member_code,
            u.full_name AS requester_name
       FROM approval_requests a
       JOIN payments p ON p.id = a.payment_id
       JOIN members  m ON m.id = p.member_id
       LEFT JOIN users u ON u.id = a.requested_by
      WHERE a.status = 'PENDING'
      ORDER BY a.id DESC"
);

$pageTitle = 'Approvals';
require __DIR__ . '/../../templates/layout/header.php';
?>
<h4 class="mb-3"><i class="fa-solid fa-clipboard-check me-2"></i>Pending Approvals</h4>

<?php if (!$rows): ?>
    <div class="alert alert-success">No pending approvals.</div>
<?php else: ?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Type</th>
                        <th>Receipt</th>
                        <th>Member</th>
                        <th class="text-end">Amount</th>
                        <th>Reason</th>
                        <th>Requested by</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="small"><?= e($r['requested_at']) ?></td>
                            <td class="small"><span class="badge bg-warning text-dark"><?= e($r['request_type']) ?></span></td>
                            <td class="small"><?= e($r['receipt_no']) ?></td>
                            <td class="small"><?= e($r['member_name']) ?><div class="text-muted"><?= e($r['member_code']) ?></div>
                            </td>
                            <td class="text-end small"><?= number_format((float)$r['amount'], 2) ?></td>
                            <td class="small"><?= e($r['reason']) ?></td>
                            <td class="small"><?= e($r['requester_name'] ?? '—') ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="decide.php?id=<?= (int)$r['id'] ?>">
                                    <i class="fa-solid fa-gavel"></i> Decide
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>