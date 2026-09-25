<?php
// /var/www/html/cwa/health.php
header('Content-Type: application/json');

$start = microtime(true);
$checks = [];
$overall = 'ok';

// 1) Config present
$configPath = __DIR__ . '/config/config.php';
$checks['config'] = is_file($configPath) ? 'ok' : 'missing';
if ($checks['config'] !== 'ok') $overall = 'fail';

// 2) DB connect
$config = is_file($configPath) ? require $configPath : null;
try {
    if (!$config) throw new RuntimeException('no config');
    $db = $config['db'];
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
        $db['username'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
    );
    $checks['db'] = 'ok';
} catch (Throwable $e) {
    $checks['db'] = 'fail';
    $overall = 'fail';
}

// 3) Writable storage
$checks['storage_writable'] = is_writable(__DIR__ . '/storage') ? 'ok' : 'fail';
if ($checks['storage_writable'] !== 'ok') $overall = 'fail';

// 4) Vendor autoload
$checks['vendor_autoload'] = is_file(__DIR__ . '/vendor/autoload.php') ? 'ok' : 'missing';
if ($checks['vendor_autoload'] !== 'ok') $overall = $overall === 'fail' ? 'fail' : 'warn';

// 5) Disk free
$free = @disk_free_space(__DIR__);
$checks['disk_free_mb'] = $free !== false ? (int)round($free / 1048576) : null;
if ($checks['disk_free_mb'] !== null && $checks['disk_free_mb'] < 500) {
    $overall = 'warn';
}

// 6) Latest successful backup
$bdir = __DIR__ . '/storage/backups';
$latest = null;
if (is_dir($bdir)) {
    foreach (scandir($bdir) as $f) {
        if (str_starts_with($f, 'db-') && str_ends_with($f, '.sql.gz')) {
            $t = filemtime("$bdir/$f");
            if ($latest === null || $t > $latest) $latest = $t;
        }
    }
}
$checks['latest_db_backup'] = $latest ? date('Y-m-d H:i', $latest) : null;
if ($latest && (time() - $latest) > 48 * 3600) $overall = 'warn';

// 7) Current financial year
try {
    if (isset($pdo)) {
        $row = $pdo->query("SELECT year FROM financial_years WHERE is_current = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $checks['current_year'] = $row['year'] ?? null;
        if (!$row) $overall = 'warn';
    }
} catch (Throwable $e) {
    // ignore
}

$checks['php_version']  = PHP_VERSION;
$checks['mysql_client'] = isset($pdo) ? ($pdo->getAttribute(PDO::ATTR_SERVER_VERSION) ?? null) : null;
$checks['duration_ms']  = (int)round((microtime(true) - $start) * 1000);

http_response_code($overall === 'fail' ? 503 : 200);
echo json_encode(['status' => $overall, 'checks' => $checks], JSON_PRETTY_PRINT);
