<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/classes/Auth.php';
require __DIR__ . '/classes/Audit.php';

$config = require __DIR__ . '/config/config.php';
$error = null;

if (Auth::check()) {
    header('Location: ' . $config['app']['url'] . '/dashboard/');
    exit;
}

if (isset($_GET['timeout']))  $error = 'Session timed out. Please sign in again.';
if (isset($_GET['ipchange'])) $error = 'Your network address changed. Please sign in again.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    require_once __DIR__ . '/classes/LoginThrottle.php';
    LoginThrottle::prune();

    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $ip       = $_SERVER['REMOTE_ADDR'] ?? null;

    if ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        $state = LoginThrottle::check($username, $ip);
        if ($state['locked']) {
            $mins = ceil($state['seconds_remaining'] / 60);
            $error = "Too many failed attempts. Try again in {$mins} minute(s).";
        } else {
            $res = Auth::attempt($username, $password);
            LoginThrottle::recordAttempt($username, $ip, $res['ok']);
            if ($res['ok']) {
                $intended = $_SESSION['intended'] ?? null;
                unset($_SESSION['intended']);
                header('Location: ' . ($intended ?: $config['app']['url'] . '/dashboard/'));
                exit;
            }
            $error = $res['error'];
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · CWA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: #f4f6f9;
        }

        .login-card {
            max-width: 420px;
            margin: 8vh auto;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="fa-solid fa-church fa-3x text-primary"></i>
            <h4 class="mt-3 mb-0">Catholic Women Association</h4>
            <div class="text-muted small">Member &amp; Contribution Management</div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h5 class="mb-3">Sign in</h5>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <label class="form-label small">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                            <input name="username" class="form-control" required autofocus
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input name="password" type="password" class="form-control" required>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Sign in
                    </button>
                </form>
            </div>
        </div>

        <div class="text-center text-muted small mt-3">
            &copy; <?= date('Y') ?> CWA
        </div>
    </div>
</body>

</html>