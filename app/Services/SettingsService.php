<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\SettingsService.php
 * DB保存型のシステム設定を扱い、APIキーなどの秘密値は暗号化して保存する。
 */

final class SettingsService
{
    public static function get(string $name, ?string $default = null): ?string
    {
        $row = Database::fetch('SELECT value FROM settings WHERE name = ? LIMIT 1', [$name]);
        return $row ? (string)$row['value'] : $default;
    }

    public static function set(string $name, ?string $value): void
    {
        Database::execute(
            'INSERT INTO settings (name, value, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()',
            [$name, $value]
        );
    }

    public static function getSecret(string $name, ?string $fallback = null): ?string
    {
        $value = self::get($name);
        if ($value === null || $value === '') {
            return $fallback;
        }
        if (!str_starts_with($value, 'secret:')) {
            return $value;
        }
        return CryptoService::decrypt(substr($value, 7));
    }

    public static function setSecret(string $name, string $plain): void
    {
        self::set($name, 'secret:' . CryptoService::encrypt($plain));
    }

    public static function isSecretSet(string $name, ?string $fallback = null): bool
    {
        $value = self::get($name);
        return ($value !== null && $value !== '') || ($fallback !== null && $fallback !== '');
    }
}
