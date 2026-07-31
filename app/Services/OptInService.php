<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\OptInService.php
 * 登録フォームとダブルオプトイン確認メールを扱う。
 */

final class OptInService
{
    public static function request(string $email, string $name, string $company, int $organizationId): void
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('メールアドレス形式が不正です。');
        }

        $existing = Database::fetch('SELECT id FROM recipients WHERE organization_id = ? AND email = ? LIMIT 1', [$organizationId, $email]);
        if ($existing) {
            $recipientId = (int)$existing['id'];
            Database::execute(
                'UPDATE recipients SET name = ?, company = ?, status = "pending_optin", updated_at = NOW() WHERE id = ?',
                [trim($name), trim($company), $recipientId]
            );
        } else {
            Database::execute(
                'INSERT INTO recipients (organization_id, email, name, company, source, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, "optin_form", "pending_optin", NOW(), NOW())',
                [$organizationId, $email, trim($name), trim($company)]
            );
            $recipientId = Database::lastInsertId();
        }

        $token = bin2hex(random_bytes(32));
        Database::execute(
            'INSERT INTO optin_tokens (recipient_id, token_hash, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())',
            [$recipientId, hash('sha256', $token)]
        );

        $url = route_url('confirm_optin', ['t' => $token]);
        $text = "メール配信登録の確認です。\n\n以下のURLを7日以内に開くと登録が完了します。\n{$url}\n\n心当たりがない場合は、このメールを破棄してください。";
        $sendResult = MailerService::sendSystemMail($email, 'Cool Mail List 配信登録確認', $text, nl2br(h($text)));
        if (!$sendResult['ok']) {
            AuditLogger::log('optin_mail_failed', ['email' => $email, 'error' => $sendResult['error'] ?? 'unknown']);
            throw new RuntimeException('確認メールの送信に失敗しました: ' . ($sendResult['error'] ?? 'unknown'));
        }

        AuditLogger::log('optin_requested', ['email' => $email]);
    }

    public static function confirm(string $token): ?string
    {
        $row = Database::fetch(
            'SELECT ot.*, r.email
             FROM optin_tokens ot
             JOIN recipients r ON r.id = ot.recipient_id
             WHERE ot.token_hash = ? AND ot.confirmed_at IS NULL AND ot.expires_at >= NOW()
             LIMIT 1',
            [hash('sha256', $token)]
        );
        if (!$row) {
            return null;
        }

        Database::execute('UPDATE optin_tokens SET confirmed_at = NOW() WHERE id = ?', [(int)$row['id']]);
        Database::execute('UPDATE recipients SET status = "active", updated_at = NOW() WHERE id = ?', [(int)$row['recipient_id']]);
        AuditLogger::log('optin_confirmed', ['email' => $row['email']]);
        return (string)$row['email'];
    }
}
