<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\subscribe.php
 * 公開配信登録フォームとダブルオプトイン受付完了を表示する画面。
 */
?>
<section class="auth-panel">
    <?php if ($done): ?>
        <div class="alert alert-success mb-0">確認メールを送信しました。メール内のURLを開くと登録が完了します。</div>
    <?php else: ?>
        <form method="post" class="stack-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="org" value="<?= h($orgSlug) ?>">
            <div>
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-control" id="email" name="email" type="email" autocomplete="email" required>
            </div>
            <div>
                <label class="form-label" for="name">氏名</label>
                <input class="form-control" id="name" name="name" autocomplete="name">
            </div>
            <div>
                <label class="form-label" for="company">会社名</label>
                <input class="form-control" id="company" name="company" autocomplete="organization">
            </div>
            <button class="btn btn-primary w-100" type="submit">登録確認メールを送信</button>
        </form>
    <?php endif; ?>
</section>
