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
        <p class="section-help">配信登録を受け付けます。入力後に確認メールが届き、メール内のURLを開くと登録が完了します。</p>
        <form method="post" class="stack-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="org" value="<?= h($orgSlug) ?>">
            <div>
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-control" id="email" name="email" type="email" autocomplete="email" required>
                <div class="form-help">確認メールを受け取れるアドレスを入力してください。</div>
            </div>
            <div>
                <label class="form-label" for="name">氏名</label>
                <input class="form-control" id="name" name="name" autocomplete="name">
                <div class="form-help">任意です。入力すると配信メール内の氏名差し込みに使われることがあります。</div>
            </div>
            <div>
                <label class="form-label" for="company">会社名</label>
                <input class="form-control" id="company" name="company" autocomplete="organization">
                <div class="form-help">任意です。会社名で宛先を整理したい場合に入力してください。</div>
            </div>
            <button class="btn btn-primary w-100" type="submit">登録確認メールを送信</button>
        </form>
    <?php endif; ?>
</section>
