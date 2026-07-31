<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\template_compare.php
 * テンプレートの保存版と現在版を件名・本文単位で差分表示する画面。
 */

function render_version_options(array $versions, string $selected): void
{
    ?>
    <option value="current" <?= $selected === 'current' ? 'selected' : '' ?>>現在版</option>
    <?php foreach ($versions as $version): ?>
        <option value="<?= h((string)$version['id']) ?>" <?= $selected === (string)$version['id'] ? 'selected' : '' ?>>
            <?= h('#' . $version['id'] . ' / ' . $version['created_at']) ?>
        </option>
    <?php endforeach; ?>
    <?php
}

function render_diff_table(string $label, array $rows): void
{
    ?>
    <h2 class="section-heading"><?= h($label) ?></h2>
    <div class="table-responsive mb-3">
        <table class="table table-sm diff-table align-middle">
            <thead><tr><th>行</th><th>左</th><th>右</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr class="<?= $row['changed'] ? 'diff-changed' : '' ?>">
                    <td><?= h((string)$row['line']) ?></td>
                    <td><pre><?= h($row['old']) ?></pre></td>
                    <td><pre><?= h($row['new']) ?></pre></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
<section class="panel">
    <div class="panel-title"><?= h($template['name']) ?></div>
    <form method="get" class="row g-3">
        <input type="hidden" name="r" value="template_compare">
        <input type="hidden" name="id" value="<?= h((string)$template['id']) ?>">
        <div class="col-md-5">
            <label class="form-label" for="left">左</label>
            <select class="form-select" id="left" name="left"><?php render_version_options($versions, $leftKey); ?></select>
        </div>
        <div class="col-md-5">
            <label class="form-label" for="right">右</label>
            <select class="form-select" id="right" name="right"><?php render_version_options($versions, $rightKey); ?></select>
        </div>
        <div class="col-md-2 align-self-end"><button class="btn btn-primary w-100" type="submit">比較</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-title"><?= h($left['label'] . ' → ' . $right['label']) ?></div>
    <?php render_diff_table('件名', $subjectDiff); ?>
    <?php render_diff_table('テキスト本文', $textDiff); ?>
    <?php render_diff_table('HTML本文', $htmlDiff); ?>
</section>
