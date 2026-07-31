<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\ai.php
 * OpenAI APIを使ったメール文面提案とテンプレート採用画面。
 */
?>
<section class="panel">
    <div class="panel-title">文面提案</div>
    <?php if (!$apiKeyReady): ?>
        <div class="alert alert-warning">OpenAI APIキーが未設定です。システム設定でAPIキーを保存してください。</div>
    <?php endif; ?>
    <form method="post" class="stack-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="generate">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="purpose">配信目的</label>
                <input class="form-control" id="purpose" name="purpose" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="audience">対象者</label>
                <input class="form-control" id="audience" name="audience" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tone">トーン</label>
                <select class="form-select" id="tone" name="tone">
                    <option value="丁寧で簡潔">丁寧で簡潔</option>
                    <option value="親しみやすい">親しみやすい</option>
                    <option value="法人向けで堅実">法人向けで堅実</option>
                    <option value="緊急性を抑えて明確">緊急性を抑えて明確</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="length">文字数目安</label>
                <input class="form-control" id="length" name="length" value="600字程度">
            </div>
            <div class="col-md-4 form-check align-self-end ms-2">
                <input class="form-check-input" id="with_html" name="with_html" type="checkbox" value="1" checked>
                <label class="form-check-label" for="with_html">HTML本文も生成</label>
            </div>
        </div>
        <div>
            <label class="form-label" for="product">商品/サービス概要</label>
            <textarea class="form-control" id="product" name="product" rows="3" required></textarea>
        </div>
        <div>
            <label class="form-label" for="points">伝えたい要点</label>
            <textarea class="form-control" id="points" name="points" rows="4" required></textarea>
        </div>
        <div>
            <label class="form-label" for="cta">CTA</label>
            <input class="form-control" id="cta" name="cta" placeholder="例: 問い合わせフォームから相談する">
        </div>
        <button class="btn btn-primary" type="submit" <?= $apiKeyReady ? '' : 'disabled' ?>>生成</button>
    </form>
</section>

<?php if ($latestDraft): ?>
    <section class="panel">
        <div class="panel-title">生成結果</div>
        <h2 class="section-heading"><?= h($latestDraft['subject']) ?></h2>
        <pre class="draft-preview"><?= h($latestDraft['body_text']) ?></pre>
        <?php if (!empty($latestDraft['body_html'])): ?>
            <details class="mt-3">
                <summary>HTML本文</summary>
                <pre class="draft-preview"><?= h($latestDraft['body_html']) ?></pre>
            </details>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-title">最近の生成結果</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>件名</th><th>モデル</th><th>実行者</th><th>日時</th><th>採用</th></tr></thead>
            <tbody>
            <?php foreach ($results as $row): ?>
                <?php $draft = json_decode((string)$row['result'], true) ?: []; ?>
                <tr>
                    <td><?= h((string)$row['id']) ?></td>
                    <td><?= h((string)($draft['subject'] ?? '')) ?></td>
                    <td><?= h($row['model']) ?></td>
                    <td><?= h($row['user_email']) ?></td>
                    <td><?= h($row['created_at']) ?></td>
                    <td>
                        <?php if ($row['adopted_at']): ?>
                            <span class="status-badge">採用済み</span>
                        <?php else: ?>
                            <form method="post" action="<?= h(route_url('ai')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="adopt">
                                <input type="hidden" name="result_id" value="<?= h((string)$row['id']) ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit">テンプレート化</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
