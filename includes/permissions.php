<?php

/**
 * Central permission map.
 * role name => list of permission keys it holds.
 * '*' = wildcard (all permissions).
 */
function permission_map(): array
{
    return [
        'SUPER_ADMIN'   => ['*'],

        'PARISH_ADMIN'  => [
            'dashboard.view',
            'center.view',
            'center.create',
            'center.edit',
            'jumuiya.view',
            'jumuiya.create',
            'jumuiya.edit',
            'member.view',
            'member.create',
            'member.edit',
            'member.transfer',
            'payment.view',
            'payment.create',
            'payment.reverse',
            'report.view',
            'report.export',
            'user.view',
            'settings.view',
            'audit.view',
        ],

        'CENTER_ADMIN'  => [
            'dashboard.view',
            'jumuiya.view',
            'member.view',
            'member.create',
            'member.edit',
            'member.transfer',
            'payment.view',
            'payment.create',
            'payment.reverse',
            'report.view',
            'report.export',
        ],

        'JUMUIYA_ADMIN' => [
            'dashboard.view',
            'member.view',
            'member.create',
            'member.edit',
            'payment.view',
            'payment.create',
            'report.view',
        ],

        'VIEWER'        => [
            'dashboard.view',
            'member.view',
            'payment.view',
            'report.view',
        ],
    ];
}

function role_permissions(?string $roleName): array
{
    if (!$roleName) return [];
    $map = permission_map();
    return $map[$roleName] ?? [];
}

function user_can(string $permission): bool
{
    if (empty($_SESSION['user'])) return false;
    $perms = role_permissions($_SESSION['user']['role_name'] ?? null);
    return in_array('*', $perms, true) || in_array($permission, $perms, true);
}

function require_permission(string $permission): void
{
    if (!user_can($permission)) {
        http_response_code(403);
        // If AJAX, return JSON; else show friendly page.
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            json_out(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        require __DIR__ . '/../templates/errors/403.php';
        exit;
    }
}
