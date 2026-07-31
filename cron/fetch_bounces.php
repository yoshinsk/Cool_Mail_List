<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\cron\fetch_bounces.php
 * IMAP/POP定期取得方式に拡張するための入口。MTA pipeを優先し、現時点では設定未実装を記録する。
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$line = '[' . date('Y-m-d H:i:s') . "] IMAP/POP bounce fetch is not configured.\n";
file_put_contents(STORAGE_PATH . '/logs/fetch_bounces.log', $line, FILE_APPEND);
echo $line;
