<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Core\Auth.php
 * メールアドレス/パスワード認証、承認状態、ロール判定を提供する。
 */

final class Auth
{
    private static ?array $user = null;

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }

        self::$user = Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [(int)$id]);
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(string $email, string $password): bool
    {
        $user = Database::fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [mb_strtolower(trim($email))]);
        if (!$user) {
            AuditLogger::log('login_failed', ['email' => $email, 'reason' => 'not_found']);
            return false;
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            AuditLogger::log('login_failed', ['email' => $email, 'reason' => 'locked']);
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $failed = (int)$user['failed_login_count'] + 1;
            $lockedUntil = $failed >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
            Database::execute(
                'UPDATE users SET failed_login_count = ?, locked_until = ?, updated_at = NOW() WHERE id = ?',
                [$failed, $lockedUntil, (int)$user['id']]
            );
            AuditLogger::log('login_failed', ['email' => $email, 'reason' => 'password']);
            return false;
        }

        if ($user['status'] !== 'active' || empty($user['approved_at'])) {
            AuditLogger::log('login_failed', ['email' => $email, 'reason' => 'not_approved']);
            return false;
        }

        Database::execute(
            'UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW(), updated_at = NOW() WHERE id = ?',
            [(int)$user['id']]
        );
        Session::regenerate();
        $_SESSION['user_id'] = (int)$user['id'];
        self::$user = $user;
        AuditLogger::log('login_success', ['email' => $email], (int)$user['id']);
        return true;
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user) {
            AuditLogger::log('logout', ['email' => $user['email']], (int)$user['id']);
        }
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::$user = null;
    }

    public static function requireRole(array $roles): void
    {
        $user = self::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            exit('権限がありません。');
        }
    }

    public static function canManage(): bool
    {
        $role = self::user()['role'] ?? '';
        return in_array($role, ['system_admin', 'delivery_admin'], true);
    }
}
