<?php
require __DIR__ . '/../includes/auth.php';
require_permission('payment.create');

$centers = Db::all("SELECT id, name FROM centers WHERE status='ACTIVE' ORDER BY name");
$year    = current_year();

$pageTitle = 'Record Contribution';
require __DIR__ . '/../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-cash-register me-2"></i>Record Contribution</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<form id="payForm" class="card shadow-sm border-0">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="card-body row g-3">

        <div class="col-md-3">
            <label class="form-label small">Center</label>
            <select id="center_id" class="form-select">
                <option value="">— select —</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Jumuiya</label>
            <select id="jumuiya_id" class="form-select">
                <option value="">— select center first —</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small">Member</label>
            <select id="member_id" name="member_id" class="form-select" required>
                <option value="">— select jumuiya first —</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small">Year</label>
            <input name="year" id="year" class="form-control" value="<?= (int)$year ?>" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Payment date</label>
            <input name="payment_date" type="date" class="form-control" required value="<?= e(date('Y-m-d')) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Amount</label>
            <input name="amount" id="amount" type="number" step="0.01" min="1" class="form-control" required placeholder="0.00">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Contribution</label>
            <select name="type" id="type" class="form-select" required>
                <option value="">— select —</option>
                <option value="REGISTRATION">Registration</option>
                <option value="WELFARE">Welfare</option>
                <option value="OTHER">Other</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small">Method</label>
            <select name="method" class="form-select">
                <?php foreach (['CASH', 'MPESA', 'BANK', 'CHEQUE', 'OTHER'] as $m): ?>
                    <option value="<?= $m ?>"><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Reference</label>
            <input name="reference" class="form-control" placeholder="e.g. MPESA code">
        </div>
        <div class="col-md-6">
            <label class="form-label small">Notes</label>
            <input name="notes" class="form-control">
        </div>

        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="manualToggle">
                <label class="form-check-label" for="manualToggle">Manual allocation</label>
            </div>
        </div>

        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><i class="fa-solid fa-list-check me-2"></i><span id="pvTitle">Allocation Preview</span></strong>
                        <button type="button" id="previewBtn" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Refresh
                        </button>
                    </div>

                    <div id="previewArea" class="text-muted small">
                        Fill in member, amount and contribution, then click <em>Refresh</em>.
                    </div>

                    <div id="manualArea" class="d-none">
                        <table class="table table-sm align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th>Line</th>
                                    <th class="text-end" style="width:130px">Outstanding</th>
                                    <th class="text-end" style="width:150px">Allocate</th>
                                </tr>
                            </thead>
                            <tbody id="manualBody"></tbody>
                            <tfoot>
                                <tr>
                                    <th>Total allocated</th>
                                    <th class="text-end" id="manTotal">0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th>Payment amount</th>
                                    <th class="text-end" id="manPayment">0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th>To advance</th>
                                    <th class="text-end text-warning" id="manAdvance">0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="text-muted small">
                            Only outstanding lines are shown. Leave a line at 0 to skip it.
                            Any unallocated remainder becomes an advance.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer bg-white text-end">
        <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Payment
        </button>
    </div>
</form>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        const $c = $('#center_id'),
            $j = $('#jumuiya_id'),
            $m = $('#member_id');
        const $amount = $('#amount'),
            $type = $('#type'),
            $year = $('#year');
        const $preview = $('#previewArea'),
            $manualArea = $('#manualArea'),
            $manualBody = $('#manualBody');
        const $save = $('#saveBtn'),
            $manualToggle = $('#manualToggle');
        let currentPlan = null;
        let manualLines = [];

        $c.on('change', function() {
            $j.empty().append('<option value="">— select —</option>');
            $m.empty().append('<option value="">— select jumuiya first —</option>');
            if (!this.value) return;
            api('/api/jumuiyas.php', {
                center_id: this.value
            }).done(res => {
                (res.data || []).forEach(x => $j.append(`<option value="${x.id}">${x.name}</option>`));
            });
        });

        $j.on('change', function() {
            $m.empty().append('<option value="">— loading… —</option>');
            if (!this.value) {
                $m.empty().append('<option value="">— select jumuiya first —</option>');
                return;
            }
            $.getJSON(CWA.base + '/api/members.php', {
                    jumuiya_id: this.value,
                    status: 'ACTIVE'
                })
                .done(res => {
                    $m.empty().append('<option value="">— select —</option>');
                    (res.data || []).forEach(x =>
                        $m.append(`<option value="${x.id}">${x.member_code} · ${x.full_name}</option>`));
                });
        });

        function renderAutoPreview(plan) {
            if (!plan.allocations.length) {
                $preview.html('<span class="text-muted">No allocations — the entire amount becomes an advance.</span>');
                return;
            }
            const rows = plan.allocations.map(a => {
                const label = a.label ?? a.component;
                const month = a.month ? ` <span class="text-muted small">(${a.month})</span>` : '';
                return `<tr><td>${label}${month}</td><td class="text-end">${Number(a.amount).toFixed(2)}</td></tr>`;
            }).join('');
            const total = plan.allocations.reduce((s, a) => s + Number(a.amount), 0);
            const adv = plan.advance_created > 0 ?
                `<div class="text-warning small mt-2"><i class="fa-solid fa-circle-info me-1"></i>
           Overflow to advance: <strong>${Number(plan.advance_created).toFixed(2)}</strong></div>` : '';
            const used = plan.advance_used > 0 ?
                `<div class="text-info small mt-2"><i class="fa-solid fa-circle-info me-1"></i>
           Advance drawn: <strong>${Number(plan.advance_used).toFixed(2)}</strong></div>` : '';
            $preview.html(`
      <table class="table table-sm mb-0">
        <tbody>${rows}</tbody>
        <tfoot><tr><th>Total</th><th class="text-end">${total.toFixed(2)}</th></tr></tfoot>
      </table>${adv}${used}`);
        }

        function refreshAuto() {
            currentPlan = null;
            $save.prop('disabled', true);
            if (!$m.val() || !$type.val()) return;
            const amt = parseFloat($amount.val() || '0');
            if (amt <= 0) return;
            $.post(CWA.base + '/api/payment-preview.php', {
                    csrf: CWA.csrf,
                    member_id: $m.val(),
                    year: $year.val(),
                    type: $type.val(),
                    amount: amt
                })
                .done(res => {
                    if (!res.ok) {
                        toastr.error(res.error || 'Preview failed.');
                        return;
                    }
                    currentPlan = res.plan;
                    renderAutoPreview(res.plan);
                    $save.prop('disabled', false);
                })
                .fail(xhr => toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Preview failed.'));
        }

        function buildManualRows(data) {
            manualLines = [];
            $manualBody.empty();
            if (!data.lines || !data.lines.length) {
                $manualBody.append('<tr><td colspan="3" class="text-muted small">Nothing outstanding — the entire amount will become an advance.</td></tr>');
            }
            data.lines.forEach((l, i) => {
                if (l.outstanding <= 0 && l.component !== 'OTHER') return;
                manualLines.push({
                    component: l.component,
                    month: l.month,
                    outstanding: l.outstanding,
                    amount: 0
                });
                const idx = manualLines.length - 1;
                const month = l.month ? ` <span class="text-muted small">(${l.month})</span>` : '';
                $manualBody.append(`
        <tr>
          <td>${l.label || l.component}${month}</td>
          <td class="text-end small text-muted">${Number(l.outstanding).toFixed(2)}</td>
          <td class="text-end">
            <input type="number" step="0.01" min="0"
                   class="form-control form-control-sm text-end js-man"
                   data-idx="${idx}" placeholder="0.00">
          </td>
        </tr>`);
            });
            recalcManual();
        }

        function recalcManual() {
            const amt = parseFloat($amount.val() || '0');
            const sum = manualLines.reduce((s, l) => s + Number(l.amount || 0), 0);
            const adv = Math.max(0, amt - sum);
            $('#manTotal').text(sum.toFixed(2));
            $('#manPayment').text(amt.toFixed(2));
            $('#manAdvance').text(adv.toFixed(2));

            const bad = sum > amt + 0.0001;
            $save.prop('disabled', bad || amt <= 0);
        }

        function refreshManual() {
            if (!$m.val() || !$type.val()) return;
            $.post(CWA.base + '/api/payment-manual-plan.php', {
                    csrf: CWA.csrf,
                    member_id: $m.val(),
                    year: $year.val(),
                    type: $type.val()
                })
                .done(res => {
                    if (!res.ok) {
                        toastr.error(res.error || 'Could not load outstanding lines.');
                        return;
                    }
                    buildManualRows(res.data);
                })
                .fail(xhr => toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Failed.'));
        }

        $(document).on('input', '.js-man', function() {
            const i = parseInt(this.dataset.idx, 10);
            manualLines[i].amount = parseFloat(this.value || '0');
            recalcManual();
        });

        $('#previewBtn').on('click', function() {
            if ($manualToggle.is(':checked')) refreshManual();
            else refreshAuto();
        });

        $manualToggle.on('change', function() {
            if (this.checked) {
                $preview.addClass('d-none');
                $manualArea.removeClass('d-none');
                $('#pvTitle').text('Manual Allocation');
                refreshManual();
            } else {
                $manualArea.addClass('d-none');
                $preview.removeClass('d-none');
                $('#pvTitle').text('Allocation Preview');
                refreshAuto();
            }
        });

        $amount.on('input', function() {
            if ($manualToggle.is(':checked')) recalcManual();
        });

        $('#payForm').on('submit', function(e) {
            e.preventDefault();

            const manual = $manualToggle.is(':checked');
            const amt = parseFloat($amount.val() || '0');

            const base = {
                csrf: CWA.csrf,
                member_id: $m.val(),
                year: $year.val(),
                payment_date: $('input[name=payment_date]').val(),
                amount: amt,
                type: $type.val(),
                method: $('select[name=method]').val(),
                reference: $('input[name=reference]').val(),
                notes: $('input[name=notes]').val(),
                mode: manual ? 'manual' : 'auto',
            };

            // Shared save + redirect
            const doSave = () => {
                $.post(CWA.base + '/api/payment-save.php', base)
                    .done(res => {
                        if (!res.ok) {
                            toastr.error(res.error || 'Save failed.');
                            return;
                        }
                        toastr.success('Saved. Receipt ' + res.receipt_no);
                        setTimeout(() => location.href = 'receipt.php?id=' + res.payment_id, 800);
                    })
                    .fail(xhr => toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Save failed.'));
            };

            // --- MANUAL MODE -------------------------------------------------------
            if (manual) {
                // Compute total inside this scope
                const manualSum = manualLines.reduce((s, l) => s + Number(l.amount || 0), 0);

                if (manualSum > amt + 0.0001) {
                    toastr.error('Manual allocations exceed the payment amount.');
                    return;
                }

                // Build lines payload
                manualLines.forEach((l, i) => {
                    if (l.amount > 0) {
                        base[`lines[${i}][component]`] = l.component;
                        base[`lines[${i}][month]`] = l.month ?? '';
                        base[`lines[${i}][amount]`] = l.amount;
                    }
                });

                const remainder = amt - manualSum;

                Swal.fire({
                    title: 'Confirm manual allocation?',
                    html: `Total allocated <b>${manualSum.toFixed(2)}</b> of <b>${amt.toFixed(2)}</b>.` +
                        (remainder > 0 ?
                            `<br><span class="text-warning">Remainder <b>${remainder.toFixed(2)}</b> will become an advance.</span>` :
                            ''),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Save payment',
                }).then(r => {
                    if (r.isConfirmed) doSave();
                });
                return;
            }

            // --- AUTO MODE ---------------------------------------------------------
            if (!currentPlan) {
                toastr.warning('Click Refresh first.');
                return;
            }

            const rows = currentPlan.allocations.map(a => {
                const label = a.label ?? a.component;
                return `<tr><td>${label}</td><td class="text-end">${Number(a.amount).toFixed(2)}</td></tr>`;
            }).join('');

            const total = currentPlan.allocations.reduce((s, a) => s + Number(a.amount), 0);

            Swal.fire({
                title: 'Confirm payment?',
                html: `<table class="table table-sm mb-0"><tbody>${rows}</tbody>
             <tfoot><tr><th>Total</th><th class="text-end">${total.toFixed(2)}</th></tr></tfoot></table>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Save payment',
            }).then(r => {
                if (r.isConfirmed) doSave();
            });
        });
    });
</script>