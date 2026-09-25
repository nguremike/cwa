<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.view');

$rows = Db::all(
    "SELECT b.*, u.full_name AS uploader, v.full_name AS voider
       FROM import_batches b
       LEFT JOIN users u ON u.id = b.uploaded_by
       LEFT JOIN users v ON v.id = b.voided_by
      ORDER BY b.id DESC
      LIMIT 500"
);

$pageTitle = 'Import Batches';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-file-import me-2"></i>Import Batches</h4>
    <a href="upload.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-upload me-1"></i> New import
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table id="tblBatches" class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Batch</th>
                    <th>When</th>
                    <th>Type</th>
                    <th>Year</th>
                    <th class="text-end">Imported</th>
                    <th class="text-end">Skipped</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Uploader</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="small"><span class="badge bg-light text-dark border"><?= e($r['batch_ref']) ?></span></td>
                        <td class="small"><?= e($r['uploaded_at']) ?></td>
                        <td class="small"><?= e($r['contribution_type']) ?></td>
                        <td class="small"><?= (int)$r['year'] ?></td>
                        <td class="text-end small"><?= (int)$r['rows_imported'] ?></td>
                        <td class="text-end small"><?= (int)$r['rows_skipped'] ?></td>
                        <td class="text-end small"><?= number_format((float)$r['total_amount'], 2) ?></td>
                        <td>
                            <span class="badge bg-<?= $r['status'] === 'POSTED' ? 'success' : 'danger' ?>">
                                <?= e($r['status']) ?>
                            </span>
                        </td>
                        <td class="small"><?= e($r['uploader'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="batch.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-eye"></i>
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
        $('#tblBatches').DataTable({
            order: [
                [1, 'desc']
            ],
            pageLength: 25,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });
    });
</script>