<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\BounceMailboxService.php
 * IMAPメールボックスからバウンスメールを取得し、BounceParserへ渡す。
 */

final class BounceMailboxService
{
    public static function fetch(): array
    {
        if (!function_exists('imap_open')) {
            throw new RuntimeException('PHP IMAP拡張が有効ではありません。');
        }

        $mailbox = self::mailboxString();
        $user = (string)Config::get('bounce_imap.user', '');
        $pass = (string)Config::get('bounce_imap.pass', '');
        if ($mailbox === '' || $user === '' || $pass === '') {
            throw new RuntimeException('バウンス取得用IMAP設定が未設定です。');
        }

        $lock = Database::fetch('SELECT GET_LOCK("cool_mail_list_fetch_bounces", 1) AS got_lock');
        if ((int)($lock['got_lock'] ?? 0) !== 1) {
            return ['processed' => 0, 'failed' => 0, 'message' => '別プロセスが実行中です。'];
        }

        try {
            $imap = @imap_open($mailbox, $user, $pass);
            if (!$imap) {
                throw new RuntimeException('IMAP接続に失敗しました: ' . (imap_last_error() ?: 'unknown'));
            }

            $numbers = imap_search($imap, (string)Config::get('bounce_imap.search', 'UNSEEN')) ?: [];
            $processed = 0;
            $failed = 0;
            foreach ($numbers as $number) {
                $header = imap_fetchheader($imap, (int)$number) ?: '';
                $body = imap_body($imap, (int)$number, FT_PEEK) ?: '';
                try {
                    BounceParser::processRaw($header . "\n" . $body);
                    if (Config::get('bounce_imap.mark_seen', true)) {
                        imap_setflag_full($imap, (string)$number, '\\Seen');
                    }
                    $processed++;
                } catch (Throwable $e) {
                    error_log('bounce fetch failed: ' . $e->getMessage());
                    $failed++;
                }
            }
            imap_close($imap);

            AuditLogger::log('bounce_mailbox_fetched', ['processed' => $processed, 'failed' => $failed]);
            return ['processed' => $processed, 'failed' => $failed, 'message' => ''];
        } finally {
            Database::fetch('SELECT RELEASE_LOCK("cool_mail_list_fetch_bounces")');
        }
    }

    private static function mailboxString(): string
    {
        $host = (string)Config::get('bounce_imap.host', '');
        $port = (int)Config::get('bounce_imap.port', 993);
        $encryption = (string)Config::get('bounce_imap.encryption', 'ssl');
        $folder = (string)Config::get('bounce_imap.mailbox', 'INBOX');
        if ($host === '') {
            return '';
        }

        $flags = '/imap';
        if ($encryption === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryption === 'tls') {
            $flags .= '/tls';
        } else {
            $flags .= '/notls';
        }

        return sprintf('{%s:%d%s}%s', $host, $port, $flags, $folder);
    }
}
