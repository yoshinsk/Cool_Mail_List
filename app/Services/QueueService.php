<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\QueueService.php
 * キャンペーンから宛先別キューを生成し、cronから低速配信する。
 */

final class QueueService
{
    public static function queueCampaign(int $campaignId): int
    {
        $campaign = Database::fetch(
            'SELECT c.*, si.smtp_account_id
             FROM campaigns c
             JOIN sender_identities si ON si.id = c.sender_identity_id
             WHERE c.id = ?',
            [$campaignId]
        );
        if (!$campaign) {
            throw new RuntimeException('キャンペーンが見つかりません。');
        }

        $recipients = Database::fetchAll(
            'SELECT id FROM recipients
             WHERE status = "active"
             AND NOT EXISTS (SELECT 1 FROM unsubscribes u WHERE u.recipient_id = recipients.id)
             ORDER BY id'
        );

        $created = 0;
        foreach ($recipients as $recipient) {
            $exists = Database::fetch(
                'SELECT id FROM mail_queue WHERE campaign_id = ? AND recipient_id = ? LIMIT 1',
                [$campaignId, (int)$recipient['id']]
            );
            if ($exists) {
                continue;
            }
            Database::execute(
                'INSERT INTO mail_queue
                    (campaign_id, recipient_id, sender_identity_id, smtp_account_id, scheduled_at, status, return_path_token, unsubscribe_token, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, "pending", ?, ?, NOW(), NOW())',
                [
                    $campaignId,
                    (int)$recipient['id'],
                    (int)$campaign['sender_identity_id'],
                    (int)$campaign['smtp_account_id'],
                    $campaign['scheduled_at'],
                    self::token('rp'),
                    self::token('unsub'),
                ]
            );
            $created++;
        }

        Database::execute('UPDATE campaigns SET status = "queued", updated_at = NOW() WHERE id = ?', [$campaignId]);
        AuditLogger::log('campaign_queued', ['campaign_id' => $campaignId, 'created' => $created]);
        return $created;
    }

    public static function sendDue(?int $limit = null): array
    {
        $limit = $limit ?? (int)Config::get('queue.batch_limit', 5);
        $lock = Database::fetch('SELECT GET_LOCK("cool_mail_list_send_queue", 1) AS got_lock');
        if ((int)($lock['got_lock'] ?? 0) !== 1) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => '別プロセスが実行中です。'];
        }

        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => ''];
        try {
            $items = Database::fetchAll(self::queueSql((int)$limit));
            foreach ($items as $item) {
                Database::execute('UPDATE mail_queue SET status = "sending", updated_at = NOW() WHERE id = ?', [(int)$item['queue_id']]);
                if ($item['recipient_status'] !== 'active') {
                    self::markSkipped((int)$item['queue_id'], '宛先が停止されています。');
                    $result['skipped']++;
                    continue;
                }

                $sendResult = MailerService::sendQueueItem($item);
                if ($sendResult['ok']) {
                    self::markSent((int)$item['queue_id'], $sendResult);
                    $result['sent']++;
                } else {
                    self::markFailed((int)$item['queue_id'], $sendResult);
                    $result['failed']++;
                }
            }
            self::refreshCampaignStatus();
        } finally {
            Database::fetch('SELECT RELEASE_LOCK("cool_mail_list_send_queue")');
        }

        return $result;
    }

    private static function queueSql(int $limit): string
    {
        return 'SELECT
                    mq.id AS queue_id, mq.return_path_token, mq.unsubscribe_token,
                    r.email AS recipient_email, r.name AS recipient_name, r.company AS recipient_company, r.status AS recipient_status,
                    si.from_name, si.from_email, si.reply_to, si.bounce_email,
                    sa.smtp_host, sa.smtp_port, sa.encryption, sa.auth_username, sa.auth_password_ciphertext,
                    COALESCE(c.subject_override, mt.subject) AS subject, mt.body_text, mt.body_html
                FROM mail_queue mq
                JOIN recipients r ON r.id = mq.recipient_id
                JOIN campaigns c ON c.id = mq.campaign_id
                JOIN sender_identities si ON si.id = mq.sender_identity_id
                JOIN smtp_accounts sa ON sa.id = mq.smtp_account_id
                JOIN mail_templates mt ON mt.id = c.template_id
                WHERE mq.status IN ("pending", "temporary_failed")
                  AND mq.scheduled_at <= NOW()
                  AND c.status IN ("queued", "sending")
                  AND sa.is_active = 1
                  AND si.is_active = 1
                ORDER BY mq.scheduled_at, mq.id
                LIMIT ' . $limit;
    }

    private static function markSent(int $queueId, array $sendResult): void
    {
        Database::execute(
            'UPDATE mail_queue
             SET status = "sent", sent_at = NOW(), message_id = ?, smtp_response_code = ?, error_message = NULL, updated_at = NOW()
             WHERE id = ?',
            [$sendResult['message_id'] ?? null, $sendResult['smtp_response'] ?? null, $queueId]
        );
        Database::execute(
            'INSERT INTO mail_send_logs (mail_queue_id, status, smtp_response, created_at) VALUES (?, "sent", ?, NOW())',
            [$queueId, $sendResult['smtp_response'] ?? null]
        );
    }

    private static function markFailed(int $queueId, array $sendResult): void
    {
        $status = !empty($sendResult['permanent']) ? 'permanent_failed' : 'temporary_failed';
        Database::execute(
            'UPDATE mail_queue
             SET status = ?, retry_count = retry_count + 1, error_message = ?, smtp_response_code = ?, updated_at = NOW()
             WHERE id = ?',
            [$status, $sendResult['error'] ?? '送信失敗', $sendResult['smtp_response'] ?? null, $queueId]
        );
        Database::execute(
            'INSERT INTO mail_send_logs (mail_queue_id, status, smtp_response, error_message, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$queueId, $status, $sendResult['smtp_response'] ?? null, $sendResult['error'] ?? null]
        );
    }

    private static function markSkipped(int $queueId, string $reason): void
    {
        Database::execute(
            'UPDATE mail_queue SET status = "skipped", error_message = ?, updated_at = NOW() WHERE id = ?',
            [$reason, $queueId]
        );
    }

    private static function refreshCampaignStatus(): void
    {
        Database::execute(
            'UPDATE campaigns c
             SET c.status = "sending", c.updated_at = NOW()
             WHERE c.status = "queued"
             AND EXISTS (SELECT 1 FROM mail_queue mq WHERE mq.campaign_id = c.id AND mq.status = "sent")'
        );
        Database::execute(
            'UPDATE campaigns c
             SET c.status = "completed", c.updated_at = NOW()
             WHERE c.status IN ("queued", "sending")
             AND NOT EXISTS (SELECT 1 FROM mail_queue mq WHERE mq.campaign_id = c.id AND mq.status IN ("pending", "sending", "temporary_failed"))'
        );
    }

    private static function token(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(16));
    }
}
