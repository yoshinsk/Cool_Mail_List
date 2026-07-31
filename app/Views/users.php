<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\users.php
 * 利用者の承認、停止、ロール変更を行う管理画面。
 */
?>
<section class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>メール</th><th>ロール</th><th>状態</th><th>承認日</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($users as $userRow): ?>
                <tr>
                    <td><?= h((string)$userRow['id']) ?></td>
                    <td><?= h($userRow['email']) ?></td>
                    <td><?= h($userRow['role']) ?></td>
                    <td><?= h($userRow['status']) ?></td>
                    <td><?= h($userRow['approved_at']) ?></td>
                    <td>
                        <form method="post" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= h((string)$userRow['id']) ?>">
                            <select class="form-select form-select-sm" name="role">
                                <?php foreach (['system_admin', 'delivery_admin', 'sender', 'editor', 'viewer'] as $role): ?>
                                    <option value="<?= h($role) ?>" <?= $userRow['role'] === $role ? 'selected' : '' ?>><?= h($role) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select form-select-sm" name="status">
                                <?php foreach (['active', 'pending_approval', 'disabled'] as $status): ?>
                                    <option value="<?= h($status) ?>" <?= $userRow['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" type="submit">更新</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
