<?php
class LoginThrottle
{
    public const WINDOW_MINUTES = 15;
    public const MAX_ATTEMPTS   = 10;
    public const LOCK_MINUTES   = 3;

    public static function recordAttempt(string $identifier, ?string $ip, bool $success): void
    {
        Db::insert('login_attempts', [
            'identifier' => strtolower($identifier),
            'ip_address' => $ip,
            'success'    => $success ? 1 : 0,
        ]);
    }

    /** @return array{locked:bool, seconds_remaining:int, failures:int} */
    public static function check(string $identifier, ?string $ip): array
    {
        $since = date('Y-m-d H:i:s', time() - self::windowMinutes() * 60);

        $idFailures = (int)(Db::one(
            "SELECT COUNT(*) c FROM login_attempts
              WHERE identifier = :i AND success = 0 AND created_at >= :s",
            ['i' => strtolower($identifier), 's' => $since]
        )['c'] ?? 0);

        $ipFailures = $ip ? (int)(Db::one(
            "SELECT COUNT(*) c FROM login_attempts
              WHERE ip_address = :ip AND success = 0 AND created_at >= :s",
            ['ip' => $ip, 's' => $since]
        )['c'] ?? 0) : 0;

        $failures = max($idFailures, $ipFailures);
        if ($failures < self::maxAttempts()) {
            return ['locked' => false, 'seconds_remaining' => 0, 'failures' => $failures];
        }

        // Find the most recent failure to know when the lock expires
        $last = Db::one(
            "SELECT created_at FROM login_attempts
              WHERE (identifier = :i" . ($ip ? " OR ip_address = :ip" : "") . ")
                AND success = 0
              ORDER BY id DESC LIMIT 1",
            $ip ? ['i' => strtolower($identifier), 'ip' => $ip] : ['i' => strtolower($identifier)]
        );
        $unlockAt = strtotime($last['created_at']) + self::lockMinutes() * 60;
        $remaining = max(0, $unlockAt - time());

        return ['locked' => $remaining > 0, 'seconds_remaining' => $remaining, 'failures' => $failures];
    }

    /** Housekeeping: purge rows older than 24h. Cheap to call once per login page load. */
    public static function prune(): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - 86400);
        Db::q("DELETE FROM login_attempts WHERE created_at < :c", ['c' => $cutoff]);
    }

    private static function windowMinutes(): int
    {
        return setting_int('login_window_minutes', 15);
    }
    private static function maxAttempts(): int
    {
        return setting_int('login_max_attempts', 5);
    }
    private static function lockMinutes(): int
    {
        return setting_int('login_lock_minutes', 15);
    }
}
