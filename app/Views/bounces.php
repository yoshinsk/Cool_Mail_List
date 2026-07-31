<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\bounces.php
 * バウンス受信内容の一覧画面。
 */
?>
<section class="panel">
    <div class="panel-title">バウンス受信一覧</div>
    <p class="section-help">Return-Path宛に戻ってきた配信失敗メールの解析結果です。Statusが5系の場合はハードバウンスとして宛先停止に使われます。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>Return-Pathトークン</th><th>Status</th><th>Action</th><th>診断</th><th>日時</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= h((string)$row['id']) ?></td>
                    <td><?= h($row['return_path_token']) ?></td>
                    <td><?= h($row['status_code']) ?></td>
                    <td><?= h($row['action']) ?></td>
                    <td class="text-break"><?= h($row['diagnostic']) ?></td>
                    <td><?= h($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
