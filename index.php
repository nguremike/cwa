<!-- create link to login if already logged in create link to dashbord/index.php -->
<!-- show php errors -->
<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require __DIR__ . '/classes/Auth.php';

$config = require __DIR__ . '/config/config.php';


if (Auth::check()) {
    header('Location: ' . $config['app']['url'] . '/dashboard/index.php');
    exit;
} else {
    header('Location: ' . $config['app']['url'] . '/login.php');
    exit;
}

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/login.php';
require __DIR__ . '/templates/layout/footer.php';
?>