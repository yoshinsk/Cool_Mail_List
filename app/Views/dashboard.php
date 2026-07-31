<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\dashboard.php
 * 配信状態と主要件数を一覧するダッシュボード。
 */
?>
<section class="metric-grid">
    <?php foreach ($stats as $label => $value): ?>
        <div class="metric">
            <span><?= h(match ($label) {
                'recipients' => '宛先総数',
                'active' => '配信可能',
                'queued' => '送信待ち',
                'sent' => '送信済み',
                'bounced' => 'バウンス停止',
                'unsubscribed' => '購読停止',
                default => $label,
            }) ?></span>
            <strong><?= h((string)$value) ?></strong>
        </div>
    <?php endforeach; ?>
</section>

<section class="panel">
    <div class="panel-title">最近の監査ログ</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>日時</th><th>操作</th><th>IP</th><th>詳細</th></tr></thead>
            <tbody>
            <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td><?= h($log['created_at']) ?></td>
                    <td><?= h($log['action']) ?></td>
                    <td><?= h($log['ip_address']) ?></td>
                    <td class="text-break"><?= h($log['details_json']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
