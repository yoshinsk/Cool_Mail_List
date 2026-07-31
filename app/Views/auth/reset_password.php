<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\auth\reset_password.php
 * 再設定トークンを使った新パスワード入力フォーム。
 */
?>
<section class="auth-panel">
    <?php if (!$valid): ?>
        <p>再設定URLが無効、または期限切れです。</p>
        <p class="section-help">再設定URLは一度使うか期限を過ぎると無効になります。もう一度メールを受け取ってください。</p>
        <a class="btn btn-outline-secondary w-100" href="<?= h(route_url('forgot_password')) ?>">再設定メールを再送</a>
    <?php else: ?>
        <p class="section-help">新しいパスワードを設定します。更新後はこのパスワードでログインしてください。</p>
        <form method="post" class="stack-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="t" value="<?= h($token) ?>">
            <div>
                <label class="form-label" for="password">新しいパスワード</label>
                <input class="form-control" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                <div class="form-help">12文字以上で入力してください。</div>
            </div>
            <div>
                <label class="form-label" for="password_confirm">新しいパスワード確認</label>
                <input class="form-control" id="password_confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required>
                <div class="form-help">入力間違いを防ぐため、同じパスワードをもう一度入力します。</div>
            </div>
            <button class="btn btn-primary w-100" type="submit">パスワードを更新</button>
        </form>
    <?php endif; ?>
</section>
