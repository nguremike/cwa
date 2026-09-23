<?php

/**
 * Session hardening.
 *  - Rotates the session ID every 30 minutes.
 *  - Enforces an idle timeout of 90 minutes.
 *  - Forces re-login if the client IP changes mid-session.
 */

const CWA_SESSION_ROTATE_SECONDS = 1800;   // 30 min
const CWA_SESSION_IDLE_SECONDS   = 5400;   // 90 min

function session_guard_run(): void
{
    $now = time();

    // Idle timeout
    if (!empty($_SESSION['last_seen']) && ($now - $_SESSION['last_seen']) > CWA_SESSION_IDLE_SECONDS) {
        Auth::logout();
        header('Location: ' . (require __DIR__ . '/../config/config.php')['app']['url'] . '/login.php?timeout=1');
        exit;
    }

    // Periodic rotation
    if (empty($_SESSION['rotated_at']) || ($now - $_SESSION['rotated_at']) > CWA_SESSION_ROTATE_SECONDS) {
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
