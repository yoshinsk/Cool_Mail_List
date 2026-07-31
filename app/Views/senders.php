<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\senders.php
 * 送信者アドレスとSMTPアカウントを固定で紐付ける管理画面。
 */
?>
<section class="panel">
    <div class="panel-title">送信者/SMTP追加</div>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-3"><input class="form-control" name="account_name" placeholder="SMTP設定名" required></div>
        <div class="col-md-3"><input class="form-control" name="from_name" placeholder="From表示名" required></div>
        <div class="col-md-3"><input class="form-control" name="from_email" type="email" placeholder="Fromメール" required></div>
        <div class="col-md-3"><input class="form-control" name="reply_to" type="email" placeholder="Reply-To"></div>
        <div class="col-md-3"><input class="form-control" name="bounce_email" type="email" placeholder="bounce@example.com"></div>
        <div class="col-md-3"><input class="form-control" name="smtp_host" placeholder="SMTPホスト" required></div>
        <div class="col-md-2"><input class="form-control" name="smtp_port" type="number" value="587" required></div>
        <div class="col-md-2">
            <select class="form-select" name="encryption">
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="">なし</option>
            </select>
        </div>
        <div class="col-md-3"><input class="form-control" name="auth_username" placeholder="SMTP認証ID"></div>
        <div class="col-md-3"><input class="form-control" name="smtp_password" type="password" placeholder="SMTPパスワード"></div>
        <div class="col-md-2"><input class="form-control" name="per_minute_limit" type="number" value="5" min="1"></div>
        <div class="col-md-2"><input class="form-control" name="daily_limit" type="number" value="1000" min="1"></div>
        <div class="col-md-2">
            <select class="form-select" name="dkim_policy">
                <option value="required">DKIM必須</option>
                <option value="recommended">DKIM推奨</option>
                <option value="none">なし</option>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">保存</button></div>
    </form>
</section>

<section class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>From</th><th>SMTP</th><th>暗号化</th><th>分上限</th><th>日上限</th><th>DKIM</th></tr></thead>
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
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
