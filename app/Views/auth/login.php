<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\auth\login.php
 * 利用者ログインフォーム。
 */
?>
<section class="auth-panel">
    <p class="section-help">承認済みの利用者だけがログインできます。まだ承認されていない場合は、管理者の承認後に利用できます。</p>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <div>
            <label class="form-label" for="email">メールアドレス</label>
            <input class="form-control" id="email" name="email" type="email" autocomplete="username" required>
            <div class="form-help">登録済みのメールアドレスを入力します。</div>
        </div>
        <div>
            <label class="form-label" for="password">パスワード</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
            <div class="form-help">5回連続で失敗すると一時的にロックされます。</div>
        </div>
        <button class="btn btn-primary w-100" type="submit">ログイン</button>
        <a class="btn btn-outline-secondary w-100" href="<?= h(route_url('register')) ?>">利用者登録</a>
        <a class="btn btn-link w-100" href="<?= h(route_url('forgot_password')) ?>">パスワード再設定</a>
    </form>

    <?php if (!empty($googleSettings['client_id'])): ?>
        <div class="auth-separator"><span>または</span></div>
        <p class="form-help text-center">Googleログインは、管理者が承認したアカウントまたは承認待ち登録に使えます。</p>
        <div id="googleLoginButton" class="google-login-box"></div>
        <form id="googleLoginForm" method="post" action="<?= h(route_url('google_callback')) ?>" hidden>
            <?= Csrf::field() ?>
            <input type="hidden" name="credential" id="googleCredential">
        </form>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <script>
            window.addEventListener('load', function () {
                if (!window.google || !google.accounts || !google.accounts.id) {
                    return;
                }
                google.accounts.id.initialize({
                    client_id: <?= json_encode($googleSettings['client_id'], JSON_UNESCAPED_SLASHES) ?>,
                    callback: function (response) {
                        document.getElementById('googleCredential').value = response.credential || '';
                        document.getElementById('googleLoginForm').submit();
                    }
                });
                google.accounts.id.renderButton(
                    document.getElementById('googleLoginButton'),
                    { theme: 'outline', size: 'large', width: 320 }
                );
            });
        </script>
    <?php endif; ?>
</section>
