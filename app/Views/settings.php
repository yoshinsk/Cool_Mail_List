<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\settings.php
 * システム設定と外部連携の現在値を確認する画面。
 */
?>
<section class="panel">
    <div class="panel-title">環境設定</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <tbody>
                <tr><th>APP_URL</th><td><?= h((string)Config::get('app.url')) ?></td></tr>
                <tr><th>QUEUE_BATCH_LIMIT</th><td><?= h((string)Config::get('queue.batch_limit')) ?></td></tr>
                <tr><th>BOUNCE_DOMAIN</th><td><?= h((string)Config::get('mail.bounce_domain')) ?></td></tr>
                <tr><th>Google Client ID</th><td><?= Config::get('google.client_id') ? '設定済み' : '未設定' ?></td></tr>
                <tr><th>OpenAI API Key</th><td><?= Config::get('openai.api_key') ? '設定済み' : '未設定' ?></td></tr>
            </tbody>
        </table>
    </div>
</section>
