<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

if ($config['app']['env'] === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/../classes/Db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/parish.php';
require_once __DIR__ . '/../classes/FinancialYear.php';
require_once __DIR__ . '/schedule_lock.php';
require_once __DIR__ . '/../classes/Member.php';
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../classes/AllocationEngine.php';
require_once __DIR__ . '/../classes/Report.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/../classes/ImportRunner.php';


$vendor = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendor)) require_once $vendor;




if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session']['name']);
    session_start();
}
