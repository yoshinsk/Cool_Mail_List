<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\import.php
 * CSV/TSV/テキスト形式の宛先インポート画面。
 */
?>
<section class="panel">
    <form method="post" enctype="multipart/form-data" class="stack-form">
        <?= Csrf::field() ?>
        <div>
            <label class="form-label" for="import_file">インポートファイル</label>
            <input class="form-control" id="import_file" name="import_file" type="file" accept=".csv,.tsv,.txt,text/plain,text/csv" required>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="encoding">文字コード</label>
                <select class="form-select" id="encoding" name="encoding">
                    <option value="auto">自動判定</option>
                    <option value="UTF-8">UTF-8</option>
                    <option value="SJIS-win">Shift_JIS</option>
                    <option value="EUC-JP">EUC-JP</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mode">重複時</label>
                <select class="form-select" id="mode" name="mode">
                    <option value="skip">スキップ</option>
                    <option value="update">上書き</option>
                </select>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">取込</button>
    </form>
</section>

<?php if ($result): ?>
    <section class="panel">
        <div class="panel-title">結果</div>
        <p>追加: <?= h((string)$result['inserted']) ?> / 更新: <?= h((string)$result['updated']) ?> / スキップ: <?= h((string)$result['skipped']) ?></p>
        <?php if ($result['errors']): ?>
            <ul class="mb-0">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>
