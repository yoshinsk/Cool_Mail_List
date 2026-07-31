<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\templates.php
 * テキスト/HTMLメールテンプレート作成とテスト送信画面。
 */
?>
<section class="panel">
    <div class="panel-title">テンプレート追加</div>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <div class="row g-3">
            <div class="col-md-4"><input class="form-control" name="name" placeholder="テンプレート名" required></div>
            <div class="col-md-8"><input class="form-control" name="subject" placeholder="件名 {{name}}" required></div>
        </div>
        <div>
            <label class="form-label" for="body_text">テキスト本文</label>
            <textarea class="form-control code-textarea" id="body_text" name="body_text" rows="8" required>{{name}} 様

本文を入力してください。

購読停止: {{unsubscribe_url}}</textarea>
        </div>
        <div>
            <label class="form-label" for="body_html">HTML本文</label>
            <textarea class="form-control code-textarea" id="body_html" name="body_html" rows="8"></textarea>
        </div>
        <button class="btn btn-primary" type="submit">保存</button>
    </form>
</section>

<section class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>名称</th><th>件名</th><th>作成日</th><th>テスト送信</th></tr></thead>
            <tbody>
            <?php foreach ($templates as $template): ?>
                <tr>
                    <td><?= h((string)$template['id']) ?></td>
                    <td><?= h($template['name']) ?></td>
                    <td><?= h($template['subject']) ?></td>
                    <td><?= h($template['created_at']) ?></td>
                    <td>
                        <form method="post" action="<?= h(route_url('test_send')) ?>" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="template_id" value="<?= h((string)$template['id']) ?>">
                            <select class="form-select form-select-sm" name="sender_identity_id" required>
                                <?php foreach ($senders as $sender): ?>
                                    <option value="<?= h((string)$sender['id']) ?>"><?= h($sender['from_email']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input class="form-control form-control-sm" name="test_to" type="email" placeholder="test@example.com" required>
                            <button class="btn btn-sm btn-outline-primary" type="submit">送信</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
