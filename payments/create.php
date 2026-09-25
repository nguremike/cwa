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
            <select id="center_id" class="form-select" style="width:100%">
                <option value="">— search center —</option>
                <?php foreach ($centers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Jumuiya</label>
            <select id="jumuiya_id" class="form-select" style="width:100%">
                <option value="">— search jumuiya —</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small d-flex justify-content-between align-items-center">
                <span>Member</span>
                <a href="#" id="quickCheckLink"
                    class="small text-primary text-decoration-none d-none">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> View history
                </a>
            </label>
            <select id="member_id" name="member_id" class="form-select" style="width:100%" required>
                <option value="">— search member —</option>
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
            <select name="type" id="type" class="form-select" style="width:100%" required>
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
    <!-- Quick-check modal -->
    <div class="modal fade" id="quickCheckModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>Contribution History
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="qcBody">
                    <div class="text-muted small">Loading…</div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="qcFullStatement" class="btn btn-outline-secondary btn-sm" target="_blank">
                        <i class="fa-solid fa-file-invoice me-1"></i> Open full statement
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>
<script>
    $(function() {
        const $c = $('#center_id');
        const $j = $('#jumuiya_id');
        const $m = $('#member_id');
        const $amount = $('#amount');
        const $type = $('#type');
        const $year = $('#year');
        const $preview = $('#previewArea');
        const $manualArea = $('#manualArea');
        const $manualBody = $('#manualBody');
        const $save = $('#saveBtn');
        const $manualToggle = $('#manualToggle');

        let currentPlan = null;
        let manualLines = [];

        // ---------- Select2 initialisation ----------
        $c.select2({
            placeholder: '— search center —',
            allowClear: true,
            width: '100%'
        });
        $j.select2({
            placeholder: '— search jumuiya —',
            allowClear: true,
            width: '100%'
        });
        $m.select2({
            placeholder: '— search member —',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0
        });

        // When Select2 clears a selection it fires 'change' but with a null-ish value.
        function val(el) {
            const v = el.val();
            return (v === null || v === '') ? '' : String(v);
        }

        // ---------- Center → Jumuiya ----------
        $c.on('change', function() {
            const cid = val($c);
            $j.empty().append('<option value="">— search jumuiya —</option>').trigger('change.select2');
            $m.empty().append('<option value="">— search member —</option>').trigger('change.select2');
            $save.prop('disabled', true);
            if (!cid) return;

            api('/api/jumuiyas.php', {
                center_id: cid
            }).done(function(res) {
                (res.data || []).forEach(function(x) {
                    $j.append($('<option>', {
                        value: x.id,
                        text: x.name
                    }));
                });
                $j.trigger('change.select2');
            });
        });

        // ---------- Jumuiya → Members ----------
        $j.on('change', function() {
            const jid = val($j);
            $m.empty().append('<option value="">— loading… —</option>').trigger('change.select2');
            $save.prop('disabled', true);
            if (!jid) {
                $m.empty().append('<option value="">— search member —</option>').trigger('change.select2');
                return;
            }
            $.getJSON(CWA.base + '/api/members.php', {
                    jumuiya_id: jid,
                    status: 'ACTIVE'
                })
                .done(function(res) {
                    $m.empty().append('<option value="">— search member —</option>');
                    (res.data || []).forEach(function(x) {
                        $m.append($('<option>', {
                            value: x.id,
                            text: x.member_code + ' · ' + x.full_name
                        }));
                    });
                    $m.trigger('change.select2');
                })
                .fail(function() {
                    $m.empty().append('<option value="">— failed to load —</option>').trigger('change.select2');
                });
        });

        // ---------- Auto preview render ----------
        function renderAutoPreview(plan) {
            if (!plan.allocations.length) {
                $preview.html('<span class="text-muted">No allocations — the entire amount becomes an advance.</span>');
                return;
            }
            var rows = plan.allocations.map(function(a) {
                var label = a.label || a.component;
                var month = a.month ? ' <span class="text-muted small">(' + a.month + ')</span>' : '';
                return '<tr><td>' + label + month + '</td><td class="text-end">' + Number(a.amount).toFixed(2) + '</td></tr>';
            }).join('');
            var total = plan.allocations.reduce(function(s, a) {
                return s + Number(a.amount);
            }, 0);
            var adv = plan.advance_created > 0 ?
                '<div class="text-warning small mt-2"><i class="fa-solid fa-circle-info me-1"></i>Overflow to advance: <strong>' + Number(plan.advance_created).toFixed(2) + '</strong></div>' :
                '';
            var used = plan.advance_used > 0 ?
                '<div class="text-info small mt-2"><i class="fa-solid fa-circle-info me-1"></i>Advance drawn: <strong>' + Number(plan.advance_used).toFixed(2) + '</strong></div>' :
                '';
            $preview.html(
                '<table class="table table-sm mb-0"><tbody>' + rows + '</tbody>' +
                '<tfoot><tr><th>Total</th><th class="text-end">' + total.toFixed(2) + '</th></tr></tfoot></table>' +
                adv + used
            );
        }

        function refreshAuto() {
            currentPlan = null;
            $save.prop('disabled', true);
            if (!val($m) || !val($type)) return;
            var amt = parseFloat($amount.val() || '0');
            if (amt <= 0) return;
            $.post(CWA.base + '/api/payment-preview.php', {
                    csrf: CWA.csrf,
                    member_id: val($m),
                    year: $year.val(),
                    type: val($type),
                    amount: amt
                })
                .done(function(res) {
                    if (!res.ok) {
                        toastr.error(res.error || 'Preview failed.');
                        return;
                    }
                    currentPlan = res.plan;
                    renderAutoPreview(res.plan);
                    $save.prop('disabled', false);
                })
                .fail(function(xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Preview failed.');
                });
        }

        // ---------- Manual allocation ----------
        function buildManualRows(data) {
            manualLines = [];
            $manualBody.empty();
            if (!data.lines || !data.lines.length) {
                $manualBody.append('<tr><td colspan="3" class="text-muted small">Nothing outstanding — the entire amount will become an advance.</td></tr>');
            }
            data.lines.forEach(function(l) {
                if (l.outstanding <= 0 && l.component !== 'OTHER') return;
                manualLines.push({
                    component: l.component,
                    month: l.month,
                    outstanding: l.outstanding,
                    amount: 0
                });
                var idx = manualLines.length - 1;
                var month = l.month ? ' <span class="text-muted small">(' + l.month + ')</span>' : '';
                $manualBody.append(
                    '<tr>' +
                    '<td>' + (l.label || l.component) + month + '</td>' +
                    '<td class="text-end small text-muted">' + Number(l.outstanding).toFixed(2) + '</td>' +
                    '<td class="text-end">' +
                    '<input type="number" step="0.01" min="0" class="form-control form-control-sm text-end js-man" data-idx="' + idx + '" placeholder="0.00">' +
                    '</td>' +
                    '</tr>'
                );
            });
            recalcManual();
        }

        function recalcManual() {
            var amt = parseFloat($amount.val() || '0');
            var sum = manualLines.reduce(function(s, l) {
                return s + Number(l.amount || 0);
            }, 0);
            var adv = Math.max(0, amt - sum);
            $('#manTotal').text(sum.toFixed(2));
            $('#manPayment').text(amt.toFixed(2));
            $('#manAdvance').text(adv.toFixed(2));
            $save.prop('disabled', sum > amt + 0.0001 || amt <= 0);
        }

        function refreshManual() {
            if (!val($m) || !val($type)) return;
            $.post(CWA.base + '/api/payment-manual-plan.php', {
                    csrf: CWA.csrf,
                    member_id: val($m),
                    year: $year.val(),
                    type: val($type)
                })
                .done(function(res) {
                    if (!res.ok) {
                        toastr.error(res.error || 'Could not load outstanding lines.');
                        return;
                    }
                    buildManualRows(res.data);
                })
                .fail(function(xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Failed.');
                });
        }

        $(document).on('input', '.js-man', function() {
            var i = parseInt(this.dataset.idx, 10);
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

        // ---------- Submit ----------
        $('#payForm').on('submit', function(e) {
            e.preventDefault();
            var manual = $manualToggle.is(':checked');
            var amt = parseFloat($amount.val() || '0');

            var base = {
                csrf: CWA.csrf,
                member_id: val($m),
                year: $year.val(),
                payment_date: $('input[name=payment_date]').val(),
                amount: amt,
                type: val($type),
                method: $('select[name=method]').val(),
                reference: $('input[name=reference]').val(),
                notes: $('input[name=notes]').val(),
                mode: manual ? 'manual' : 'auto'
            };

            var doSave = function() {
                $.post(CWA.base + '/api/payment-save.php', base)
                    .done(function(res) {
                        if (!res.ok) {
                            toastr.error(res.error || 'Save failed.');
                            return;
                        }
                        toastr.success('Saved. Receipt ' + res.receipt_no);
                        setTimeout(function() {
                            location.href = 'receipt.php?id=' + res.payment_id;
                        }, 800);
                    })
                    .fail(function(xhr) {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Save failed.');
                    });
            };

            if (manual) {
                var manualSum = manualLines.reduce(function(s, l) {
                    return s + Number(l.amount || 0);
                }, 0);
                if (manualSum > amt + 0.0001) {
                    toastr.error('Manual allocations exceed the payment amount.');
                    return;
                }
                manualLines.forEach(function(l, i) {
                    if (l.amount > 0) {
                        base['lines[' + i + '][component]'] = l.component;
                        base['lines[' + i + '][month]'] = l.month || '';
                        base['lines[' + i + '][amount]'] = l.amount;
                    }
                });
                var remainder = amt - manualSum;
                Swal.fire({
                    title: 'Confirm manual allocation?',
                    html: 'Total allocated <b>' + manualSum.toFixed(2) + '</b> of <b>' + amt.toFixed(2) + '</b>.' +
                        (remainder > 0 ? '<br><span class="text-warning">Remainder <b>' + remainder.toFixed(2) + '</b> will become an advance.</span>' : ''),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Save payment'
                }).then(function(r) {
                    if (r.isConfirmed) doSave();
                });
                return;
            }

            if (!currentPlan) {
                toastr.warning('Click Refresh first.');
                return;
            }
            var rows = currentPlan.allocations.map(function(a) {
                var label = a.label || a.component;
                return '<tr><td>' + label + '</td><td class="text-end">' + Number(a.amount).toFixed(2) + '</td></tr>';
            }).join('');
            var total = currentPlan.allocations.reduce(function(s, a) {
                return s + Number(a.amount);
            }, 0);

            Swal.fire({
                title: 'Confirm payment?',
                html: '<table class="table table-sm mb-0"><tbody>' + rows + '</tbody>' +
                    '<tfoot><tr><th>Total</th><th class="text-end">' + total.toFixed(2) + '</th></tr></tfoot></table>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Save payment'
            }).then(function(r) {
                if (r.isConfirmed) doSave();
            });
        });

        // ---------------- Quick-check modal ----------------
        var $qcLink = $('#quickCheckLink');
        var $qcModalEl = document.getElementById('quickCheckModal');
        var qcModal = new bootstrap.Modal($qcModalEl);

        function toggleQcLink() {
            if (val($m)) $qcLink.removeClass('d-none');
            else $qcLink.addClass('d-none');
        }

        // Show/hide link on member change
        $m.on('change', toggleQcLink);

        // Click handler — fetch and render
        $qcLink.on('click', function(e) {
            e.preventDefault();
            var memberId = val($m);
            if (!memberId) {
                toastr.warning('Select a member first.');
                return;
            }

            var type = val($type);
            var year = $year.val();

            $('#qcBody').html('<div class="text-muted small">Loading…</div>');
            qcModal.show();

            $.getJSON(CWA.base + '/api/member-quick-check.php', {
                member_id: memberId,
                type: type,
                year: year
            }).done(function(res) {
                if (!res.ok) {
                    $('#qcBody').html('<div class="text-danger small">' + (res.error || 'Failed.') + '</div>');
                    return;
                }
                renderQuickCheck(res);
            }).fail(function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Failed to load.';
                $('#qcBody').html('<div class="text-danger small">' + msg + '</div>');
            });
        });

        function renderQuickCheck(res) {
            var m = res.member;
            var html = '';

            // Identity
            html += '<div class="mb-3">';
            html += '<div><strong>' + escapeHtml(m.full_name) + '</strong> ';
            html += '<span class="badge bg-light text-dark border ms-1">' + escapeHtml(m.member_code) + '</span> ';
            var statusClass = m.status === 'ACTIVE' ? 'success' : 'secondary';
            html += '<span class="badge bg-' + statusClass + ' ms-1">' + escapeHtml(m.status) + '</span>';
            html += '</div>';
            html += '<div class="small text-muted">' + escapeHtml(m.center_name) + ' · ' + escapeHtml(m.jumuiya_name);
            if (m.phone) html += ' · ' + escapeHtml(m.phone);
            html += '</div>';
            html += '<div class="small text-muted">Year: <strong>' + res.year + '</strong>';
            if (res.type) html += ' · Type: <strong>' + res.type + '</strong>';
            html += '</div>';
            html += '</div>';

            // Summaries
            var s = res.summaries || {};
            var hasSummary = Object.keys(s).length > 0;
            if (hasSummary) {
                html += '<h6 class="mb-2">Summary for ' + res.year + '</h6>';
                html += '<table class="table table-sm mb-3"><thead class="table-light"><tr>';
                html += '<th>Type</th><th class="text-end">Due</th><th class="text-end">Paid</th><th class="text-end">Balance</th>';
                html += '</tr></thead><tbody>';
                Object.keys(s).forEach(function(t) {
                    var r = s[t];
                    var balClass = r.balance > 0 ? 'text-danger fw-bold' : 'text-success';
                    html += '<tr>';
                    html += '<td>' + t + '</td>';
                    html += '<td class="text-end">' + fmt(r.due) + '</td>';
                    html += '<td class="text-end">' + fmt(r.paid) + '</td>';
                    html += '<td class="text-end ' + balClass + '">' + fmt(r.balance) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            } else {
                html += '<div class="alert alert-light border small">No obligation for ' + res.year +
                    (res.type ? ' under ' + res.type : '') + '.</div>';
            }

            // Outstanding lines
            var out = res.outstanding || [];
            if (out.length) {
                html += '<h6 class="mb-2">Outstanding lines</h6>';
                html += '<table class="table table-sm mb-3"><thead class="table-light"><tr>';
                html += '<th>Type</th><th>Line</th><th class="text-end">Outstanding</th>';
                html += '</tr></thead><tbody>';
                out.forEach(function(o) {
                    html += '<tr>';
                    html += '<td class="small">' + o.type + '</td>';
                    html += '<td class="small">' + escapeHtml(o.label) + '</td>';
                    html += '<td class="text-end small text-danger">' + fmt(o.outstanding) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            }

            // Advances
            var adv = res.advances || {};
            var advKeys = Object.keys(adv);
            if (advKeys.length) {
                html += '<h6 class="mb-2">Advance (credit)</h6>';
                html += '<table class="table table-sm mb-3"><tbody>';
                advKeys.forEach(function(t) {
                    html += '<tr>';
                    html += '<td class="small">' + t + '</td>';
                    html += '<td class="text-end small text-info">' + fmt(adv[t]) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            }

            // Recent payments
            var recent = res.recent || [];
            if (recent.length) {
                html += '<h6 class="mb-2">Recent payments</h6>';
                html += '<table class="table table-sm mb-0"><thead class="table-light"><tr>';
                html += '<th>Receipt</th><th>Date</th><th>Type</th><th class="text-end">Amount</th><th>Status</th>';
                html += '</tr></thead><tbody>';
                recent.forEach(function(p) {
                    var statusClass = p.status === 'ACTIVE' ? 'success' : 'danger';
                    html += '<tr>';
                    html += '<td class="small"><a href="' + CWA.base + '/payments/receipt.php?id=' + p.id +
                        '" target="_blank">' + escapeHtml(p.receipt_no) + '</a></td>';
                    html += '<td class="small">' + escapeHtml(p.payment_date) + '</td>';
                    html += '<td class="small">' + escapeHtml(p.payment_type) + '</td>';
                    html += '<td class="text-end small">' + fmt(p.amount) + '</td>';
                    html += '<td class="small"><span class="badge bg-' + statusClass + '">' + escapeHtml(p.status) + '</span></td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            } else {
                html += '<div class="text-muted small">No payments recorded yet for this filter.</div>';
            }

            $('#qcBody').html(html);
            $('#qcFullStatement').attr('href',
                CWA.base + '/reports/member-statement.php?id=' + m.id + '&year=' + res.year);
        }

        function fmt(n) {
            n = Number(n || 0);
            return n.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function escapeHtml(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Refresh when member or type changes while the modal is open
        $m.on('change', function() {
            if ($qcModalEl.classList.contains('show')) $qcLink.trigger('click');
        });
        $type.on('change', function() {
            if ($qcModalEl.classList.contains('show')) $qcLink.trigger('click');
        });

        // Initial state
        toggleQcLink();
    });
</script>