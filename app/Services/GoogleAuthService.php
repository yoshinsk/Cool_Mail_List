<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\GoogleAuthService.php
 * Google Identity ServicesのIDトークンを検証し、承認済み利用者のログインまたは連携を行う。
 */

final class GoogleAuthService
{
    public static function settings(): array
    {
        return [
            'client_id' => SettingsService::get('google_client_id', (string)Config::get('google.client_id', '')) ?? '',
            'allowed_domain' => SettingsService::get('google_allowed_domain', (string)Config::get('google.allowed_domain', '')) ?? '',
        ];
    }

    public static function updateSettings(array $input): void
    {
        SettingsService::set('google_client_id', trim((string)($input['google_client_id'] ?? '')));
        SettingsService::set('google_allowed_domain', strtolower(trim((string)($input['google_allowed_domain'] ?? ''))));
    }

    public static function handleCredential(string $credential): array
    {
        $settings = self::settings();
        if ($settings['client_id'] === '') {
            throw new RuntimeException('Google Client ID が未設定です。');
        }

        $payload = self::verifyIdToken($credential, $settings['client_id']);
        $allowedDomain = $settings['allowed_domain'];
        if ($allowedDomain !== '' && (($payload['hd'] ?? '') !== $allowedDomain)) {
            throw new RuntimeException('許可されていないGoogle Workspaceドメインです。');
        }

        if (empty($payload['email']) || empty($payload['email_verified'])) {
            throw new RuntimeException('Googleアカウントのメール確認状態を検証できません。');
        }

        $email = strtolower((string)$payload['email']);
        $sub = (string)$payload['sub'];
        $user = Database::fetch('SELECT * FROM users WHERE google_sub = ? LIMIT 1', [$sub]);
        if (!$user) {
            $user = Database::fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
            if ($user && $user['status'] === 'active' && !empty($user['approved_at'])) {
                Database::execute(
                    'UPDATE users SET google_sub = ?, email_verified_at = COALESCE(email_verified_at, NOW()), updated_at = NOW() WHERE id = ?',
                    [$sub, (int)$user['id']]
                );
                self::upsertGoogleAccount((int)$user['id'], $sub, $email, true);
            }
        }

        if (!$user) {
            Database::execute(
                'INSERT INTO users (organization_id, email, password_hash, role, status, google_sub, email_verified_at, created_at, updated_at)
                 VALUES (?, ?, "", "viewer", "pending_approval", ?, NOW(), NOW(), NOW())',
                [OrganizationService::defaultId(), $email, $sub]
            );
            $userId = Database::lastInsertId();
            self::upsertGoogleAccount($userId, $sub, $email, true);
            AuditLogger::log('google_user_registered', ['email' => $email], $userId);
            return ['ok' => false, 'pending' => true, 'email' => $email];
        }

        if ($user['status'] !== 'active' || empty($user['approved_at'])) {
            AuditLogger::log('google_login_pending', ['email' => $email], (int)$user['id']);
            return ['ok' => false, 'pending' => true, 'email' => $email];
        }

        Session::regenerate();
        $_SESSION['user_id'] = (int)$user['id'];
        Database::execute('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?', [(int)$user['id']]);
        AuditLogger::log('google_login_success', ['email' => $email], (int)$user['id']);
        return ['ok' => true, 'pending' => false, 'email' => $email];
    }

    private static function verifyIdToken(string $jwt, string $audience): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new RuntimeException('Google IDトークン形式が不正です。');
        }

        $header = json_decode(self::base64UrlDecode($parts[0]), true);
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        if (!is_array($header) || !is_array($payload)) {
            throw new RuntimeException('Google IDトークンを解析できません。');
        }
        if (($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
            throw new RuntimeException('Google IDトークン署名方式が不正です。');
        }

        $cert = self::certificates()[(string)$header['kid']] ?? null;
        if (!$cert) {
            throw new RuntimeException('Google IDトークン検証用証明書が見つかりません。');
        }

        $signed = $parts[0] . '.' . $parts[1];
        $signature = self::base64UrlDecode($parts[2]);
        if (openssl_verify($signed, $signature, $cert, OPENSSL_ALGO_SHA256) !== 1) {
            throw new RuntimeException('Google IDトークン署名検証に失敗しました。');
        }
        if (($payload['aud'] ?? '') !== $audience) {
            throw new RuntimeException('Google IDトークンのaudが一致しません。');
        }
        if (!in_array(($payload['iss'] ?? ''), ['accounts.google.com', 'https://accounts.google.com'], true)) {
            throw new RuntimeException('Google IDトークンのissが不正です。');
        }
        if ((int)($payload['exp'] ?? 0) < time()) {
            throw new RuntimeException('Google IDトークンが期限切れです。');
        }
        if (empty($payload['sub'])) {
            throw new RuntimeException('Google IDトークンにsubがありません。');
        }

        return $payload;
    }

    private static function certificates(): array
    {
        $cachePath = STORAGE_PATH . '/google-certs.json';
        if (is_file($cachePath)) {
            $cached = json_decode((string)file_get_contents($cachePath), true);
            if (is_array($cached) && (int)($cached['expires_at'] ?? 0) > time() && is_array($cached['certs'] ?? null)) {
                return $cached['certs'];
            }
        }

        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $body = file_get_contents('https://www.googleapis.com/oauth2/v1/certs', false, $context);
        if ($body === false) {
            throw new RuntimeException('Google証明書を取得できません。');
        }
        $certs = json_decode($body, true);
        if (!is_array($certs)) {
            throw new RuntimeException('Google証明書を解析できません。');
        }
        file_put_contents($cachePath, json_encode(['expires_at' => time() + 3600, 'certs' => $certs], JSON_UNESCAPED_SLASHES));
        return $certs;
    }

    private static function upsertGoogleAccount(int $userId, string $sub, string $email, bool $verified): void
    {
        Database::execute(
            'INSERT INTO user_google_accounts (user_id, google_sub, email, email_verified, created_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE email = VALUES(email), email_verified = VALUES(email_verified)',
            [$userId, $sub, $email, $verified ? 1 : 0]
        );
    }

    private static function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4)) ?: '';
    }
}
