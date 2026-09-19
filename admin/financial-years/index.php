<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.view');

$years = FinancialYear::all();
$base  = $config['app']['url'];

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
            'current'  => 'Current year changed.',
            default    => 'Done.',
        }) ?>
    </div>
<?php endif; ?>
<?php if (!empty($_GET['err'])): ?>
    <div class="alert alert-danger py-2 small">
        <?= e($_GET['err']) ?>
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
                <?php foreach ($years as $y):
                    $id       = (int)$y['id'];
                    $yr       = (int)$y['year'];
                    $isOpen   = $y['status'] === 'OPEN';
                    $isCurr   = (int)$y['is_current'] === 1;
                    $csrf     = csrf_token();
                ?>
                    <tr>
                        <td><strong><?= $yr ?></strong></td>
                        <td class="small text-muted"><?= e($y['start_date']) ?></td>
                        <td class="small text-muted"><?= e($y['end_date']) ?></td>
                        <td>
                            <span class="badge bg-<?= $isOpen ? 'success' : 'secondary' ?>">
                                <?= e($y['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($isCurr): ?>
                                <span class="badge bg-primary">Current</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-secondary" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <?php if ($isOpen && !$isCurr): ?>
                                    <a href="<?= e($base) ?>/admin/financial-years/close.php?action=current&id=<?= $id ?>&csrf=<?= $csrf ?>"
                                        class="btn btn-outline-secondary js-fy-action"
                                        data-title="Set <?= $yr ?> as current year?"
                                        data-text="The previous current year will be unset. No data is deleted."
                                        data-confirm="Set current"
                                        title="Mark as current">
                                        <i class="fa-solid fa-star"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if ($isOpen): ?>
                                    <a href="<?= e($base) ?>/admin/financial-years/close.php?action=close&id=<?= $id ?>&csrf=<?= $csrf ?>"
                                        class="btn btn-outline-warning js-fy-action"
                                        data-title="Close <?= $yr ?>?"
                                        data-text="Closing locks all schedules and marks the year non-current. You can reopen it later."
                                        data-confirm="Close year"
                                        title="Close year">
                                        <i class="fa-solid fa-lock"></i>
                                    </a>

                                    <a href="<?= e($base) ?>/admin/financial-years/roll-forward.php?id=<?= $id ?>&csrf=<?= $csrf ?>"
                                        class="btn btn-outline-primary js-fy-roll"
                                        data-year="<?= $yr ?>"
                                        title="Close & roll forward">
                                        <i class="fa-solid fa-forward"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= e($base) ?>/admin/financial-years/close.php?action=reopen&id=<?= $id ?>&csrf=<?= $csrf ?>"
                                        class="btn btn-outline-success js-fy-action"
                                        data-title="Reopen <?= $yr ?>?"
                                        data-text="Schedules become editable again unless payments lock them. Reopening does not automatically make it the current year."
                                        data-confirm="Reopen"
                                        title="Reopen year">
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


<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>
<script>
    (function() {
        // Delegated handlers — safe against DataTables re-renders.
        $(document).on('click', '.js-fy-action', function(e) {
            e.preventDefault();
            var url = this.getAttribute('href');
            var title = this.dataset.title || 'Confirm?';
            var text = this.dataset.text || '';
            var cta = this.dataset.confirm || 'Confirm';

            Swal.fire({
                title: title,
                html: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: cta,
            }).then(function(r) {
                if (r.isConfirmed) window.location.href = url;
            });
        });

        $(document).on('click', '.js-fy-roll', function(e) {
            e.preventDefault();
            var url = this.getAttribute('href');
            var year = this.dataset.year;

            Swal.fire({
                title: 'Close ' + year + ' and open ' + (parseInt(year, 10) + 1) + '?',
                html: 'Schedules from <b>' + year + '</b> will be copied into <b>' +
                    (parseInt(year, 10) + 1) + '</b>.<br>' +
                    '<span class="text-muted small">The new year becomes the current year.</span>',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Roll forward',
            }).then(function(r) {
                if (!r.isConfirmed) return;

                // Roll-forward returns JSON; call it via GET and interpret.
                $.getJSON(url)
                    .done(function(res) {
                        if (res && res.ok) {
                            toastr.success('Opened ' + res.next_year + '. Copied ' + res.copied + ' schedule rows.');
                            setTimeout(function() {
                                window.location.href = 'index.php?ok=created';
                            }, 900);
                        } else {
                            toastr.error((res && res.error) || 'Roll forward failed.');
                        }
                    })
                    .fail(function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Server error.';
                        toastr.error(msg);
                    });
            });
        });

        // Ensure DataTables init doesn't wipe the delegated handlers — it won't,
        // because they're on document, but keep this here for future-proofing.
        $('#tblFY').DataTable({
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            columnDefs: [{
                orderable: false,
                targets: -1
            }],
        });
    })();
</script>