<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\campaigns.php
 * キャンペーン作成、予約日時設定、宛先キュー生成画面。
 */
$canQueueCampaign = route_allowed_for_user('queue_campaign');
?>
<section class="panel">
    <div class="panel-title">キャンペーン作成</div>
    <p class="section-help">送信者、テンプレート、予約日時を組み合わせて配信予定を作ります。作成直後はまだキュー化されず、一覧の「キュー生成」で宛先別の送信予定を作ります。</p>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-4">
            <label class="form-label" for="campaign_name">キャンペーン名</label>
            <input class="form-control" id="campaign_name" name="name" placeholder="例: 8月ニュースレター" required>
            <div class="form-help">管理画面で見分けるための名前です。受信者には表示されません。</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="campaign_sender">送信者</label>
            <select class="form-select" id="campaign_sender" name="sender_identity_id" required>
                <option value="">送信者</option>
                <?php foreach ($senders as $sender): ?>
                    <option value="<?= h((string)$sender['id']) ?>"><?= h($sender['from_email']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-help">Fromに使う送信者です。先にテスト送信とDNS診断を済ませてください。</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="campaign_template">テンプレート</label>
            <select class="form-select" id="campaign_template" name="template_id" required>
                <option value="">テンプレート</option>
                <?php foreach ($templates as $template): ?>
                    <option value="<?= h((string)$template['id']) ?>"><?= h($template['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-help">本文に <code>{{unsubscribe_url}}</code> がないテンプレートは配信作成できません。</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="subject_override">件名上書き</label>
            <input class="form-control" id="subject_override" name="subject_override" placeholder="任意">
            <div class="form-help">空欄ならテンプレートの件名を使います。今回だけ件名を変えたい時に入力します。</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="scheduled_at">予約日時</label>
            <input class="form-control" id="scheduled_at" name="scheduled_at" type="datetime-local" value="<?= h(date('Y-m-d\TH:i')) ?>" required>
            <div class="form-help">この日時以降にcronが順番に送信します。すぐ送る場合は現在時刻のままで構いません。</div>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">作成</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-title">キャンペーン一覧</div>
    <p class="section-help">キュー生成後は宛先ごとの送信予定が作成されます。キュー数が0の間だけキュー生成できます。</p>
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
                        <?php if ($canQueueCampaign && (int)$campaign['queue_count'] === 0): ?>
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
