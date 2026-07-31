<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\recipients.php
 * 宛先の検索、登録、ステータス確認画面。
 */
?>
<section class="panel">
    <div class="panel-title">宛先追加/更新</div>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-3"><input class="form-control" name="email" type="email" placeholder="email@example.com" required></div>
        <div class="col-md-2"><input class="form-control" name="name" placeholder="氏名"></div>
        <div class="col-md-2"><input class="form-control" name="company" placeholder="会社名"></div>
        <div class="col-md-2"><input class="form-control" name="tags" placeholder="タグ"></div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <?php foreach (['active', 'unsubscribed', 'hard_bounced', 'soft_bounced', 'manually_disabled', 'pending_optin'] as $status): ?>
                    <option value="<?= h($status) ?>"><?= h($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">保存</button></div>
    </form>
</section>

<section class="panel">
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="r" value="recipients">
        <div class="col-md-3"><input class="form-control" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="メール、氏名、会社、タグ"></div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">全ステータス</option>
                <?php foreach (['active', 'unsubscribed', 'hard_bounced', 'soft_bounced', 'manually_disabled', 'pending_optin'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">絞り込み</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>メール</th><th>氏名</th><th>会社</th><th>タグ</th><th>状態</th><th>更新日</th></tr></thead>
            <tbody>
            <?php foreach ($recipients as $recipient): ?>
                <tr>
                    <td><?= h((string)$recipient['id']) ?></td>
                    <td><?= h($recipient['email']) ?></td>
                    <td><?= h($recipient['name']) ?></td>
                    <td><?= h($recipient['company']) ?></td>
                    <td><?= h($recipient['tags']) ?></td>
                    <td><span class="status-badge"><?= h($recipient['status']) ?></span></td>
                    <td><?= h($recipient['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
