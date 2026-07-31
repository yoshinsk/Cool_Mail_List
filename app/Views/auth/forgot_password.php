<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\auth\forgot_password.php
 * パスワード再設定メールの送信依頼フォーム。
 */
?>
<section class="auth-panel">
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <div>
            <label class="form-label" for="email">登録メールアドレス</label>
            <input class="form-control" id="email" name="email" type="email" autocomplete="username" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">再設定メールを送信</button>
        <a class="btn btn-outline-secondary w-100" href="<?= h(route_url('login')) ?>">ログインへ戻る</a>
    </form>
</section>
