<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('audit.view');

$id = (int)($_GET['id'] ?? 0);
$a  = Db::one(
    "SELECT a.*, u.full_name AS user_name, u.username
       FROM audit_logs a
       LEFT JOIN users u ON u.id = a.user_id
      WHERE a.id = :id",
    ['id' => $id]
);
if (!$a) {
    http_response_code(404);
    exit('Audit entry not found');
}

$pretty = function (?string $json): string {
    if (!$json) return '—';
    $d = json_decode($json, true);
    if ($d === null) return $json;
    return json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
};

$pageTitle = 'Audit Entry #' . $id;
require __DIR__ . '/../../templates/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-clipboard-list me-2"></i>Audit Entry #<?= $id ?></h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Summary</strong></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-4 text-muted">When</dt>
                    <dd class="col-8"><?= e($a['created_at']) ?></dd>
                    <dt class="col-4 text-muted">User</dt>
                    <dd class="col-8">
                        <?= e($a['user_name'] ?? '—') ?>
                        <?= $a['username'] ? ' · ' . e($a['username']) : '' ?>
                    </dd>
                    <dt class="col-4 text-muted">Action</dt>
                    <dd class="col-8"><?= e($a['action']) ?></dd>
                    <dt class="col-4 text-muted">Table</dt>
                    <dd class="col-8"><?= e($a['table_name'] ?? '—') ?></dd>
                    <dt class="col-4 text-muted">Record</dt>
                    <dd class="col-8"><?= $a['record_id'] ? '#' . (int)$a['record_id'] : '—' ?></dd>
                    <dt class="col-4 text-muted">IP</dt>
                    <dd class="col-8"><?= e($a['ip_address'] ?? '—') ?></dd>
                    <dt class="col-4 text-muted">User agent</dt>
                    <dd class="col-8 small"><?= e($a['user_agent'] ?? '—') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Old values</strong></div>
            <div class="card-body">
                <pre class="small mb-0" style="max-height:280px;overflow:auto"><?= e($pretty($a['old_values'])) ?></pre>
            </div>
        </div>
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white"><strong>New values</strong></div>
            <div class="card-body">
                <pre class="small mb-0" style="max-height:280px;overflow:auto"><?= e($pretty($a['new_values'])) ?></pre>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>