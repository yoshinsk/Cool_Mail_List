<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\AuditLogger.php
 * 認証、設定変更、配信操作などの監査ログをDBへ保存する。
 */

final class AuditLogger
{
    public static function log(string $action, array $details = [], ?int $userId = null): void
    {
        try {
            $resolvedUserId = $userId ?? ($_SESSION['user_id'] ?? null);
            Database::execute(
                'INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details_json, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())',
                [
                    $resolvedUserId,
                    $action,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (Throwable $e) {
            error_log('audit log failed: ' . $e->getMessage());
        }
    }
}
