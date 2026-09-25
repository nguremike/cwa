<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../classes/Audit.php';

$auditDays = setting_int('housekeeping_audit_days', 730);
$loginDays = setting_int('housekeeping_login_days', 30);

Db::begin();
try {
    $deletedLogins = 0;
    if ($loginDays > 0) {
        $cutoff = date('Y-m-d H:i:s', time() - $loginDays * 86400);
        $deletedLogins = Db::q("DELETE FROM login_attempts WHERE created_at < :c", ['c' => $cutoff])->rowCount();
        Db::insert('housekeeping_log', ['task' => 'login_attempts', 'deleted' => $deletedLogins]);
    }

    $deletedAudit = 0;
    if ($auditDays > 0) {
        $cutoff = date('Y-m-d H:i:s', time() - $auditDays * 86400);
        $deletedAudit = Db::q("DELETE FROM audit_logs WHERE created_at < :c", ['c' => $cutoff])->rowCount();
        Db::insert('housekeeping_log', ['task' => 'audit_logs', 'deleted' => $deletedAudit]);
    }

    Db::commit();
    fwrite(STDOUT, "login_attempts purged: {$deletedLogins}\naudit_logs purged: {$deletedAudit}\n");
} catch (Throwable $e) {
    Db::rollback();
    fwrite(STDERR, "Housekeeping failed: " . $e->getMessage() . "\n");
    exit(1);
}
