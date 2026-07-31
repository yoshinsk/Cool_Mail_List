<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\BounceParser.php
 * DSNメールからReturn-PathトークンとStatusコードを抽出し、宛先停止へ反映する。
 */

final class BounceParser
{
    public static function processRaw(string $raw): array
    {
        $token = self::extractToken($raw);
        $statusCode = self::extractStatus($raw);
        $action = self::extractHeaderValue($raw, 'Action') ?: 'unknown';
        $diagnostic = self::extractHeaderValue($raw, 'Diagnostic-Code') ?: '';
        $queue = $token ? self::findQueue($token) : null;

        Database::execute(
            'INSERT INTO bounce_messages (organization_id, return_path_token, status_code, action, diagnostic, raw_message, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$queue['organization_id'] ?? null, $token, $statusCode, $action, $diagnostic, $raw]
        );

        if ($queue) {
            self::applyBounce($queue, $statusCode, $diagnostic);
        }

        return ['token' => $token, 'status_code' => $statusCode, 'action' => $action];
    }

    private static function findQueue(string $token): ?array
    {
        return Database::fetch(
            'SELECT mq.id, mq.recipient_id, mq.organization_id FROM mail_queue mq WHERE mq.return_path_token = ? LIMIT 1',
            [$token]
        );
    }

    private static function applyBounce(array $queue, ?string $statusCode, string $diagnostic): void
    {
        $hard = $statusCode !== null && str_starts_with($statusCode, '5.');
        Database::execute(
            'INSERT INTO bounce_events (mail_queue_id, recipient_id, status_code, diagnostic, is_hard, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [(int)$queue['id'], (int)$queue['recipient_id'], $statusCode, $diagnostic, $hard ? 1 : 0]
        );

        if ($hard) {
            Database::execute(
                'UPDATE recipients SET status = "hard_bounced", updated_at = NOW() WHERE id = ?',
                [(int)$queue['recipient_id']]
            );
            return;
        }

        $recentSoft = Database::fetch(
            'SELECT COUNT(*) AS count FROM bounce_events WHERE recipient_id = ? AND is_hard = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)',
            [(int)$queue['recipient_id']]
        );
        if ((int)($recentSoft['count'] ?? 0) >= 3) {
            Database::execute(
                'UPDATE recipients SET status = "soft_bounced", updated_at = NOW() WHERE id = ?',
                [(int)$queue['recipient_id']]
            );
        }
    }

    private static function extractToken(string $raw): ?string
    {
        if (preg_match('/\\+([a-z0-9_]+)@/i', $raw, $matches)) {
            return $matches[1];
        }
        if (preg_match('/return_path_token=([a-z0-9_]+)/i', $raw, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function extractStatus(string $raw): ?string
    {
        if (preg_match('/^Status:\\s*([245]\\.\\d+\\.\\d+)/mi', $raw, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function extractHeaderValue(string $raw, string $header): ?string
    {
        if (preg_match('/^' . preg_quote($header, '/') . ':\\s*(.+)$/mi', $raw, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
