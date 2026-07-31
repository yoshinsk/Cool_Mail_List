<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Core\Csrf.php
 * フォーム送信のCSRFトークン生成と検証を行う。
 */

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . h(self::token()) . '">';
    }

    public static function requireValid(): void
    {
        $submitted = $_POST['_csrf'] ?? '';
        $current = $_SESSION['_csrf'] ?? '';
        if (!is_string($submitted) || !is_string($current) || !hash_equals($current, $submitted)) {
            http_response_code(419);
            exit('CSRF token mismatch.');
        }
    }
}
