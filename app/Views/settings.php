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
                <tr><th>固定バウンス基準アドレス</th><td><?= h($mailSettings['bounce_base_email']) ?></td></tr>
                <tr><th>Google Client ID</th><td><?= $googleSettings['client_id'] !== '' ? '設定済み' : '未設定' ?></td></tr>
                <tr><th>Google Workspace制限</th><td><?= h($googleSettings['allowed_domain'] !== '' ? $googleSettings['allowed_domain'] : '制限なし') ?></td></tr>
                <tr><th>OpenAI API Key</th><td><?= !empty($openaiKeySet) ? '設定済み' : '未設定' ?></td></tr>
                <tr><th>システムSMTP</th><td><?= h($mailSettings['system_smtp_host'] . ':' . $mailSettings['system_smtp_port']) ?></td></tr>
                <tr><th>バウンスIMAP</th><td><?= h($mailSettings['bounce_imap_host'] . ':' . $mailSettings['bounce_imap_port']) ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-title">Googleログイン設定</div>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="google_settings">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="google_client_id">Google Client ID</label>
                <input class="form-control" id="google_client_id" name="google_client_id" value="<?= h($googleSettings['client_id']) ?>" placeholder="000000000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="google_allowed_domain">許可Workspaceドメイン</label>
                <input class="form-control" id="google_allowed_domain" name="google_allowed_domain" value="<?= h($googleSettings['allowed_domain']) ?>" placeholder="example.com">
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Google設定を保存</button>
    </form>
</section>

<section class="panel">
    <div class="panel-title">メール設定</div>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="mail_settings">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="bounce_base_email">固定バウンス基準アドレス</label>
                <input class="form-control" id="bounce_base_email" name="bounce_base_email" type="email" value="<?= h($mailSettings['bounce_base_email']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="system_mail_from">システムメールFrom</label>
                <input class="form-control" id="system_mail_from" name="system_mail_from" type="email" value="<?= h($mailSettings['system_mail_from']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="system_mail_from_name">システムメール表示名</label>
                <input class="form-control" id="system_mail_from_name" name="system_mail_from_name" value="<?= h($mailSettings['system_mail_from_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="system_smtp_host">SMTPホスト</label>
                <input class="form-control" id="system_smtp_host" name="system_smtp_host" value="<?= h($mailSettings['system_smtp_host']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="system_smtp_port">SMTPポート</label>
                <input class="form-control" id="system_smtp_port" name="system_smtp_port" type="number" min="1" value="<?= h($mailSettings['system_smtp_port']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="system_smtp_encryption">SMTP暗号化</label>
                <select class="form-select" id="system_smtp_encryption" name="system_smtp_encryption">
                    <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => 'なし'] as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= $mailSettings['system_smtp_encryption'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="system_smtp_user">SMTPユーザー</label>
                <input class="form-control" id="system_smtp_user" name="system_smtp_user" value="<?= h($mailSettings['system_smtp_user']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="system_smtp_pass">SMTPパスワード</label>
                <input class="form-control" id="system_smtp_pass" name="system_smtp_pass" type="password" autocomplete="off" placeholder="<?= $mailSettings['system_smtp_pass_set'] ? '設定済み。変更時のみ入力' : '未設定' ?>">
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="bounce_imap_host">IMAPホスト</label>
                <input class="form-control" id="bounce_imap_host" name="bounce_imap_host" value="<?= h($mailSettings['bounce_imap_host']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="bounce_imap_port">IMAPポート</label>
                <input class="form-control" id="bounce_imap_port" name="bounce_imap_port" type="number" min="1" value="<?= h($mailSettings['bounce_imap_port']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="bounce_imap_encryption">IMAP暗号化</label>
                <select class="form-select" id="bounce_imap_encryption" name="bounce_imap_encryption">
                    <?php foreach (['ssl' => 'SSL', 'tls' => 'TLS', '' => 'なし'] as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= $mailSettings['bounce_imap_encryption'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bounce_imap_user">IMAPユーザー</label>
                <input class="form-control" id="bounce_imap_user" name="bounce_imap_user" value="<?= h($mailSettings['bounce_imap_user']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bounce_imap_pass">IMAPパスワード</label>
                <input class="form-control" id="bounce_imap_pass" name="bounce_imap_pass" type="password" autocomplete="off" placeholder="<?= $mailSettings['bounce_imap_pass_set'] ? '設定済み。変更時のみ入力' : '未設定' ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="bounce_imap_mailbox">メールボックス</label>
                <input class="form-control" id="bounce_imap_mailbox" name="bounce_imap_mailbox" value="<?= h($mailSettings['bounce_imap_mailbox']) ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="bounce_imap_search">検索条件</label>
                <input class="form-control" id="bounce_imap_search" name="bounce_imap_search" value="<?= h($mailSettings['bounce_imap_search']) ?>" required>
            </div>
            <div class="col-md-12 form-check ms-2">
                <input class="form-check-input" id="bounce_imap_mark_seen" name="bounce_imap_mark_seen" type="checkbox" value="1" <?= $mailSettings['bounce_imap_mark_seen'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="bounce_imap_mark_seen">取得後に既読化する</label>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">メール設定を保存</button>
    </form>
</section>

<section class="panel">
    <div class="panel-title">OpenAI API設定</div>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="openai">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="openai_model">モデル</label>
                <select class="form-select" id="openai_model" name="openai_model" required>
                    <?php foreach ($openaiModelOptions as $option): ?>
                        <option value="<?= h($option['id']) ?>" <?= ($openaiModel ?? '') === $option['id'] ? 'selected' : '' ?>>
                            <?= h($option['name'] . ' / ' . $option['id'] . ($option['recommended'] ? ' / 推奨' : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="openai_api_key">APIキー</label>
                <input class="form-control" id="openai_api_key" name="openai_api_key" type="password" autocomplete="off" placeholder="<?= !empty($openaiKeySet) ? '設定済み。変更時のみ入力' : '未設定' ?>">
            </div>
        </div>
        <button class="btn btn-primary" type="submit">保存</button>
    </form>
    <div class="table-responsive mt-3">
        <table class="table table-sm align-middle">
            <thead><tr><th>選択肢</th><th>API</th><th>特徴</th><th>コスト感</th><th>主用途</th></tr></thead>
            <tbody>
            <?php foreach ($openaiModelOptions as $option): ?>
                <tr>
                    <td><?= h($option['name']) ?><br><span class="text-muted"><?= h($option['id']) ?></span></td>
                    <td><?= h($option['api']) ?></td>
                    <td><?= h($option['features']) ?></td>
                    <td><?= h($option['cost_level']) ?></td>
                    <td><?= h($option['summary']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
