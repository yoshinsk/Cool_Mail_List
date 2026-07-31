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
                <tr><th>Google Client ID</th><td>後回し</td></tr>
                <tr><th>OpenAI API Key</th><td><?= !empty($openaiKeySet) ? '設定済み' : '未設定' ?></td></tr>
                <tr><th>System SMTP</th><td><?= h((string)Config::get('system_mail.smtp_host') . ':' . (string)Config::get('system_mail.smtp_port')) ?></td></tr>
                <tr><th>Bounce IMAP</th><td><?= h((string)Config::get('bounce_imap.host') . ':' . (string)Config::get('bounce_imap.port')) ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-title">OpenAI API設定</div>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="openai_model">モデル</label>
                <input class="form-control" id="openai_model" name="openai_model" value="<?= h($openaiModel ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="openai_api_key">APIキー</label>
                <input class="form-control" id="openai_api_key" name="openai_api_key" type="password" autocomplete="off" placeholder="<?= !empty($openaiKeySet) ? '設定済み。変更時のみ入力' : '未設定' ?>">
            </div>
        </div>
        <button class="btn btn-primary" type="submit">保存</button>
    </form>
</section>
