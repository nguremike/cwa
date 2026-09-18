<?php
class Audit
{
    public static function log(
        string $action,
        ?string $table = null,
        ?int $recordId = null,
        ?array $old = null,
        ?array $new = null
    ): void {
        try {
            Db::insert('audit_logs', [
                'user_id'    => $_SESSION['user']['id'] ?? null,
                'action'     => $action,
                'table_name' => $table,
                'record_id'  => $recordId,
                'old_values' => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (Throwable $e) {
            // Never let audit failure break the app; log to file instead
            error_log('[AUDIT] ' . $e->getMessage());
        }
    }
}
