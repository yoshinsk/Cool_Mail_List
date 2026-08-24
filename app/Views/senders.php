<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\senders.php
 * 送信者アドレスとSMTPアカウントを固定で紐付ける管理画面。
 */

function sender_option_selected(string $actual, string $expected): string
{
    return $actual === $expected ? 'selected' : '';
}
?>
<section class="panel">
    <div class="panel-title">送信者/SMTP追加</div>
    <p class="section-help">送信者は「受信者に見えるFrom」と「実際に接続するSMTP」をセットで登録します。通常、587番はTLS、465番はSSLを選択します。まずはSMTPチェックで認証情報と到達性を確認してください。</p>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="create">
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

<?php if ($smtpCheckResult): ?>
    <section class="panel">
        <div class="panel-title">SMTPチェック結果</div>
        <div class="alert <?= !empty($smtpCheckResult['ok']) ? 'alert-success' : 'alert-danger' ?> mb-3">
            <?= h((string)$smtpCheckResult['message']) ?>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                <tr><th>送信者</th><td><?= h((string)$smtpCheckResult['from_email']) ?></td></tr>
                <tr><th>SMTP</th><td><?= h((string)$smtpCheckResult['smtp_host'] . ':' . (string)$smtpCheckResult['smtp_port']) ?></td></tr>
                <tr><th>暗号化</th><td><?= h((string)$smtpCheckResult['encryption']) ?></td></tr>
                <tr><th>SMTP認証ID</th><td><?= !empty($smtpCheckResult['auth_user_set']) ? '設定あり' : '未設定' ?></td></tr>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-title">登録済み送信者</div>
    <p class="section-help">「SMTPチェック」はメールを送らず、SMTPサーバへの接続と認証だけを確認します。削除時に使用履歴がある場合は、過去データを守るため無効化します。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>状態</th><th>From</th><th>SMTP</th><th>暗号化</th><th>分上限</th><th>日上限</th><th>DKIM</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($senders as $sender): ?>
                <tr>
                    <td><?= h((string)$sender['id']) ?></td>
                    <td>
                        <span class="status-badge"><?= (int)$sender['is_active'] === 1 && (int)$sender['smtp_is_active'] === 1 ? '有効' : '無効' ?></span>
                    </td>
                    <td><?= h($sender['from_name'] . ' <' . $sender['from_email'] . '>') ?></td>
                    <td><?= h($sender['smtp_host'] . ':' . $sender['smtp_port']) ?></td>
                    <td><?= h($sender['encryption']) ?></td>
                    <td><?= h((string)$sender['per_minute_limit']) ?></td>
                    <td><?= h((string)$sender['daily_limit']) ?></td>
                    <td><?= h($sender['dkim_policy']) ?></td>
                    <td>
                        <div class="inline-form">
                            <form method="post">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="check_smtp">
                                <input type="hidden" name="sender_id" value="<?= h((string)$sender['id']) ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit">SMTPチェック</button>
                            </form>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= h(route_url('dns_checks')) ?>">DNS診断</a>
                            <form method="post" onsubmit="return confirm('この送信者/SMTP設定を削除します。使用履歴がある場合は無効化します。よろしいですか。')">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="sender_id" value="<?= h((string)$sender['id']) ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">削除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="9">
                        <details class="sender-edit-panel">
                            <summary>この送信者/SMTP情報を編集</summary>
                            <form method="post" class="row g-3 mt-3">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="sender_id" value="<?= h((string)$sender['id']) ?>">
                                <div class="col-md-3">
                                    <label class="form-label" for="account_name_<?= h((string)$sender['id']) ?>">SMTP設定名</label>
                                    <input class="form-control" id="account_name_<?= h((string)$sender['id']) ?>" name="account_name" value="<?= h($sender['account_name']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="from_name_<?= h((string)$sender['id']) ?>">From表示名</label>
                                    <input class="form-control" id="from_name_<?= h((string)$sender['id']) ?>" name="from_name" value="<?= h($sender['from_name']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="from_email_<?= h((string)$sender['id']) ?>">Fromメール</label>
                                    <input class="form-control" id="from_email_<?= h((string)$sender['id']) ?>" name="from_email" type="email" value="<?= h($sender['from_email']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="reply_to_<?= h((string)$sender['id']) ?>">Reply-To</label>
                                    <input class="form-control" id="reply_to_<?= h((string)$sender['id']) ?>" name="reply_to" type="email" value="<?= h($sender['reply_to']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="smtp_host_<?= h((string)$sender['id']) ?>">SMTPホスト</label>
                                    <input class="form-control" id="smtp_host_<?= h((string)$sender['id']) ?>" name="smtp_host" value="<?= h($sender['smtp_host']) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="smtp_port_<?= h((string)$sender['id']) ?>">SMTPポート</label>
                                    <input class="form-control" id="smtp_port_<?= h((string)$sender['id']) ?>" name="smtp_port" type="number" min="1" max="65535" value="<?= h((string)$sender['smtp_port']) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="encryption_<?= h((string)$sender['id']) ?>">暗号化</label>
                                    <select class="form-select" id="encryption_<?= h((string)$sender['id']) ?>" name="encryption">
                                        <option value="tls" <?= sender_option_selected((string)$sender['encryption'], 'tls') ?>>TLS</option>
                                        <option value="ssl" <?= sender_option_selected((string)$sender['encryption'], 'ssl') ?>>SSL</option>
                                        <option value="" <?= sender_option_selected((string)$sender['encryption'], '') ?>>なし</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="auth_username_<?= h((string)$sender['id']) ?>">SMTP認証ID</label>
                                    <input class="form-control" id="auth_username_<?= h((string)$sender['id']) ?>" name="auth_username" value="<?= h($sender['auth_username']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="smtp_password_<?= h((string)$sender['id']) ?>">SMTPパスワード</label>
                                    <input class="form-control" id="smtp_password_<?= h((string)$sender['id']) ?>" name="smtp_password" type="password" autocomplete="off" placeholder="<?= $sender['auth_password_ciphertext'] ? '設定済み。変更時のみ入力' : '未設定' ?>">
                                    <div class="form-help">空欄のまま更新すると、保存済みパスワードを変更しません。</div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="per_minute_limit_<?= h((string)$sender['id']) ?>">分上限</label>
                                    <input class="form-control" id="per_minute_limit_<?= h((string)$sender['id']) ?>" name="per_minute_limit" type="number" min="1" value="<?= h((string)$sender['per_minute_limit']) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="daily_limit_<?= h((string)$sender['id']) ?>">日上限</label>
                                    <input class="form-control" id="daily_limit_<?= h((string)$sender['id']) ?>" name="daily_limit" type="number" min="1" value="<?= h((string)$sender['daily_limit']) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="dkim_policy_<?= h((string)$sender['id']) ?>">DKIM方針</label>
                                    <select class="form-select" id="dkim_policy_<?= h((string)$sender['id']) ?>" name="dkim_policy">
                                        <option value="recommended" <?= sender_option_selected((string)$sender['dkim_policy'], 'recommended') ?>>DKIM推奨</option>
                                        <option value="required" <?= sender_option_selected((string)$sender['dkim_policy'], 'required') ?>>DKIM必須</option>
                                        <option value="none" <?= sender_option_selected((string)$sender['dkim_policy'], 'none') ?>>なし</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="is_active_<?= h((string)$sender['id']) ?>">状態</label>
                                    <select class="form-select" id="is_active_<?= h((string)$sender['id']) ?>" name="is_active">
                                        <option value="1" <?= (int)$sender['is_active'] === 1 && (int)$sender['smtp_is_active'] === 1 ? 'selected' : '' ?>>有効</option>
                                        <option value="0" <?= (int)$sender['is_active'] === 0 || (int)$sender['smtp_is_active'] === 0 ? 'selected' : '' ?>>無効</option>
                                    </select>
                                </div>
                                <div class="col-md-2 align-self-end">
                                    <button class="btn btn-primary w-100" type="submit">更新</button>
                                </div>
                            </form>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
