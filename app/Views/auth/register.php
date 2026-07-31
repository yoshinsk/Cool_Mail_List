<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\auth\register.php
 * 管理者承認前提の利用者登録フォーム。
 */
?>
<section class="auth-panel">
    <p class="section-help">利用者登録後、管理者が承認するとログインできるようになります。</p>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <div>
            <label class="form-label" for="email">メールアドレス</label>
            <input class="form-control" id="email" name="email" type="email" autocomplete="username" required>
            <div class="form-help">ログインIDとして使います。通知や承認確認にも使うため、受信できるアドレスを入力してください。</div>
        </div>
        <div>
            <label class="form-label" for="password">パスワード</label>
            <input class="form-control" id="password" name="password" type="password" minlength="10" autocomplete="new-password" required>
            <div class="form-help">10文字以上で設定してください。承認後のログインに使います。</div>
        </div>
        <button class="btn btn-primary w-100" type="submit">登録</button>
        <a class="btn btn-outline-secondary w-100" href="<?= h(route_url('login')) ?>">ログインへ戻る</a>
    </form>
</section>
