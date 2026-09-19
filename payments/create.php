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
                <input class="form-check-input" type="checkbox" id="manualToggle" name="mode" value="manual">
                <label class="form-check-label" for="manualToggle">Manual allocation</label>
            </div>
        </div>

        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><i class="fa-solid fa-list-check me-2"></i>Allocation Preview</strong>
                        <button type="button" id="previewBtn" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Preview allocation
                        </button>
                    </div>
                    <div id="previewArea" class="text-muted small">
                        Fill in member, amount and contribution, then click <em>Preview allocation</em>.
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
            $save = $('#saveBtn');
        let currentPlan = null;

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

        function renderPreview(plan, manualMode) {
            if (!plan.allocations.length) {
                $preview.html('<span class="text-muted">No allocations — the entire amount will become an advance.</span>');
                return;
            }
            let rows = '';
            plan.allocations.forEach(a => {
                const label = a.label ?? a.component;
                const month = a.month ? ` <span class="text-muted small">(${a.month})</span>` : '';
                rows += `<tr><td>${label}${month}</td><td class="text-end">${Number(a.amount).toFixed(2)}</td></tr>`;
            });
            const total = plan.allocations.reduce((s, a) => s + Number(a.amount), 0);
            const advance = plan.advance_created > 0 ?
                `<div class="text-warning small mt-2"><i class="fa-solid fa-circle-info me-1"></i>
           Overflow to advance: <strong>${Number(plan.advance_created).toFixed(2)}</strong></div>` : '';
            const usedAdv = plan.advance_used > 0 ?
                `<div class="text-info small mt-2"><i class="fa-solid fa-circle-info me-1"></i>
           Advance drawn: <strong>${Number(plan.advance_used).toFixed(2)}</strong></div>` : '';
            $preview.html(`
      <table class="table table-sm mb-0">
        <tbody>${rows}</tbody>
        <tfoot><tr><th>Total</th><th class="text-end">${total.toFixed(2)}</th></tr></tfoot>
      </table>
      ${advance}${usedAdv}
    `);
        }

        function preview(manualMode) {
            currentPlan = null;
            $save.prop('disabled', true);
            if (!$m.val()) {
                toastr.warning('Select a member.');
                return;
            }
            if (!$type.val()) {
                toastr.warning('Select a contribution.');
                return;
            }
            const amt = parseFloat($amount.val() || '0');
            if (amt <= 0) {
                toastr.warning('Amount must be positive.');
                return;
            }

            const payload = {
                csrf: CWA.csrf,
                member_id: $m.val(),
                year: $year.val(),
                type: $type.val(),
                amount: amt
            };
            $.post(CWA.base + '/api/payment-preview.php', payload)
                .done(res => {
                    if (!res.ok) {
                        toastr.error(res.error || 'Preview failed.');
                        return;
                    }
                    currentPlan = res.plan;
                    renderPreview(res.plan, manualMode);
                    if (!manualMode) $save.prop('disabled', false);
                })
                .fail(xhr => toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Preview failed.'));
        }

        $('#previewBtn').on('click', () => preview($('#manualToggle').is(':checked')));
        $('#manualToggle').on('change', function() {
            if (this.checked) {
                $save.prop('disabled', true);
                $preview.html('<span class="text-muted">Manual mode: allocation amounts are entered by you. Fill in the form and click <em>Save Payment</em> to record with advance overflow allowed.</span>');
            } else {
                preview(false);
            }
        });

        $('#payForm').on('submit', function(e) {
            e.preventDefault();
            const manual = $('#manualToggle').is(':checked');

            const fire = () => {
                const base = {
                    csrf: CWA.csrf,
                    member_id: $m.val(),
                    year: $year.val(),
                    payment_date: $('input[name=payment_date]').val(),
                    amount: parseFloat($amount.val() || '0'),
                    type: $type.val(),
                    method: $('select[name=method]').val(),
                    reference: $('input[name=reference]').val(),
                    notes: $('input[name=notes]').val(),
                    mode: manual ? 'manual' : 'auto',
                };

                if (manual) {
                    // In this step, manual mode sends the *plan* as-is if already previewed,
                    // otherwise sends only the auto plan with mode=manual so the backend
                    // will convert overflow to advance (matching "allow-advance").
                    // A full manual editor lands in Step 2.
                    if (currentPlan && currentPlan.allocations) {
                        currentPlan.allocations.forEach((a, i) => {
                            base[`lines[${i}][component]`] = a.component;
                            base[`lines[${i}][month]`] = a.month ?? '';
                            base[`lines[${i}][amount]`] = a.amount;
                        });
                    }
                }

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

            if (manual) return fire();

            // Auto mode: show confirm dialog with the preview table before writing
            if (!currentPlan) {
                toastr.warning('Click Preview first.');
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
                if (r.isConfirmed) fire();
            });
        });
    });
</script>