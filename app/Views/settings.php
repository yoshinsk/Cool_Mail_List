<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\settings.php
 * システム設定と外部連携の現在値を確認する画面。
 */
?>
<section class="panel">
    <div class="panel-title">環境設定</div>
    <p class="section-help">現在の主要設定です。パスワードやAPIキーは値を表示せず、設定済みかどうかだけを表示します。</p>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <tbody>
                <tr><th>APP_URL</th><td><?= h((string)Config::get('app.url')) ?></td></tr>
                <tr><th>QUEUE_BATCH_LIMIT</th><td><?= h((string)Config::get('queue.batch_limit')) ?></td></tr>
                <tr><th>BOUNCE_DOMAIN</th><td><?= h((string)Config::get('mail.bounce_domain')) ?></td></tr>
                <tr><th>固定バウンス基準アドレス</th><td><?= h($mailSettings['bounce_base_email']) ?></td></tr>
                <tr><th>Google Client ID</th><td><?= $googleSettings['client_id'] !== '' ? '設定済み' : '未設定' ?></td></tr>
                <tr><th>許可Workspaceドメイン(任意)</th><td><?= h($googleSettings['allowed_domain'] !== '' ? $googleSettings['allowed_domain'] : '制限なし') ?></td></tr>
                <tr><th>OpenAI API Key</th><td><?= !empty($openaiKeySet) ? '設定済み' : '未設定' ?></td></tr>
                <tr><th>システムSMTP</th><td><?= h($mailSettings['system_smtp_host'] . ':' . $mailSettings['system_smtp_port']) ?></td></tr>
                <tr><th>バウンスIMAP</th><td><?= h($mailSettings['bounce_imap_host'] . ':' . $mailSettings['bounce_imap_port']) ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-title">Googleログイン設定</div>
    <p class="section-help">Googleログインを使う場合に設定します。Google Cloud Console側で、このサイトの生成元 <code><?= h(rtrim((string)Config::get('app.url'), '/')) ?></code> を許可してください。</p>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="google_settings">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="google_client_id">Google Client ID</label>
                <input class="form-control" id="google_client_id" name="google_client_id" value="<?= h($googleSettings['client_id']) ?>" placeholder="000000000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com">
                <div class="form-help">Google Cloud Consoleで作成したOAuth 2.0クライアントIDです。設定するとログイン画面にGoogleボタンが表示されます。</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="google_allowed_domain">許可Workspaceドメイン(任意)</label>
                <input class="form-control" id="google_allowed_domain" name="google_allowed_domain" value="<?= h($googleSettings['allowed_domain']) ?>" placeholder="example.com">
                <div class="form-help">特定のGoogle Workspaceだけ許可する場合に入力します。制限しない場合は空欄にします。</div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Google設定を保存</button>
    </form>
</section>

<section class="panel">
    <div class="panel-title">メール設定</div>
    <p class="section-help">パスワード再設定、配信登録確認、バウンス取得に使う共通メール設定です。パスワードは入力した時だけ更新されます。</p>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="mail_settings">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="bounce_base_email">固定バウンス基準アドレス</label>
                <input class="form-control" id="bounce_base_email" name="bounce_base_email" type="email" value="<?= h($mailSettings['bounce_base_email']) ?>" required>
                <div class="form-help">Return-Pathの基準です。実際の配信では <code>local+rp_xxx@example.com</code> の形式で使います。</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="system_mail_from">システムメールFrom</label>
                <input class="form-control" id="system_mail_from" name="system_mail_from" type="email" value="<?= h($mailSettings['system_mail_from']) ?>" required>
                <div class="form-help">パスワード再設定や配信登録確認メールの差出人アドレスです。</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="system_mail_from_name">システムメール表示名</label>
                <input class="form-control" id="system_mail_from_name" name="system_mail_from_name" value="<?= h($mailSettings['system_mail_from_name']) ?>" required>
                <div class="form-help">受信者に表示される差出人名です。</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="system_smtp_host">SMTPホスト</label>
                <input class="form-control" id="system_smtp_host" name="system_smtp_host" value="<?= h($mailSettings['system_smtp_host']) ?>" required>
                <div class="form-help">システムメール送信用のSMTPサーバです。</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="system_smtp_port">SMTPポート</label>
                <input class="form-control" id="system_smtp_port" name="system_smtp_port" type="number" min="1" value="<?= h($mailSettings['system_smtp_port']) ?>" required>
                <div class="form-help">通常は587です。SSL専用の場合は465を使うことがあります。</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="system_smtp_encryption">SMTP暗号化</label>
                <select class="form-select" id="system_smtp_encryption" name="system_smtp_encryption">
                    <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => 'なし'] as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= $mailSettings['system_smtp_encryption'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-help">SMTP接続の暗号化方式です。迷ったらTLSを選びます。</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="system_smtp_user">SMTPユーザー</label>
                <input class="form-control" id="system_smtp_user" name="system_smtp_user" value="<?= h($mailSettings['system_smtp_user']) ?>">
                <div class="form-help">SMTP認証に使うユーザー名です。</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="system_smtp_pass">SMTPパスワード</label>
                <input class="form-control" id="system_smtp_pass" name="system_smtp_pass" type="password" autocomplete="off" placeholder="<?= $mailSettings['system_smtp_pass_set'] ? '設定済み。変更時のみ入力' : '未設定' ?>">
                <div class="form-help">変更したい場合だけ入力します。保存時は暗号化されます。</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="bounce_imap_host">IMAPホスト</label>
                <input class="form-control" id="bounce_imap_host" name="bounce_imap_host" value="<?= h($mailSettings['bounce_imap_host']) ?>" required>
                <div class="form-help">バウンスメールを読むためのIMAPサーバです。</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="bounce_imap_port">IMAPポート</label>
                <input class="form-control" id="bounce_imap_port" name="bounce_imap_port" type="number" min="1" value="<?= h($mailSettings['bounce_imap_port']) ?>" required>
                <div class="form-help">SSL接続では通常993です。</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="bounce_imap_encryption">IMAP暗号化</label>
                <select class="form-select" id="bounce_imap_encryption" name="bounce_imap_encryption">
                    <?php foreach (['ssl' => 'SSL', 'tls' => 'TLS', '' => 'なし'] as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= $mailSettings['bounce_imap_encryption'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-help">IMAP接続の暗号化方式です。通常はSSLを選びます。</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bounce_imap_user">IMAPユーザー</label>
                <input class="form-control" id="bounce_imap_user" name="bounce_imap_user" value="<?= h($mailSettings['bounce_imap_user']) ?>" required>
                <div class="form-help">バウンスメールボックスへログインするユーザー名です。</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bounce_imap_pass">IMAPパスワード</label>
                <input class="form-control" id="bounce_imap_pass" name="bounce_imap_pass" type="password" autocomplete="off" placeholder="<?= $mailSettings['bounce_imap_pass_set'] ? '設定済み。変更時のみ入力' : '未設定' ?>">
                <div class="form-help">変更したい場合だけ入力します。保存時は暗号化されます。</div>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="bounce_imap_mailbox">メールボックス</label>
                <input class="form-control" id="bounce_imap_mailbox" name="bounce_imap_mailbox" value="<?= h($mailSettings['bounce_imap_mailbox']) ?>" required>
                <div class="form-help">通常はINBOXです。サブフォルダを読む場合だけ変更します。</div>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="bounce_imap_search">検索条件</label>
                <input class="form-control" id="bounce_imap_search" name="bounce_imap_search" value="<?= h($mailSettings['bounce_imap_search']) ?>" required>
                <div class="form-help">IMAP検索条件です。通常はUNSEENで未読メールだけを処理します。</div>
            </div>
            <div class="col-md-12 form-check ms-2">
                <input class="form-check-input" id="bounce_imap_mark_seen" name="bounce_imap_mark_seen" type="checkbox" value="1" <?= $mailSettings['bounce_imap_mark_seen'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="bounce_imap_mark_seen">取得後に既読化する</label>
                <div class="form-help">同じバウンスメールを何度も処理しないため、通常はオンにします。</div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">メール設定を保存</button>
    </form>
</section>

<section class="panel">
    <div class="panel-title">OpenAI API設定</div>
    <p class="section-help">AI文面提案で使うモデルとAPIキーです。生成結果はすぐ送信されず、テンプレート化してから配信に使います。</p>
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
                <div class="form-help">通常は推奨モデルで開始し、品質や費用に応じて切り替えます。</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="openai_api_key">APIキー</label>
                <input class="form-control" id="openai_api_key" name="openai_api_key" type="password" autocomplete="off" placeholder="<?= !empty($openaiKeySet) ? '設定済み。変更時のみ入力' : '未設定' ?>">
                <div class="form-help">OpenAI APIキーです。変更したい場合だけ入力します。保存時は暗号化されます。</div>
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
