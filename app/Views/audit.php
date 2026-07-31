<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\audit.php
 * 監査ログの一覧画面。
 */
?>
<section class="panel">
    <p class="section-help">システム全体の操作履歴です。誰が、いつ、どの機能を操作したかを確認できます。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>利用者</th><th>操作</th><th>IP</th><th>詳細</th><th>日時</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= h((string)$row['id']) ?></td>
                    <td><?= h((string)$row['user_id']) ?></td>
                    <td><?= h($row['action']) ?></td>
                    <td><?= h($row['ip_address']) ?></td>
                    <td class="text-break"><?= h($row['details_json']) ?></td>
                    <td><?= h($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
