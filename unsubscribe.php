<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\unsubscribe.php
 * 購読停止URLを短く保つための公開ルート用ラッパー。
 */

$_GET['r'] = 'unsubscribe';
require __DIR__ . '/public/index.php';
