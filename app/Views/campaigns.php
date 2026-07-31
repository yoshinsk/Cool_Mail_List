<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\campaigns.php
 * キャンペーン作成、予約日時設定、宛先キュー生成画面。
 */
?>
<section class="panel">
    <div class="panel-title">キャンペーン作成</div>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-4"><input class="form-control" name="name" placeholder="キャンペーン名" required></div>
        <div class="col-md-4">
            <select class="form-select" name="sender_identity_id" required>
                <option value="">送信者</option>
                <?php foreach ($senders as $sender): ?>
                    <option value="<?= h((string)$sender['id']) ?>"><?= h($sender['from_email']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" name="template_id" required>
                <option value="">テンプレート</option>
                <?php foreach ($templates as $template): ?>
                    <option value="<?= h((string)$template['id']) ?>"><?= h($template['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6"><input class="form-control" name="subject_override" placeholder="件名上書き 任意"></div>
        <div class="col-md-4"><input class="form-control" name="scheduled_at" type="datetime-local" value="<?= h(date('Y-m-d\TH:i')) ?>" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">作成</button></div>
    </form>
</section>

<section class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>名称</th><th>From</th><th>テンプレート</th><th>状態</th><th>予約日時</th><th>キュー</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td><?= h((string)$campaign['id']) ?></td>
                    <td><?= h($campaign['name']) ?></td>
                    <td><?= h($campaign['from_email']) ?></td>
                    <td><?= h($campaign['template_name']) ?></td>
                    <td><span class="status-badge"><?= h($campaign['status']) ?></span></td>
                    <td><?= h($campaign['scheduled_at']) ?></td>
                    <td><?= h((string)$campaign['queue_count']) ?></td>
                    <td>
                        <?php if ((int)$campaign['queue_count'] === 0): ?>
                            <form method="post" action="<?= h(route_url('queue_campaign')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= h((string)$campaign['id']) ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit">キュー生成</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
