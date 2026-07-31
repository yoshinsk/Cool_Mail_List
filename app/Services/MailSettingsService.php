<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\MailSettingsService.php
 * 固定Return-Path、システムSMTP、バウンスIMAPの設定をDB優先・.envフォールバックで提供する。
 */

final class MailSettingsService
{
    public static function update(array $input): void
    {
        $bounceBaseEmail = mb_strtolower(trim((string)($input['bounce_base_email'] ?? '')));
        if (!filter_var($bounceBaseEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('固定バウンスメールアドレスが不正です。');
        }
        $systemMailFrom = mb_strtolower(trim((string)($input['system_mail_from'] ?? '')));
        if (!filter_var($systemMailFrom, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('システムメールFromが不正です。');
        }

        self::setValue('bounce_base_email', $bounceBaseEmail);
        self::setValue('system_mail_from', $systemMailFrom);
        self::setValue('system_mail_from_name', trim((string)($input['system_mail_from_name'] ?? '')));
        self::setValue('system_smtp_host', trim((string)($input['system_smtp_host'] ?? '')));
        self::setValue('system_smtp_port', (string)max(1, (int)($input['system_smtp_port'] ?? 587)));
        self::setValue('system_smtp_encryption', self::normalizeEncryption((string)($input['system_smtp_encryption'] ?? 'tls')));
        self::setValue('system_smtp_user', trim((string)($input['system_smtp_user'] ?? '')));
        self::setSecretIfPresent('system_smtp_pass', (string)($input['system_smtp_pass'] ?? ''));

        self::setValue('bounce_imap_host', trim((string)($input['bounce_imap_host'] ?? '')));
        self::setValue('bounce_imap_port', (string)max(1, (int)($input['bounce_imap_port'] ?? 993)));
        self::setValue('bounce_imap_encryption', self::normalizeEncryption((string)($input['bounce_imap_encryption'] ?? 'ssl')));
        self::setValue('bounce_imap_user', trim((string)($input['bounce_imap_user'] ?? '')));
        self::setSecretIfPresent('bounce_imap_pass', (string)($input['bounce_imap_pass'] ?? ''));
        self::setValue('bounce_imap_mailbox', trim((string)($input['bounce_imap_mailbox'] ?? 'INBOX')) ?: 'INBOX');
        self::setValue('bounce_imap_search', trim((string)($input['bounce_imap_search'] ?? 'UNSEEN')) ?: 'UNSEEN');
        self::setValue('bounce_imap_mark_seen', !empty($input['bounce_imap_mark_seen']) ? '1' : '0');
    }

    public static function formValues(): array
    {
        return [
            'bounce_base_email' => self::bounceBaseEmail(),
            'system_mail_from' => self::value('system_mail_from', 'system_mail.from', ''),
            'system_mail_from_name' => self::value('system_mail_from_name', 'system_mail.from_name', 'Cool Mail List'),
            'system_smtp_host' => self::value('system_smtp_host', 'system_mail.smtp_host', ''),
            'system_smtp_port' => (string)self::intValue('system_smtp_port', 'system_mail.smtp_port', 587),
            'system_smtp_encryption' => self::value('system_smtp_encryption', 'system_mail.smtp_encryption', 'tls'),
            'system_smtp_user' => self::value('system_smtp_user', 'system_mail.smtp_user', ''),
            'system_smtp_pass_set' => SettingsService::isSecretSet('system_smtp_pass', (string)Config::get('system_mail.smtp_pass', '')),
            'bounce_imap_host' => self::value('bounce_imap_host', 'bounce_imap.host', ''),
            'bounce_imap_port' => (string)self::intValue('bounce_imap_port', 'bounce_imap.port', 993),
            'bounce_imap_encryption' => self::value('bounce_imap_encryption', 'bounce_imap.encryption', 'ssl'),
            'bounce_imap_user' => self::value('bounce_imap_user', 'bounce_imap.user', ''),
            'bounce_imap_pass_set' => SettingsService::isSecretSet('bounce_imap_pass', (string)Config::get('bounce_imap.pass', '')),
            'bounce_imap_mailbox' => self::value('bounce_imap_mailbox', 'bounce_imap.mailbox', 'INBOX'),
            'bounce_imap_search' => self::value('bounce_imap_search', 'bounce_imap.search', 'UNSEEN'),
            'bounce_imap_mark_seen' => self::boolValue('bounce_imap_mark_seen', 'bounce_imap.mark_seen', true),
        ];
    }

    public static function bounceBaseEmail(): string
    {
        return self::value('bounce_base_email', 'mail.bounce_base_email', '');
    }

    public static function systemSmtp(): array
    {
        return [
            'from' => self::value('system_mail_from', 'system_mail.from', ''),
            'from_name' => self::value('system_mail_from_name', 'system_mail.from_name', 'Cool Mail List'),
            'host' => self::value('system_smtp_host', 'system_mail.smtp_host', ''),
            'port' => self::intValue('system_smtp_port', 'system_mail.smtp_port', 587),
            'encryption' => self::value('system_smtp_encryption', 'system_mail.smtp_encryption', 'tls'),
            'user' => self::value('system_smtp_user', 'system_mail.smtp_user', ''),
            'pass' => SettingsService::getSecret('system_smtp_pass', (string)Config::get('system_mail.smtp_pass', '')) ?? '',
        ];
    }

    public static function bounceImap(): array
    {
        return [
            'host' => self::value('bounce_imap_host', 'bounce_imap.host', ''),
            'port' => self::intValue('bounce_imap_port', 'bounce_imap.port', 993),
            'encryption' => self::value('bounce_imap_encryption', 'bounce_imap.encryption', 'ssl'),
            'user' => self::value('bounce_imap_user', 'bounce_imap.user', ''),
            'pass' => SettingsService::getSecret('bounce_imap_pass', (string)Config::get('bounce_imap.pass', '')) ?? '',
            'mailbox' => self::value('bounce_imap_mailbox', 'bounce_imap.mailbox', 'INBOX'),
            'search' => self::value('bounce_imap_search', 'bounce_imap.search', 'UNSEEN'),
            'mark_seen' => self::boolValue('bounce_imap_mark_seen', 'bounce_imap.mark_seen', true),
        ];
    }

    private static function value(string $setting, string $config, string $default): string
    {
        return SettingsService::get($setting, (string)Config::get($config, $default)) ?? $default;
    }

    private static function intValue(string $setting, string $config, int $default): int
    {
        return max(1, (int)self::value($setting, $config, (string)$default));
    }

    private static function boolValue(string $setting, string $config, bool $default): bool
    {
        $value = SettingsService::get($setting);
        if ($value !== null) {
            return $value === '1';
        }
        return (bool)Config::get($config, $default);
    }

    private static function setValue(string $name, string $value): void
    {
        SettingsService::set($name, $value);
    }

    private static function setSecretIfPresent(string $name, string $value): void
    {
        $value = trim($value);
        if ($value !== '') {
            SettingsService::setSecret($name, $value);
        }
    }

    private static function normalizeEncryption(string $value): string
    {
        return in_array($value, ['tls', 'ssl', ''], true) ? $value : 'tls';
    }
}
