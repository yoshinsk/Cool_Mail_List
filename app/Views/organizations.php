<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\organizations.php
 * 複数組織の作成、状態確認、公開登録URLを表示する管理画面。
 */
?>
<section class="panel">
    <div class="panel-title">組織追加</div>
    <p class="section-help">複数の運用単位を分けたい時に組織を作ります。宛先、送信者、テンプレート、キャンペーンは組織ごとに分かれます。</p>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-5">
            <label class="form-label" for="organization_name">組織名</label>
            <input class="form-control" id="organization_name" name="name" placeholder="例: 東京営業部" required>
            <div class="form-help">管理画面で表示される名前です。</div>
        </div>
        <div class="col-md-5">
            <label class="form-label" for="organization_slug">スラッグ</label>
            <input class="form-control" id="organization_slug" name="slug" placeholder="例: tokyo-sales" required>
            <div class="form-help">公開登録URLに使う英数字の識別子です。後からURLとして共有されます。</div>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">作成</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-title">組織一覧</div>
    <p class="section-help">公開登録URLを配ると、その組織の宛先としてダブルオプトイン登録されます。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>組織名</th><th>スラッグ</th><th>状態</th><th>公開登録URL</th></tr></thead>
            <tbody>
            <?php foreach ($organizations as $organization): ?>
                <tr>
                    <td><?= h((string)$organization['id']) ?></td>
                    <td><?= h($organization['name']) ?></td>
                    <td><?= h($organization['slug']) ?></td>
                    <td><span class="status-badge"><?= (int)$organization['is_active'] === 1 ? 'active' : 'disabled' ?></span></td>
                    <td><a href="<?= h(route_url('subscribe', ['org' => $organization['slug']])) ?>" target="_blank" rel="noopener"><?= h(route_url('subscribe', ['org' => $organization['slug']])) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
