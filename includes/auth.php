<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Audit.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/session_guard.php';
session_guard_run();


if (!Auth::check()) {
    // Remember where they were going (optional)
    $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
    header('Location: ' . $config['app']['url'] . '/login.php');
    exit;
}

// if (isset($_GET['logout'])) {
//     Auth::logout();
//     header('Location: ' . $config['app']['url'] . '/login.php');
//     exit;
// }