<?php
class Auth
{
    public static function attempt(string $username, string $password): array
    {
        $user = Db::one(
            "SELECT u.*, r.name AS role_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.username = :u
              LIMIT 1",
            ['u' => $username]
        );

        if (!$user) {
            return ['ok' => false, 'error' => 'Invalid username or password.'];
        }
        if ($user['status'] !== 'ACTIVE') {
            return ['ok' => false, 'error' => 'Account is not active. Contact administrator.'];
        }
        if (!password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Invalid username or password.'];
        }

        // Optional: transparent rehash if cost changed
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT)) {
            Db::update(
                'users',
                ['password_hash' => password_hash($password, PASSWORD_BCRYPT)],
                'id = :id',
                ['id' => $user['id']]
            );
        }

        // Store minimal session payload
        $_SESSION['user'] = [
            'id'         => (int)$user['id'],
            'username'   => $user['username'],
            'full_name'  => $user['full_name'],
            'role_id'    => (int)$user['role_id'],
            'role_name'  => $user['role_name'],
            'center_id'  => $user['center_id'] !== null ? (int)$user['center_id'] : null,
            'jumuiya_id' => $user['jumuiya_id'] !== null ? (int)$user['jumuiya_id'] : null,
        ];

        Db::update(
            'users',
            ['last_login_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );

        Audit::log('LOGIN', 'users', (int)$user['id'], null, null);

        return ['ok' => true];
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function logout(): void
    {
        if (self::check()) {
            Audit::log('LOGOUT', 'users', self::id(), null, null);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }
        session_destroy();
    }
}
