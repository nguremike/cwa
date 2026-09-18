<?php

/**
 * Single-parish helper.
 * The system supports exactly one parish. All UI/pages call parish_current().
 */
function parish_current(): ?array
{
    static $cached = null;
    if ($cached !== null) return $cached;
    $cached = Db::one("SELECT * FROM parishes ORDER BY id ASC LIMIT 1");
    return $cached;
}

function parish_required(): array
{
    $p = parish_current();
    if (!$p) {
        header('Location: ' . (require __DIR__ . '/../config/config.php')['app']['url'] . '/setup-parish.php');
        exit;
    }
    return $p;
}
