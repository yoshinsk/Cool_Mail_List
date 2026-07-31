<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\users.php
 * 利用者の承認、停止、ロール変更を行う管理画面。
 */
?>
<section class="panel">
    <div class="panel-title">利用者一覧</div>
    <p class="section-help">登録された利用者の承認、停止、ロール、所属組織を管理します。ロールによって表示されるメニューと利用できる機能が変わります。</p>
    <div class="help-grid mb-3">
        <div class="help-card"><strong>system_admin</strong><br>全機能、設定、組織、利用者、監査ログを管理できます。</div>
        <div class="help-card"><strong>delivery_admin</strong><br>宛先、送信者、DNS、テンプレート、AI、配信を管理できます。</div>
        <div class="help-card"><strong>sender</strong><br>キャンペーン作成とキュー状況確認を行えます。</div>
        <div class="help-card"><strong>editor</strong><br>テンプレート編集とAI文面提案を行えます。</div>
        <div class="help-card"><strong>viewer</strong><br>ダッシュボードのみ確認できます。</div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>メール</th><th>組織</th><th>ロール</th><th>状態</th><th>承認日</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($users as $userRow): ?>
                <tr>
                    <td><?= h((string)$userRow['id']) ?></td>
                    <td><?= h($userRow['email']) ?></td>
                    <td><?= h($userRow['organization_name']) ?></td>
                    <td><?= h($userRow['role']) ?></td>
                    <td><?= h($userRow['status']) ?></td>
                    <td><?= h($userRow['approved_at']) ?></td>
                    <td>
                        <form method="post" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= h((string)$userRow['id']) ?>">
                            <select class="form-select form-select-sm" name="organization_id">
                                <?php foreach ($organizations as $organization): ?>
                                    <option value="<?= h((string)$organization['id']) ?>" <?= (int)$userRow['organization_id'] === (int)$organization['id'] ? 'selected' : '' ?>>
                                        <?= h($organization['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-help">所属組織</span>
                            <select class="form-select form-select-sm" name="role">
                                <?php foreach (['system_admin', 'delivery_admin', 'sender', 'editor', 'viewer'] as $role): ?>
                                    <option value="<?= h($role) ?>" <?= $userRow['role'] === $role ? 'selected' : '' ?>><?= h($role) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-help">権限ロール</span>
                            <select class="form-select form-select-sm" name="status">
                                <?php foreach (['active', 'pending_approval', 'disabled'] as $status): ?>
                                    <option value="<?= h($status) ?>" <?= $userRow['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-help">利用状態</span>
                            <button class="btn btn-sm btn-outline-primary" type="submit">更新</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
