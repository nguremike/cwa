<?php
// Every page that includes this must already have run includes/auth.php
$config = $config ?? require __DIR__ . '/../../config/config.php';
$user   = $_SESSION['user'] ?? null;
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · CWA</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="<?= e($config['app']['url']) ?>/assets/css/app.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= e($config['app']['url']) ?>/dashboard/">
      <i class="fa-solid fa-church"></i> CWA
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="<?= e($config['app']['url']) ?>/dashboard/">
            <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
          </a>
        </li>
        <?php if (user_can('member.view') || user_can('*')): ?>
            <li class="nav-item">
            <a class="nav-link" href="<?= e($config['app']['url']) ?>/members/">
                <i class="fa-solid fa-users me-1"></i> Members
            </a>
            </li>
            <?php endif; ?>

            <?php if (user_can('payment.view') || user_can('*')): ?>
                <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
                    <i class="fa-solid fa-cash-register me-1"></i> Payments
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/payments/">
                    <i class="fa-solid fa-receipt me-2"></i> Payment History</a></li>
                    <?php if (user_can('payment.create') || user_can('*')): ?>
                    <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/payments/create.php">
                        <i class="fa-solid fa-plus me-2"></i> Record Contribution</a></li>
                    <?php endif; ?>
                </ul>
                </li>
            <?php endif; ?>

        <?php if (user_can('user.view') || user_can('center.view') || user_can('jumuiya.view') || user_can('*')): ?>
            <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
                <i class="fa-solid fa-user-shield me-1"></i> Administration
            </a>
            <ul class="dropdown-menu">
                <?php if (user_can('center.view') || user_can('*')): ?>
                <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/admin/centers/">
                    <i class="fa-solid fa-diagram-project me-2"></i> Centers</a></li>
                <?php endif; ?>
                <?php if (user_can('jumuiya.view') || user_can('*')): ?>
                <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/admin/jumuiyas/">
                    <i class="fa-solid fa-people-group me-2"></i> Jumuiyas</a></li>
                <?php endif; ?>
                <?php if (user_can('settings.view') || user_can('*')): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/admin/financial-years/">
                    <i class="fa-solid fa-calendar-days me-2"></i> Financial Years</a></li>
                <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/admin/schedules/">
                    <i class="fa-solid fa-table-list me-2"></i> Contribution Schedules</a></li>
                <?php endif; ?>
                <?php if (user_can('user.view') || user_can('*')): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/admin/users/">
                    <i class="fa-solid fa-users me-2"></i> Users</a></li>
                <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/admin/roles/">
                    <i class="fa-solid fa-key me-2"></i> Roles</a></li>
                <?php endif; ?>
                <?php if (user_can('audit.view') || user_can('*')): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/admin/audit/">
                    <i class="fa-solid fa-clipboard-list me-2"></i> Audit Log</a></li>
                <?php endif; ?>
            </ul>
            </li>
            <?php endif; ?>
            </ul>

      <?php if ($user): ?>
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
            <i class="fa-solid fa-circle-user me-1"></i>
            <?= e($user['full_name']) ?>
            <span class="badge bg-secondary ms-1"><?= e($user['role_name']) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-muted">
              Signed in as <strong><?= e($user['username']) ?></strong>
            </span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= e($config['app']['url']) ?>/change-password.php">
              <i class="fa-solid fa-key me-2"></i> Change password</a></li>
            <li><a class="dropdown-item text-danger" href="<?= e($config['app']['url']) ?>/logout.php">
              <i class="fa-solid fa-right-from-bracket me-2"></i> Sign out</a></li>
          </ul>
        </li>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container-fluid py-4">