<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\auth\reset_password.php
 * 再設定トークンを使った新パスワード入力フォーム。
 */
?>
<section class="auth-panel">
    <?php if (!$valid): ?>
        <p>再設定URLが無効、または期限切れです。</p>
        <a class="btn btn-outline-secondary w-100" href="<?= h(route_url('forgot_password')) ?>">再設定メールを再送</a>
    <?php else: ?>
        <form method="post" class="stack-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="t" value="<?= h($token) ?>">
            <div>
                <label class="form-label" for="password">新しいパスワード</label>
                <input class="form-control" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
            </div>
            <div>
                <label class="form-label" for="password_confirm">新しいパスワード確認</label>
                <input class="form-control" id="password_confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">パスワードを更新</button>
        </form>
    <?php endif; ?>
</section>
