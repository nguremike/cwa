<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.view');

$rows = Db::all(
    "SELECT v.id, v.payment_id, v.reason, v.voided_at,
            u.full_name AS uname,
            p.receipt_no, p.payment_date, p.amount, p.payment_type,
            m.full_name AS member_name, m.member_code,
            c.name AS center_name, j.name AS jumuiya_name
       FROM payment_voids v
       JOIN payments p ON p.id = v.payment_id
       JOIN members  m ON m.id = p.member_id
       JOIN jumuiyas j ON j.id = m.jumuiya_id
       JOIN centers  c ON c.id = j.center_id
       LEFT JOIN users u ON u.id = v.voided_by
      ORDER BY v.id DESC
      LIMIT 500"
);

$pageTitle = 'Voided Payments';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-ban me-2"></i>Voided Payments</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Payments</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table id="tblVoids" class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>When</th>
                    <th>Receipt</th>
                    <th>Member</th>
                    <th>Jumuiya</th>
                    <th>Type</th>
                    <th class="text-end">Amount</th>
                    <th>Reason</th>
                    <th>By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="small"><?= e($r['voided_at']) ?></td>
                        <td class="small"><span class="badge bg-light text-dark border"><?= e($r['receipt_no']) ?></span></td>
                        <td class="small"><?= e($r['member_name']) ?><div class="text-muted"><?= e($r['member_code']) ?></div>
                        </td>
                        <td class="small"><?= e($r['jumuiya_name']) ?><div class="text-muted"><?= e($r['center_name']) ?></div>
                        </td>
                        <td class="small"><span class="badge bg-info text-dark"><?= e($r['payment_type']) ?></span></td>
                        <td class="text-end small"><?= number_format((float)$r['amount'], 2) ?></td>
                        <td class="small"><?= e($r['reason']) ?></td>
                        <td class="small"><?= e($r['uname'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="receipt.php?id=<?= (int)$r['payment_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-receipt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../templates/layout/footer.php'; ?>


<script>
    $(function() {
        $('#tblVoids').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 50,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });
    });
</script>