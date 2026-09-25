<?php

/**
 * Session hardening.
 *  - Rotates the session ID every 30 minutes.
 *  - Enforces an idle timeout of 90 minutes.
 *  - Forces re-login if the client IP changes mid-session.
 */

// const CWA_SESSION_ROTATE_SECONDS = 1800;   // 30 min
// const CWA_SESSION_IDLE_SECONDS   = 5400;   // 90 min
// $rotateSeconds = setting_int('session_rotate_seconds', 1800);
// $idleSeconds   = setting_int('session_idle_seconds',   5400);

function session_guard_run(): void
{
    $now = time();
    $rotateSeconds = setting_int('session_rotate_seconds', 1800);
    $idleSeconds   = setting_int('session_idle_seconds',   5400);


    // Idle timeout
    if (!empty($_SESSION['last_seen']) && ($now - $_SESSION['last_seen']) > $idleSeconds) {
        Auth::logout();
        header('Location: ' . (require __DIR__ . '/../config/config.php')['app']['url'] . '/login.php?timeout=1');
        exit;
    }

    // Periodic rotation
    if (empty($_SESSION['rotated_at']) || ($now - $_SESSION['rotated_at']) > $rotateSeconds) {
        session_regenerate_id(true);
        $_SESSION['rotated_at'] = $now;
    }

    // IP change → force re-login (unless behind a known proxy header)
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip) {
        if (empty($_SESSION['login_ip'])) {
            $_SESSION['login_ip'] = $ip;
        } elseif ($_SESSION['login_ip'] !== $ip) {
            Auth::logout();
            header('Location: ' . (require __DIR__ . '/../config/config.php')['app']['url'] . '/login.php?ipchange=1');
            exit;
        }
    }

    $_SESSION['last_seen']  = $now;
    $_SESSION['rotated_at'] = $_SESSION['rotated_at'] ?? $now;
}
