<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\unsubscribes.php
 * 購読停止済み宛先の一覧画面。
 */
?>
<section class="panel">
    <div class="panel-title">購読停止一覧</div>
    <p class="section-help">受信者が購読停止URLを開いて停止した履歴です。ここに載った宛先は配信対象から外れます。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>メール</th><th>理由</th><th>日時</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= h((string)$row['id']) ?></td>
                    <td><?= h($row['email']) ?></td>
                    <td><?= h($row['reason']) ?></td>
                    <td><?= h($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
