<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\recipients.php
 * 宛先の検索、登録、ステータス確認画面。
 */
?>
<section class="panel">
    <div class="panel-title">宛先追加/更新</div>
    <p class="section-help">1件ずつ宛先を登録します。同じメールアドレスがある場合は、氏名、会社名、タグ、状態を更新します。</p>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-3">
            <label class="form-label" for="recipient_email">メール</label>
            <input class="form-control" id="recipient_email" name="email" type="email" placeholder="email@example.com" required>
            <div class="form-help">配信先のメールアドレスです。重複判定にも使います。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="recipient_name">氏名</label>
            <input class="form-control" id="recipient_name" name="name" placeholder="山田 太郎">
            <div class="form-help">本文の <code>{{name}}</code> に差し込めます。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="recipient_company">会社名</label>
            <input class="form-control" id="recipient_company" name="company" placeholder="サンプル株式会社">
            <div class="form-help">本文の <code>{{company}}</code> に差し込めます。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="recipient_tags">タグ</label>
            <input class="form-control" id="recipient_tags" name="tags" placeholder="既存顧客,セミナー">
            <div class="form-help">分類用のメモです。キャンペーン作成時に配信先タグとして選択できます。</div>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="recipient_status">状態</label>
            <select class="form-select" id="recipient_status" name="status">
                <?php foreach (['active', 'unsubscribed', 'hard_bounced', 'soft_bounced', 'manually_disabled', 'pending_optin'] as $status): ?>
                    <option value="<?= h($status) ?>"><?= h($status) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-help">送信してよい宛先はactiveです。停止や確認待ちの宛先には配信しません。</div>
        </div>
        <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">保存</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-title">宛先一覧</div>
    <p class="section-help">条件で絞り込み、登録済み宛先の状態を確認します。バウンスや購読停止で状態が変わった宛先もここに表示されます。</p>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="r" value="recipients">
        <div class="col-md-3">
            <label class="form-label" for="recipient_q">キーワード</label>
            <input class="form-control" id="recipient_q" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="メール、氏名、会社、タグ">
            <div class="form-help">メール、氏名、会社名、タグをまとめて検索します。</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="recipient_filter_status">状態</label>
            <select class="form-select" id="recipient_filter_status" name="status">
                <option value="">全ステータス</option>
                <?php foreach (['active', 'unsubscribed', 'hard_bounced', 'soft_bounced', 'manually_disabled', 'pending_optin'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-help">送信可能な宛先だけ見る場合はactiveを選びます。</div>
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
