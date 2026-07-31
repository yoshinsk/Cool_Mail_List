<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\CryptoService.php
 * SMTPパスワードなどDB保存が必要な秘密値をAES-256-GCMで暗号化する。
 */

final class CryptoService
{
    public static function encrypt(string $plain): string
    {
        $key = self::key();
        $nonce = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) {
            throw new RuntimeException('暗号化に失敗しました。');
        }

        return base64_encode($nonce . $tag . $cipher);
    }

    public static function decrypt(?string $encoded): string
    {
        if ($encoded === null || $encoded === '') {
            return '';
        }

        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 29) {
            throw new RuntimeException('暗号文の形式が不正です。');
        }

        $nonce = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $nonce, $tag);
        if ($plain === false) {
            throw new RuntimeException('復号に失敗しました。');
        }

        return $plain;
    }

    private static function key(): string
    {
        $secret = (string)Config::get('app.key', '');
        if ($secret === '' || str_starts_with($secret, 'change-me')) {
            throw new RuntimeException('APP_KEY が未設定です。');
        }
        return hash('sha256', $secret, true);
    }
}
