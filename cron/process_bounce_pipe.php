<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\cron\process_bounce_pipe.php
 * MTAからpipeされたバウンスメールを標準入力で受け取り、DSN解析結果をDBに保存する。
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$raw = stream_get_contents(STDIN);
if ($raw === false || trim($raw) === '') {
    fwrite(STDERR, "empty message\n");
    exit(1);
}

$result = BounceParser::processRaw($raw);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
