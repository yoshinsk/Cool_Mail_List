<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\confirm_optin.php
 * ダブルオプトイン確認URLの結果を表示する画面。
 */
?>
<section class="auth-panel">
    <?php if ($email): ?>
        <div class="alert alert-success mb-0"><?= h($email) ?> の配信登録を完了しました。</div>
    <?php else: ?>
        <div class="alert alert-danger mb-0">確認URLが無効、または期限切れです。</div>
    <?php endif; ?>
</section>
