<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\template_edit.php
 * メールテンプレートを編集し、過去版への差分導線を表示する画面。
 */
?>
<section class="panel">
    <div class="panel-title">テンプレート編集</div>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= h((string)$template['id']) ?>">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="name">テンプレート名</label>
                <input class="form-control" id="name" name="name" value="<?= h($template['name']) ?>" required>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="subject">件名</label>
                <input class="form-control" id="subject" name="subject" value="<?= h($template['subject']) ?>" required>
            </div>
        </div>
        <div>
            <label class="form-label" for="body_text">テキスト本文</label>
            <textarea class="form-control code-textarea" id="body_text" name="body_text" rows="10" required><?= h($template['body_text']) ?></textarea>
        </div>
        <div>
            <label class="form-label" for="body_html">HTML本文</label>
            <textarea class="form-control code-textarea" id="body_html" name="body_html" rows="10"><?= h($template['body_html']) ?></textarea>
        </div>
        <div class="inline-form">
            <button class="btn btn-primary" type="submit">更新</button>
            <a class="btn btn-outline-secondary" href="<?= h(route_url('template_compare', ['id' => (int)$template['id']])) ?>">差分比較</a>
            <a class="btn btn-outline-secondary" href="<?= h(route_url('templates')) ?>">一覧へ戻る</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-title">保存版</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>件名</th><th>保存日</th><th>比較</th></tr></thead>
            <tbody>
            <?php foreach ($versions as $version): ?>
                <tr>
                    <td><?= h((string)$version['id']) ?></td>
                    <td><?= h($version['subject']) ?></td>
                    <td><?= h($version['created_at']) ?></td>
                    <td><a class="btn btn-sm btn-outline-secondary" href="<?= h(route_url('template_compare', ['id' => (int)$template['id'], 'left' => (int)$version['id'], 'right' => 'current'])) ?>">現在版と比較</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
