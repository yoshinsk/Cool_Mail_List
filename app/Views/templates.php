<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\templates.php
 * テキスト/HTMLメールテンプレート作成とテスト送信画面。
 */
$canDeleteTemplates = (current_user()['role'] ?? '') === 'system_admin';
?>
<section class="panel">
    <div class="panel-title">テンプレート追加</div>
    <p class="section-help">配信メールのひな形を作ります。本文には必ず <code>{{unsubscribe_url}}</code> を入れてください。名前や会社名は <code>{{name}}</code>、<code>{{company}}</code> で差し込めます。</p>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="template_name">テンプレート名</label>
                <input class="form-control" id="template_name" name="name" placeholder="例: 8月ニュースレター" required>
                <div class="form-help">管理画面で見分けるための名前です。受信者には表示されません。</div>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="template_subject">件名</label>
                <input class="form-control" id="template_subject" name="subject" placeholder="例: {{name}} 様へ新機能のお知らせ" required>
                <div class="form-help">受信者のメールソフトに表示される件名です。必要に応じて差し込みタグを使えます。</div>
            </div>
        </div>
        <div>
            <label class="form-label" for="body_text">テキスト本文</label>
            <textarea class="form-control code-textarea" id="body_text" name="body_text" rows="8" required>{{name}} 様

本文を入力してください。

購読停止: {{unsubscribe_url}}</textarea>
            <div class="form-help">HTMLを表示しないメールソフトでも読める本文です。配信前の安全策として購読停止URLを必ず残してください。</div>
        </div>
        <div>
            <label class="form-label" for="body_html">HTML本文</label>
            <textarea class="form-control code-textarea" id="body_html" name="body_html" rows="8"></textarea>
            <div class="form-help">装飾したメールを送りたい場合だけ入力します。未入力ならテキスト本文から簡易HTMLを作ります。</div>
        </div>
        <button class="btn btn-primary" type="submit">保存</button>
    </form>
</section>

<section class="panel">
    <div class="panel-title">保存済みテンプレート</div>
    <p class="section-help">編集すると更新前の版が自動で残ります。削除は system_admin のみ可能で、キャンペーンで使用中のテンプレートは削除できません。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>名称</th><th>件名</th><th>作成日</th><th>操作</th><th>テスト送信</th></tr></thead>
            <tbody>
            <?php foreach ($templates as $template): ?>
                <tr>
                    <td><?= h((string)$template['id']) ?></td>
                    <td><?= h($template['name']) ?></td>
                    <td><?= h($template['subject']) ?></td>
                    <td><?= h($template['created_at']) ?></td>
                    <td>
                        <div class="inline-form">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= h(route_url('template_edit', ['id' => (int)$template['id']])) ?>">編集</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= h(route_url('template_compare', ['id' => (int)$template['id']])) ?>">差分</a>
                            <?php if ($canDeleteTemplates): ?>
                                <form method="post" action="<?= h(route_url('template_delete')) ?>" onsubmit="return confirm('このテンプレートを削除します。キャンペーンで使用中の場合は削除できません。よろしいですか。')">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= h((string)$template['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">削除</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
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
                        <div class="form-help mt-1">本配信前に、自分または確認担当者のメールアドレスへ試送できます。</div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
