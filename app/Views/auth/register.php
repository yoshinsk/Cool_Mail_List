<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\auth\register.php
 * 管理者承認前提の利用者登録フォーム。
 */
?>
<section class="auth-panel">
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <div>
            <label class="form-label" for="email">メールアドレス</label>
            <input class="form-control" id="email" name="email" type="email" autocomplete="username" required>
        </div>
        <div>
            <label class="form-label" for="password">パスワード</label>
            <input class="form-control" id="password" name="password" type="password" minlength="10" autocomplete="new-password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">登録</button>
        <a class="btn btn-outline-secondary w-100" href="<?= h(route_url('login')) ?>">ログインへ戻る</a>
    </form>
</section>
