<?php

/**
 * One-time parish setup. Enforces single parish.
 * Delete this file after running.
 */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/classes/Audit.php';

$existing = Db::one("SELECT * FROM parishes ORDER BY id LIMIT 1");

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $name     = trim($_POST['name'] ?? '');
    $diocese  = trim($_POST['diocese'] ?? '');
    $address  = trim($_POST['physical_address'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($name === '') $errors[] = 'Parish name is required.';

    if (!$errors) {
        Db::begin();
        try {
            if ($existing) {
                Db::update('parishes', [
                    'name'             => $name,
                    'diocese'          => $diocese ?: null,
                    'physical_address' => $address ?: null,
                    'phone'            => $phone ?: null,
                    'email'            => $email ?: null,
                ], 'id = :id', ['id' => $existing['id']]);
                Audit::log('UPDATE', 'parishes', (int)$existing['id'], null, ['name' => $name]);
            } else {
                $id = Db::insert('parishes', [
                    'name'             => $name,
                    'diocese'          => $diocese ?: null,
                    'physical_address' => $address ?: null,
                    'phone'            => $phone ?: null,
                    'email'            => $email ?: null,
                ]);
                Audit::log('CREATE', 'parishes', $id, null, ['name' => $name]);
            }
            Db::commit();
            header('Location: login.php');
            exit;
        } catch (Throwable $e) {
            Db::rollback();
            $errors[] = 'Save failed: ' . $e->getMessage();
        }
    }
}

$config = require __DIR__ . '/config/config.php';
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Setup Parish · CWA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-light">
    <div class="container py-5" style="max-width:640px;">
        <h4><i class="fa-solid fa-church me-2"></i>Parish Setup</h4>
        <p class="text-muted">
            Fill in the details below once.
            <?= $existing ? '<br><span class="text-warning">A parish already exists — submitting will update it.</span>' : '' ?>
        </p>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="card shadow-sm border-0">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label small">Parish name *</label>
                    <input name="name" class="form-control" required value="<?= htmlspecialchars($existing['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Diocese</label>
                    <input name="diocese" class="form-control" value="<?= htmlspecialchars($existing['diocese'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Phone</label>
                    <input name="phone" class="form-control" value="<?= htmlspecialchars($existing['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Email</label>
                    <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($existing['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Physical address</label>
                    <input name="physical_address" class="form-control" value="<?= htmlspecialchars($existing['physical_address'] ?? '') ?>">
                </div>
            </div>
            <div class="card-footer bg-white text-end">
                <button class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Parish
                </button>
            </div>
        </form>
    </div>
</body>

</html>