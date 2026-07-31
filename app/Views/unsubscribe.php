<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\unsubscribe.php
 * ログイン不要の購読停止確認画面。
 */
?>
<section class="auth-panel">
    <?php if (!$queue): ?>
        <p class="mb-0">購読停止URLが無効です。</p>
    <?php else: ?>
        <p><?= h($queue['email']) ?> への配信を停止します。</p>
        <p class="section-help">停止すると、この配信元からの今後のメール送信対象から外れます。</p>
        <form method="post">
            <input type="hidden" name="t" value="<?= h($queue['unsubscribe_token']) ?>">
            <button class="btn btn-primary w-100" type="submit">購読停止</button>
        </form>
    <?php endif; ?>
</section>
