<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.reverse');

$id = (int)($_GET['id'] ?? 0);
$p  = Payment::find($id);
if (!$p) {
    http_response_code(404);
    exit('Payment not found');
}

$check = Payment::canCorrect($id);
if (!$check['ok']) {
    $pageTitle = 'Correct Payment';
    require __DIR__ . '/../templates/layout/header.php';
?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Correction Not Allowed</h4>
        <a href="receipt.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Back to Receipt</a>
    </div>
    <div class="alert alert-warning"><?= e($check['reason']) ?></div>
<?php
    require __DIR__ . '/../templates/layout/footer.php';
    exit;
}

$pageTitle = 'Correct Allocation';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fa-solid fa-sliders me-2"></i>Correct Allocation ·
        <span class="text-muted small"><?= e($p['receipt_no']) ?></span>
    </h4>
    <a href="receipt.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<div class="alert alert-light border small">
    <strong><?= e($p['full_name']) ?></strong> (<?= e($p['member_code']) ?>) ·
    <?= e($p['payment_type']) ?> ·
    <strong><?= number_format((float)$p['amount'], 2) ?></strong> ·
    <?= e($p['payment_date']) ?>
</div>

<form id="correctForm" class="card shadow-sm border-0">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="payment_id" value="<?= $id ?>">

    <div class="card-body">
        <div id="loading" class="text-muted small">Loading outstanding lines…</div>

        <div id="grid" class="d-none">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Line</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end">Currently allocated</th>
                        <th class="text-end" style="width:150px">New allocation</th>
                    </tr>
                </thead>
                <tbody id="gridBody"></tbody>
                <tfoot>
                    <tr>
                        <th>Total new</th>
                        <th></th>
                        <th class="text-end" id="sumOld">0.00</th>
                        <th class="text-end" id="sumNew">0.00</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Payment amount</th>
                        <th class="text-end" id="payAmt">0.00</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">To advance</th>
                        <th class="text-end text-warning" id="toAdv">0.00</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white text-end">
        <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Correction
        </button>
    </div>
</form>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        const paymentId = <?= (int)$id ?>;
        const paymentAmount = <?= json_encode((float)$p['amount']) ?>;
        let lines = [];

        $.post(CWA.base + '/api/payment-manual-plan.php', {
            csrf: CWA.csrf,
            member_id: <?= (int)$p['member_id'] ?>,
            year: <?= (int)$p['year'] ?>,
            type: <?= json_encode($p['payment_type']) ?>,
            payment_id: paymentId
        }).done(res => {
            if (!res.ok) {
                toastr.error(res.error || 'Load failed.');
                return;
            }
            buildGrid(res.data);
            $('#loading').addClass('d-none');
            $('#grid').removeClass('d-none');
        }).fail(xhr => toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Load failed.'));

        function buildGrid(data) {
            lines = [];
            $('#gridBody').empty();

            // Map of currently allocated per component|month
            const curAlloc = {};
            (data.existing_alloc || []).forEach(a => {
                const k = a.component + '|' + (a.month ?? '');
                curAlloc[k] = (curAlloc[k] || 0) + Number(a.amount);
            });

            // Union of outstanding lines and currently allocated lines
            const seen = {};
            const rows = [];
            (data.lines || []).forEach(l => {
                const k = l.component + '|' + (l.month ?? '');
                seen[k] = true;
                rows.push({
                    k,
                    component: l.component,
                    month: l.month,
                    outstanding: l.outstanding,
                    current: curAlloc[k] || 0,
                    label: l.label
                });
            });
            Object.keys(curAlloc).forEach(k => {
                if (seen[k]) return;
                const [comp, mo] = k.split('|');
                rows.push({
                    k,
                    component: comp,
                    month: mo ? parseInt(mo, 10) : null,
                    outstanding: 0,
                    current: curAlloc[k],
                    label: comp
                });
            });

            rows.forEach(r => {
                lines.push({
                    component: r.component,
                    month: r.month,
                    amount: r.current
                });
                const month = r.month ? ` <span class="text-muted small">(${r.month})</span>` : '';
                $('#gridBody').append(`
        <tr>
          <td>${r.label || r.component}${month}</td>
          <td class="text-end small text-muted">${Number(r.outstanding).toFixed(2)}</td>
          <td class="text-end small text-muted">${Number(r.current).toFixed(2)}</td>
          <td class="text-end">
            <input type="number" step="0.01" min="0"
                   class="form-control form-control-sm text-end js-new"
                   data-idx="${lines.length - 1}"
                   value="${Number(r.current).toFixed(2)}">
          </td>
        </tr>`);
            });

            recalc();
        }

        function recalc() {
            const sumOld = lines.reduce((s, l) => s + Number(l.amount || 0), 0);
            const sumNew = lines.reduce((s, l) => s + Number(l.amount || 0), 0); // recomputed on input
            $('#sumOld').text(sumOld.toFixed(2));
            $('#payAmt').text(paymentAmount.toFixed(2));

            const total = lines.reduce((s, l) => s + Number(l.amount || 0), 0);
            $('#sumNew').text(total.toFixed(2));

            const toAdv = Math.max(0, paymentAmount - total);
            $('#toAdv').text(toAdv.toFixed(2));

            const bad = total > paymentAmount + 0.0001;
            $('#saveBtn').prop('disabled', bad);
        }

        $(document).on('input', '.js-new', function() {
            const i = parseInt(this.dataset.idx, 10);
            lines[i].amount = parseFloat(this.value || '0');
            recalc();
        });

        $('#correctForm').on('submit', function(e) {
            e.preventDefault();
            const total = lines.reduce((s, l) => s + Number(l.amount || 0), 0);
            if (total > paymentAmount + 0.0001) {
                toastr.error('Allocations exceed payment amount.');
                return;
            }

            Swal.fire({
                title: 'Save correction?',
                html: `This will reverse the current allocation lines and write new ones against the same payment.
             <br>Old lines are preserved as <code>ADJUSTMENT</code> rows.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Save correction',
            }).then(r => {
                if (!r.isConfirmed) return;
                const base = {
                    csrf: CWA.csrf,
                    payment_id: paymentId
                };
                lines.forEach((l, i) => {
                    base[`lines[${i}][component]`] = l.component;
                    base[`lines[${i}][month]`] = l.month ?? '';
                    base[`lines[${i}][amount]`] = l.amount;
                });
                $.post(CWA.base + '/api/payment-correct.php', base)
                    .done(res => {
                        if (!res.ok) {
                            toastr.error(res.error || 'Save failed.');
                            return;
                        }
                        toastr.success('Correction saved.');
                        setTimeout(() => location.href = 'receipt.php?id=' + paymentId, 700);
                    })
                    .fail(xhr => toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Save failed.'));
            });
        });
    });
</script>