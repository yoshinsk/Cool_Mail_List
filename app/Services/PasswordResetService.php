<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\PasswordResetService.php
 * パスワード再設定トークンを発行し、システムSMTPで再設定メールを送信する。
 */

final class PasswordResetService
{
    public static function request(string $email): void
    {
        $email = mb_strtolower(trim($email));
        $user = Database::fetch('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1', [$email]);
        if (!$user) {
            AuditLogger::log('password_reset_requested', ['email' => $email, 'matched' => false]);
            return;
        }

        $token = bin2hex(random_bytes(32));
        Database::execute(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())',
            [(int)$user['id'], hash('sha256', $token)]
        );

        $url = route_url('reset_password', ['t' => $token]);
        $subject = 'Cool Mail List パスワード再設定';
        $text = "パスワード再設定の依頼を受け付けました。\n\n以下のURLを1時間以内に開いて、新しいパスワードを設定してください。\n{$url}\n\nこの依頼に心当たりがない場合は、このメールを破棄してください。";
        $sendResult = MailerService::sendSystemMail($email, $subject, $text, nl2br(h($text)));
        AuditLogger::log(
            'password_reset_requested',
            ['email' => $email, 'matched' => true, 'mail_sent' => $sendResult['ok']],
            (int)$user['id']
        );
    }

    public static function findValid(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        return Database::fetch(
            'SELECT pr.*, u.email
             FROM password_resets pr
             JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at >= NOW()
             LIMIT 1',
            [hash('sha256', $token)]
        );
    }

    public static function reset(string $token, string $password): bool
    {
        $reset = self::findValid($token);
        if (!$reset || strlen($password) < 12) {
            return false;
        }

        Database::execute(
            'UPDATE users SET password_hash = ?, failed_login_count = 0, locked_until = NULL, updated_at = NOW() WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), (int)$reset['user_id']]
        );
        Database::execute('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [(int)$reset['id']]);
        AuditLogger::log('password_reset_completed', ['email' => $reset['email']], (int)$reset['user_id']);
        return true;
    }
}
