<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/classes/Auth.php';
require __DIR__ . '/classes/Audit.php';

Auth::logout();
$config = require __DIR__ . '/config/config.php';
header('Location: ' . $config['app']['url'] . '/login.php');
exit;
