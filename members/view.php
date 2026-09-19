<?php
require __DIR__ . '/../includes/auth.php';
require_permission('member.view');

$id = (int)($_GET['id'] ?? 0);
$m  = Member::find($id);
if (!$m) {
    http_response_code(404);
    exit('Member not found');
}

$history = Member::history($id);

// Current-year obligations
$year = current_year();
$reg  = Db::one(
    "SELECT * FROM member_registration WHERE member_id = :m AND year = :y",
    ['m' => $id, 'y' => $year]
);
$wel  = Db::one(
    "SELECT * FROM member_welfare WHERE member_id = :m AND year = :y",
    ['m' => $id, 'y' => $year]
);

$pageTitle = 'Member · ' . $m['full_name'];
require __DIR__ . '/../templates/layout/header.php';
?>
<?php if (!empty($_GET['ok'])): ?>
    <div class="alert alert-success py-2 small">Saved.</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fa-solid fa-user me-2"></i><?= e($m['full_name']) ?>
        <span class="badge bg-light text-dark border ms-2"><?= e($m['member_code']) ?></span>
        <span class="badge bg-<?= $m['status'] === 'ACTIVE' ? 'success' : 'secondary' ?> ms-1"><?= e($m['status']) ?></span>
    </h4>
    <div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-pen me-1"></i> Edit
        </a>
        <a href="transfer.php?id=<?= $id ?>" class="btn btn-outline-warning btn-sm">
            <i class="fa-solid fa-right-left me-1"></i> Transfer
        </a>
        <a href="status.php?id=<?= $id ?>" class="btn btn-outline-danger btn-sm">
            <i class="fa-solid fa-user-shield me-1"></i> Status
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white"><strong>Identity</strong></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Member code</dt>
                    <dd class="col-7"><?= e($m['member_code']) ?></dd>
                    <dt class="col-5 text-muted">Full name</dt>
                    <dd class="col-7"><?= e($m['full_name']) ?></dd>
                    <dt class="col-5 text-muted">Phone</dt>
                    <dd class="col-7"><?= e($m['phone']) ?></dd>
                    <dt class="col-5 text-muted">National ID</dt>
                    <dd class="col-7"><?= e($m['id_number'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Gender</dt>
                    <dd class="col-7"><?= e($m['gender'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Date of birth</dt>
                    <dd class="col-7"><?= e($m['date_of_birth'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Join date</dt>
                    <dd class="col-7"><?= e($m['join_date']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white"><strong>Current Placement</strong></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Center</dt>
                    <dd class="col-7"><?= e($m['center_name']) ?></dd>
                    <dt class="col-5 text-muted">Jumuiya</dt>
                    <dd class="col-7"><?= e($m['jumuiya_name']) ?></dd>
                    <dt class="col-5 text-muted">Welfare enabled (center)</dt>
                    <dd class="col-7">
                        <?= $m['welfare_enabled'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-light text-muted border">No</span>' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <strong><?= (int)$year ?> Registration Obligation</strong>
            </div>
            <div class="card-body">
                <?php if ($reg): ?>
                    <dl class="row mb-0 small">
                        <dt class="col-7 text-muted">Renewal required</dt>
                        <dd class="col-5 text-end"><?= number_format((float)$reg['renewal_required'], 2) ?></dd>
                        <dt class="col-7 text-muted">Registration required</dt>
                        <dd class="col-5 text-end"><?= number_format((float)$reg['registration_required'], 2) ?></dd>
                        <dt class="col-7 text-muted">Card fee</dt>
                        <dd class="col-5 text-end"><?= number_format((float)$reg['card_required'], 2) ?></dd>
                        <dt class="col-7 fw-bold">Total</dt>
                        <dd class="col-5 text-end fw-bold">
                            <?= number_format((float)$reg['renewal_required'] + (float)$reg['registration_required'] + (float)$reg['card_required'], 2) ?>
                        </dd>
                    </dl>
                    <div class="text-muted small mt-2">
                        Paid vs outstanding will appear here once the payments module is built (Phase 3).
                    </div>
                <?php else: ?>
                    <div class="text-muted small">No registration row for <?= (int)$year ?>.</div>
                <?php endif; ?>

                <?php
                $adv = Db::one(
                    "SELECT amount FROM member_advances
      WHERE member_id = :m AND year = :y AND contribution_type = 'REGISTRATION'",
                    ['m' => $id, 'y' => $year]
                );
                $advAmt = (float)($adv['amount'] ?? 0);
                if ($advAmt > 0):
                ?>
                    <div class="alert alert-info py-2 small mt-2">
                        <i class="fa-solid fa-piggy-bank me-1"></i>
                        Advance available: <strong><?= number_format($advAmt, 2) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <strong><?= (int)$year ?> Welfare Obligation</strong>
            </div>
            <div class="card-body">
                <?php if ($wel && $wel['status'] !== 'NOT_APPLICABLE'): ?>
                    <dl class="row mb-0 small">
                        <dt class="col-7 text-muted">Welfare required</dt>
                        <dd class="col-5 text-end"><?= number_format((float)$wel['welfare_required'], 2) ?></dd>
                        <dt class="col-7 text-muted">Starts from month</dt>
                        <dd class="col-5 text-end"><?= (int)$wel['start_month'] ?></dd>
                    </dl>
                    <div class="text-muted small mt-2">
                        Paid vs outstanding will appear here once the payments module is built (Phase 3).
                    </div>
                <?php elseif ($wel && $wel['status'] === 'NOT_APPLICABLE'): ?>
                    <div class="text-muted small">
                        Welfare is not enabled for this member's center.
                    </div>
                <?php else: ?>
                    <div class="text-muted small">No welfare row for <?= (int)$year ?>.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Jumuiya History</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Effective</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Reason</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="small"><?= e($h['effective_date']) ?></td>
                                <td class="small text-muted"><?= e($h['old_name'] ?? '— initial —') ?></td>
                                <td class="small"><?= e($h['new_name']) ?></td>
                                <td class="small"><?= e($h['reason'] ?? '') ?></td>
                                <td class="small text-muted"><?= e($h['changed_by_name'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/layout/footer.php'; ?>