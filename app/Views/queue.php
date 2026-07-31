<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\queue.php
 * 宛先別メールキューの状態確認画面。
 */
?>
<section class="panel">
    <div class="panel-title">配信キュー一覧</div>
    <p class="section-help">キャンペーンから作られた宛先別の送信予定です。pendingは送信待ち、sentは送信済み、temporary_failedは再試行対象です。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>キャンペーン</th><th>宛先</th><th>状態</th><th>予約</th><th>送信</th><th>再試行</th><th>エラー</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= h((string)$row['id']) ?></td>
                    <td><?= h($row['campaign_name']) ?></td>
                    <td><?= h($row['recipient_email']) ?></td>
                    <td><span class="status-badge"><?= h($row['status']) ?></span></td>
                    <td><?= h($row['scheduled_at']) ?></td>
                    <td><?= h($row['sent_at']) ?></td>
                    <td><?= h((string)$row['retry_count']) ?></td>
                    <td class="text-break"><?= h($row['error_message']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
