<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\cron\send_queue.php
 * 毎分cronで起動し、PHPMailerで最大5通程度のキュー配信を行う。
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$result = QueueService::sendDue();
$line = sprintf(
    "[%s] sent=%d failed=%d skipped=%d %s\n",
    date('Y-m-d H:i:s'),
    $result['sent'],
    $result['failed'],
    $result['skipped'],
    $result['message']
);
file_put_contents(STORAGE_PATH . '/logs/send_queue.log', $line, FILE_APPEND);
echo $line;
