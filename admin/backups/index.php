<?php
require __DIR__ . '/../../includes/auth.php';
require_permission('settings.edit');

$dir = __DIR__ . '/../../storage/backups';
if (!is_dir($dir)) @mkdir($dir, 0775, true);

$msg = null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $what = $_POST['what'] ?? '';
    $script = match ($what) {
        'db'    => escapeshellcmd(__DIR__ . '/../../bin/backup-db.sh'),
        'files' => escapeshellcmd(__DIR__ . '/../../bin/backup-files.sh'),
        default => null,
    };
    if ($script) {
        $out = shell_exec($script . ' 2>&1');
        Audit::log('EXPORT', 'backup', null, null, ['what' => $what, 'output' => $out]);
        $msg = "Backup started. " . htmlspecialchars((string)$out);
    } else {
        $err = 'Unknown backup type.';
    }
}

$files = [];
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $dir . '/' . $f;
        if (!is_file($full)) continue;
        $files[] = ['name' => $f, 'size' => filesize($full), 'mtime' => filemtime($full)];
    }
    usort($files, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
}

$pageTitle = 'Backups';
require __DIR__ . '/../../templates/layout/header.php';
?>
<h4 class="mb-3"><i class="fa-solid fa-database me-2"></i>Backups</h4>

<?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<form method="post" class="mb-3">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <button name="what" value="db" class="btn btn-primary">
        <i class="fa-solid fa-database me-1"></i> Backup database now
    </button>
    <button name="what" value="files" class="btn btn-outline-secondary">
        <i class="fa-solid fa-folder-zipped me-1"></i> Backup files now
    </button>
</form>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white"><strong>Existing backups</strong></div>
    <div class="card-body p-0">
        <?php if (!$files): ?>
            <div class="p-3 text-muted small">No backups yet.</div>
        <?php else: ?>
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>File</th>
                        <th class="text-end">Size</th>
                        <th>Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $f): ?>
                        <tr>
                            <td class="small"><?= e($f['name']) ?></td>
                            <td class="text-end small"><?= number_format($f['size'] / 1024, 1) ?> KB</td>
                            <td class="small"><?= date('Y-m-d H:i', $f['mtime']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-light border small mt-3">
    <i class="fa-solid fa-circle-info me-1"></i>
    These scripts dump to <code>storage/backups/</code>. Restore with:
    <code>bin/restore.sh storage/backups/db-....sql.gz</code>.
</div>
<?php require __DIR__ . '/../../templates/layout/footer.php'; ?>