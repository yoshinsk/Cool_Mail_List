<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\cron\fetch_bounces.php
 * IMAP定期取得方式でバウンスメールを読み取り、解析結果をDBへ反映する。
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$result = BounceMailboxService::fetch();
$line = sprintf(
    "[%s] processed=%d failed=%d %s\n",
    date('Y-m-d H:i:s'),
    $result['processed'],
    $result['failed'],
    $result['message']
);
file_put_contents(STORAGE_PATH . '/logs/fetch_bounces.log', $line, FILE_APPEND);
echo $line;
