<?php

/**
 * Runtime settings accessor with an in-request cache.
 */
function setting_get(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (Db::all("SELECT setting_key, setting_value FROM settings") as $r) {
            $cache[$r['setting_key']] = $r['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function setting_int(string $key, int $default = 0): int
{
    $v = setting_get($key, null);
    return $v === null ? $default : (int)$v;
}

function setting_bool(string $key, bool $default = false): bool
{
    $v = setting_get($key, null);
    return $v === null ? $default : ((int)$v === 1);
}

function setting_set(string $key, string $value): void
{
    Db::q(
        "INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        ['k' => $key, 'v' => $value]
    );
}
