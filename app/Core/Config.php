<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Core\Config.php
 * .env 由来の値をアプリ内で安全に参照するための設定管理クラス。
 */

final class Config
{
    private static array $values = [];

    public static function load(array $env): void
    {
        self::$values = [
            'app.name' => $env['APP_NAME'] ?? 'Cool Mail List',
            'app.env' => $env['APP_ENV'] ?? 'production',
            'app.url' => $env['APP_URL'] ?? '',
            'app.timezone' => $env['APP_TIMEZONE'] ?? 'Asia/Tokyo',
            'app.key' => $env['APP_KEY'] ?? '',
            'app.cookie_secure' => ($env['APP_COOKIE_SECURE'] ?? '1') === '1',
            'db.host' => $env['DB_HOST'] ?? 'localhost',
            'db.port' => (int)($env['DB_PORT'] ?? 3306),
            'db.name' => $env['DB_NAME'] ?? '',
            'db.user' => $env['DB_USER'] ?? '',
            'db.pass' => $env['DB_PASS'] ?? '',
            'queue.batch_limit' => max(1, (int)($env['QUEUE_BATCH_LIMIT'] ?? 5)),
            'mail.bounce_domain' => $env['BOUNCE_DOMAIN'] ?? '',
            'mail.default_from_name' => $env['DEFAULT_FROM_NAME'] ?? 'Cool Mail List',
            'system_mail.from' => $env['SYSTEM_MAIL_FROM'] ?? '',
            'system_mail.from_name' => $env['SYSTEM_MAIL_FROM_NAME'] ?? 'Cool Mail List',
            'system_mail.smtp_host' => $env['SYSTEM_SMTP_HOST'] ?? '',
            'system_mail.smtp_port' => (int)($env['SYSTEM_SMTP_PORT'] ?? 587),
            'system_mail.smtp_encryption' => $env['SYSTEM_SMTP_ENCRYPTION'] ?? 'tls',
            'system_mail.smtp_user' => $env['SYSTEM_SMTP_USER'] ?? '',
            'system_mail.smtp_pass' => $env['SYSTEM_SMTP_PASS'] ?? '',
            'bounce_imap.host' => $env['BOUNCE_IMAP_HOST'] ?? '',
            'bounce_imap.port' => (int)($env['BOUNCE_IMAP_PORT'] ?? 993),
            'bounce_imap.encryption' => $env['BOUNCE_IMAP_ENCRYPTION'] ?? 'ssl',
            'bounce_imap.user' => $env['BOUNCE_IMAP_USER'] ?? '',
            'bounce_imap.pass' => $env['BOUNCE_IMAP_PASS'] ?? '',
            'bounce_imap.mailbox' => $env['BOUNCE_IMAP_MAILBOX'] ?? 'INBOX',
            'bounce_imap.search' => $env['BOUNCE_IMAP_SEARCH'] ?? 'UNSEEN',
            'bounce_imap.mark_seen' => ($env['BOUNCE_IMAP_MARK_SEEN'] ?? '1') === '1',
            'google.client_id' => $env['GOOGLE_CLIENT_ID'] ?? '',
            'google.allowed_domain' => $env['GOOGLE_ALLOWED_DOMAIN'] ?? '',
            'openai.api_key' => $env['OPENAI_API_KEY'] ?? '',
            'openai.model' => $env['OPENAI_MODEL'] ?? 'gpt-5.6',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$values[$key] ?? $default;
    }
}
