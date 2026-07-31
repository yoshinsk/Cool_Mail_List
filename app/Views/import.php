<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\import.php
 * CSV/TSV/テキスト形式の宛先インポート画面。
 */
?>
<section class="panel">
    <div class="panel-title">ファイル取込</div>
    <p class="section-help">宛先をまとめて登録します。1列目は必ずメールアドレスにしてください。2列目以降は、氏名、会社名、タグの順に読み込みます。</p>
    <div class="help-grid mb-3">
        <div class="help-card">
            <strong>CSV例</strong>
            <pre>email@example.com,山田 太郎,サンプル株式会社,既存顧客
user@example.net,佐藤 花子,Example Inc,セミナー</pre>
        </div>
        <div class="help-card">
            <strong>TSV例</strong>
            <pre>email@example.com	山田 太郎	サンプル株式会社	既存顧客</pre>
        </div>
        <div class="help-card">
            <strong>1行1メール例</strong>
            <pre>email@example.com
user@example.net</pre>
        </div>
    </div>
    <form method="post" enctype="multipart/form-data" class="stack-form">
        <?= Csrf::field() ?>
        <div>
            <label class="form-label" for="import_file">インポートファイル</label>
            <input class="form-control" id="import_file" name="import_file" type="file" accept=".csv,.tsv,.txt,text/plain,text/csv" required>
            <div class="form-help">対応形式はCSV、TSV、TXTです。Excelから保存する場合はCSV UTF-8が最も安定します。</div>
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
                <div class="form-help">通常は自動判定で問題ありません。文字化けした場合は、元ファイルに合わせてUTF-8またはShift_JISを選びます。</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mode">重複時</label>
                <select class="form-select" id="mode" name="mode">
                    <option value="skip">スキップ</option>
                    <option value="update">上書き</option>
                </select>
                <div class="form-help">同じメールアドレスが既にある場合の扱いです。既存情報を守るならスキップ、氏名やタグを更新したいなら上書きを選びます。</div>
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
