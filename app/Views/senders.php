<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\senders.php
 * 送信者アドレスとSMTPアカウントを固定で紐付ける管理画面。
 */
?>
<section class="panel">
    <div class="panel-title">送信者/SMTP追加</div>
    <p class="section-help">送信者は「受信者に見えるFrom」と「実際に接続するSMTP」をセットで登録します。まずは少量のテスト送信で認証情報と到達性を確認してください。</p>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-3">
            <label class="form-label" for="account_name">SMTP設定名</label>
            <input class="form-control" id="account_name" name="account_name" placeholder="例: 標準SMTP" required>
            <div class="form-help">管理用の名前です。複数のSMTPを使う時に見分けやすくします。</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="from_name">From表示名</label>
            <input class="form-control" id="from_name" name="from_name" placeholder="例: Cool Mail List" required>
            <div class="form-help">受信者に表示される差出人名です。</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="from_email">Fromメール</label>
            <input class="form-control" id="from_email" name="from_email" type="email" placeholder="例: news@example.com" required>
            <div class="form-help">受信者に表示される差出人メールアドレスです。SMTPで許可されたアドレスを使ってください。</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="reply_to">Reply-To</label>
            <input class="form-control" id="reply_to" name="reply_to" type="email" placeholder="例: support@example.com">
            <div class="form-help">返信を受けたいアドレスです。空欄ならFrom宛の返信になります。</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="smtp_host">SMTPホスト</label>
            <input class="form-control" id="smtp_host" name="smtp_host" placeholder="例: smtp.example.com" required>
            <div class="form-help">メール送信に接続するサーバ名です。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="smtp_port">SMTPポート</label>
            <input class="form-control" id="smtp_port" name="smtp_port" type="number" value="587" required>
            <div class="form-help">通常は587です。SSL接続だけを使う場合は465になることがあります。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="encryption">暗号化</label>
            <select class="form-select" id="encryption" name="encryption">
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="">なし</option>
            </select>
            <div class="form-help">送信経路の暗号化方式です。一般的にはTLSを選びます。</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="auth_username">SMTP認証ID</label>
            <input class="form-control" id="auth_username" name="auth_username" placeholder="例: news@example.com">
            <div class="form-help">SMTPログインに使うユーザー名です。認証不要なサーバなら空欄にします。</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="smtp_password">SMTPパスワード</label>
            <input class="form-control" id="smtp_password" name="smtp_password" type="password" placeholder="SMTPパスワード">
            <div class="form-help">暗号化して保存します。認証IDがある場合は入力してください。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="per_minute_limit">分上限</label>
            <input class="form-control" id="per_minute_limit" name="per_minute_limit" type="number" value="5" min="1">
            <div class="form-help">1分あたりの送信数です。最初は小さめにして到達状況を見ます。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="daily_limit">日上限</label>
            <input class="form-control" id="daily_limit" name="daily_limit" type="number" value="1000" min="1">
            <div class="form-help">1日あたりの目安上限です。SMTP契約の制限に合わせます。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="dkim_policy">DKIM方針</label>
            <select class="form-select" id="dkim_policy" name="dkim_policy">
                <option value="recommended" selected>DKIM推奨</option>
                <option value="required">DKIM必須</option>
                <option value="none">なし</option>
            </select>
            <div class="form-help">通常は推奨で開始します。DNS設定が整ってから必須へ上げます。</div>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">保存</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-title">登録済み送信者</div>
    <p class="section-help">DNS診断でFromドメインのSPF/DKIM/DMARCを確認できます。結果が整ってから本配信へ進むと迷惑メール判定のリスクを下げられます。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>From</th><th>SMTP</th><th>暗号化</th><th>分上限</th><th>日上限</th><th>DKIM</th><th>確認</th></tr></thead>
            <tbody>
            <?php foreach ($senders as $sender): ?>
                <tr>
                    <td><?= h((string)$sender['id']) ?></td>
                    <td><?= h($sender['from_name'] . ' <' . $sender['from_email'] . '>') ?></td>
                    <td><?= h($sender['smtp_host'] . ':' . $sender['smtp_port']) ?></td>
                    <td><?= h($sender['encryption']) ?></td>
                    <td><?= h((string)$sender['per_minute_limit']) ?></td>
                    <td><?= h((string)$sender['daily_limit']) ?></td>
                    <td><?= h($sender['dkim_policy']) ?></td>
                    <td><a class="btn btn-sm btn-outline-secondary" href="<?= h(route_url('dns_checks')) ?>">DNS診断</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
