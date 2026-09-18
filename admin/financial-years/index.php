<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.view');

$years = FinancialYear::all();

$pageTitle = 'Financial Years';
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-calendar-days me-2"></i>Financial Years</h4>
    <a href="create.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i> New Year
    </a>
</div>

<?php if (!empty($_GET['ok'])): ?>
    <div class="alert alert-success py-2 small">
        <?= e(match ($_GET['ok']) {
            'created'  => 'Year created.',
            'updated'  => 'Year updated.',
            'closed'   => 'Year closed.',
            'reopened' => 'Year reopened.',
            default    => 'Done.',
        }) ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table id="tblFY" class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Year</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th>Current</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($years as $y): ?>
                    <tr>
                        <td><strong><?= (int)$y['year'] ?></strong></td>
                        <td class="small text-muted"><?= e($y['start_date']) ?></td>
                        <td class="small text-muted"><?= e($y['end_date']) ?></td>
                        <td>
                            <span class="badge bg-<?= $y['status'] === 'OPEN' ? 'success' : 'secondary' ?>">
                                <?= e($y['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($y['is_current']): ?>
                                <span class="badge bg-primary">Current</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="edit.php?id=<?= (int)$y['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <?php if ($y['status'] === 'OPEN'): ?>
                                    <a href="#" class="btn btn-outline-secondary btn-current"
                                        data-id="<?= (int)$y['id'] ?>" title="Mark as current"
                                        <?= $y['is_current'] ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-star"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-warning btn-close-year"
                                        data-id="<?= (int)$y['id'] ?>" data-year="<?= (int)$y['year'] ?>" title="Close year">
                                        <i class="fa-solid fa-lock"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-primary btn-roll"
                                        data-id="<?= (int)$y['id'] ?>" data-year="<?= (int)$y['year'] ?>" title="Close & roll forward">
                                        <i class="fa-solid fa-forward"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn btn-outline-success btn-reopen"
                                        data-id="<?= (int)$y['id'] ?>" data-year="<?= (int)$y['year'] ?>" title="Reopen year">
                                        <i class="fa-solid fa-lock-open"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(function() {
        $('#tblFY').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });

        const post = (url, data) => $.post(url, $.extend({
            csrf: CWA.csrf
        }, data), null, 'json');

        $('.btn-current').on('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            Swal.fire({
                    title: 'Set as current year?',
                    icon: 'question',
                    showCancelButton: true
                })
                .then(r => r.isConfirmed && post('<?= e($config['app']['url']) ?>/admin/financial-years/close.php', {
                    action: 'current',
                    id
                }).done(() => location.reload()));
        });

        $('.btn-close-year').on('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id,
                y = this.dataset.year;
            Swal.fire({
                title: `Close ${y}?`,
                html: `Closing locks all schedules and marks the year non-current.<br>You can reopen it later.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Close year'
            }).then(r => r.isConfirmed && post('<?= e($config['app']['url']) ?>/admin/financial-years/close.php', {
                action: 'close',
                id
            }).done(() => location.href = 'index.php?ok=closed'));
        });

        $('.btn-reopen').on('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id,
                y = this.dataset.year;
            Swal.fire({
                title: `Reopen ${y}?`,
                html: `Schedules become editable again (unless payments lock them).<br>Reopening does not automatically make it the current year.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Reopen'
            }).then(r => r.isConfirmed && post('<?= e($config['app']['url']) ?>/admin/financial-years/close.php', {
                action: 'reopen',
                id
            }).done(() => location.href = 'index.php?ok=reopened'));
        });

        $('.btn-roll').on('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id,
                y = this.dataset.year;
            Swal.fire({
                title: `Close ${y} and open ${+y+1}?`,
                html: `Schedules from <b>${y}</b> will be copied into <b>${+y+1}</b>.<br>
            <span class="text-muted small">The new year becomes the current year.</span>`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Roll forward'
            }).then(r => {
                if (!r.isConfirmed) return;
                $.post('<?= e($config['app']['url']) ?>/admin/financial-years/roll-forward.php', {
                        csrf: CWA.csrf,
                        id
                    },
                    res => {
                        if (res.ok) {
                            toastr.success(`Opened ${res.next_year}. Copied ${res.copied} schedule rows.`);
                            setTimeout(() => location.href = 'index.php', 900);
                        } else {
                            toastr.error(res.error || 'Roll forward failed.');
                        }
                    }, 'json').fail(() => toastr.error('Server error.'));
            });
        });
    });
</script>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>